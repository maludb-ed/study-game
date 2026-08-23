"""Cert Arena record-memory MCP server (client-facing, read-only).

Answers the Phase 0 record questions over PostgreSQL through the
studygame_records_ro role. The four analytics tools load the SAME SQL files the
application screens use (app/features/analytics/sql/) — screens and tools can
never disagree about what a number means.
"""
from __future__ import annotations

import json
import os
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))

from mcp.server import MCPServer
from pydantic import BaseModel, ConfigDict, Field

from common import (
    Pools, ToolTimer, error_json, guard_search_sql, load_shared_sql,
    log_tool_call, make_auth_middleware, rows_to_json, wrap_with_limit,
)

SERVER = "studygame_records_mcp"
mcp = MCPServer(SERVER)
pools = Pools("RECORDS_MCP_DSN")

READINESS_NOTE = (
    "Weighted score = sum(domain accuracy x blueprint weight) scaled to 100-1000 as "
    "100 + 900 x weighted_accuracy; unseen domains count 0. 'Ready' means sustained >=720 "
    "with >=60% of the blueprint seen. An indicator, not a Pearson VUE predictor. "
    "Claimed answers only."
)


async def _exam_id(exam_code: str) -> int | None:
    row = await pools.read.fetchrow("SELECT id FROM exams WHERE upper(code) = upper($1)", exam_code.strip())
    return None if row is None else row["id"]


async def _member(member: str) -> dict | None:
    row = await pools.read.fetchrow(
        """SELECT id, display_name, email FROM users
           WHERE email ILIKE $1 OR display_name ILIKE '%' || $1 || '%'
           ORDER BY (email ILIKE $1) DESC LIMIT 1""",
        member.strip(),
    )
    return None if row is None else dict(row)


async def _logged(tool: str, arguments: dict, coro):
    await pools.ensure()
    with ToolTimer() as timer:
        result = await coro
    await log_tool_call(pools, SERVER, tool, arguments, timer.duration_ms)
    return result


class ListQuestionsInput(BaseModel):
    model_config = ConfigDict(str_strip_whitespace=True, extra="forbid")
    exam_code: str = Field(default="", description="Filter: CCAO-F, CCDV-F, CCAR-F, or CCAR-P")
    domain: str = Field(default="", description="Domain name fragment, e.g. 'Prompting'")
    status: str = Field(default="", pattern="^(draft|active|retired)?$")
    difficulty: str = Field(default="", pattern="^(easy|medium|hard)?$")
    search: str = Field(default="", description="Text fragment matched against stems")
    limit: int = Field(default=20, ge=1, le=100)
    offset: int = Field(default=0, ge=0)


@mcp.tool(name="list_questions", annotations={"title": "List Questions", "readOnlyHint": True, "openWorldHint": False})
async def list_questions(params: ListQuestionsInput) -> str:
    """Browse the study question bank with filters. Call this to find questions by exam,
    domain, status, difficulty, or text — e.g. before drilling, editing, or auditing coverage."""
    async def run():
        return rows_to_json(await pools.read.fetch(
            """SELECT q.id, e.code AS exam, d.name AS domain, q.stem, q.status, q.difficulty,
                      count(DISTINCT gq.id) AS times_played,
                      CASE WHEN count(a.id) = 0 THEN NULL
                           ELSE round(count(a.id) FILTER (WHERE NOT a.is_correct)::numeric / count(a.id), 2)
                      END AS miss_rate
               FROM questions q
               JOIN exams e ON e.id = q.exam_id
               JOIN domains d ON d.id = q.domain_id
               LEFT JOIN game_questions gq ON gq.question_id = q.id
               LEFT JOIN answers a ON a.game_question_id = gq.id
               WHERE ($1 = '' OR upper(e.code) = upper($1))
                 AND ($2 = '' OR d.name ILIKE '%' || $2 || '%')
                 AND ($3 = '' OR q.status = $3)
                 AND ($4 = '' OR q.difficulty = $4)
                 AND ($5 = '' OR q.stem ILIKE '%' || $5 || '%')
               GROUP BY q.id, e.code, d.name
               ORDER BY q.updated_at DESC
               LIMIT $6 OFFSET $7""",
            params.exam_code, params.domain, params.status, params.difficulty,
            params.search, params.limit, params.offset,
        ))
    return await _logged("list_questions", params.model_dump(), run())


class BankCoverageInput(BaseModel):
    model_config = ConfigDict(str_strip_whitespace=True, extra="forbid")
    exam_code: str = Field(..., min_length=4, description="CCAO-F, CCDV-F, CCAR-F, or CCAR-P")
    game_size: int = Field(default=10, ge=5, le=30)


@mcp.tool(name="bank_coverage", annotations={"title": "Bank Coverage", "readOnlyHint": True, "openWorldHint": False})
async def bank_coverage(params: BankCoverageInput) -> str:
    """Whether the question bank can fill a blueprint-weighted game for an exam: per domain,
    the official weight, active question count, the target for a game of game_size, and a
    shortfall flag. Call this before scheduling a game night or planning question writing."""
    async def run():
        exam_id = await _exam_id(params.exam_code)
        if exam_id is None:
            return error_json(f"no exam matches '{params.exam_code}'; use CCAO-F, CCDV-F, CCAR-F, or CCAR-P")
        rows = await pools.read.fetch(
            """SELECT d.name AS domain, d.weight_pct,
                      count(q.id) FILTER (WHERE q.status = 'active') AS active_questions,
                      round(d.weight_pct / 100 * $2)::int AS target_for_game
               FROM domains d
               LEFT JOIN questions q ON q.domain_id = d.id
               WHERE d.exam_id = $1
               GROUP BY d.id ORDER BY d.display_order""",
            exam_id, params.game_size,
        )
        data = [dict(r) | {"short": r["active_questions"] < r["target_for_game"]} for r in rows]
        return json.dumps({"rows": [{k: (float(v) if k == "weight_pct" else v) for k, v in d.items()} for d in data]})
    return await _logged("bank_coverage", params.model_dump(), run())


class ListGamesInput(BaseModel):
    model_config = ConfigDict(str_strip_whitespace=True, extra="forbid")
    exam_code: str = Field(default="")
    limit: int = Field(default=10, ge=1, le=50)
    offset: int = Field(default=0, ge=0)


@mcp.tool(name="list_games", annotations={"title": "List Games", "readOnlyHint": True, "openWorldHint": False})
async def list_games(params: ListGamesInput) -> str:
    """Recent game nights: date, exam, host, state, player count, and the winner when the
    game finished. Call this to find a game_id for game_report or to review play history."""
    async def run():
        return rows_to_json(await pools.read.fetch(
            """SELECT g.id AS game_id, g.created_at AS played_at, e.code AS exam, g.state,
                      u.display_name AS host,
                      (SELECT count(*) FROM game_players gp WHERE gp.game_id = g.id AND gp.kicked_at IS NULL) AS players,
                      (SELECT gp.nickname FROM game_players gp
                        WHERE gp.game_id = g.id AND gp.final_rank = 1 LIMIT 1) AS winner
               FROM games g JOIN exams e ON e.id = g.exam_id JOIN users u ON u.id = g.host_user_id
               WHERE ($1 = '' OR upper(e.code) = upper($1))
               ORDER BY g.created_at DESC LIMIT $2 OFFSET $3""",
            params.exam_code, params.limit, params.offset,
        ))
    return await _logged("list_games", params.model_dump(), run())


class GameReportInput(BaseModel):
    model_config = ConfigDict(extra="forbid")
    game_id: int | None = Field(default=None, ge=1, description="Omit for the latest finished game")


@mcp.tool(name="game_report", annotations={"title": "Game Report", "readOnlyHint": True, "openWorldHint": False})
async def game_report(params: GameReportInput) -> str:
    """Full report for one game: podium with final scores, and per-question correct rates.
    Call this for 'how did game night go' or any question about a specific game's results."""
    async def run():
        game_id = params.game_id
        if game_id is None:
            row = await pools.read.fetchrow("SELECT id FROM games WHERE state = 'ended' ORDER BY ended_at DESC LIMIT 1")
            if row is None:
                return error_json("no finished games yet")
            game_id = row["id"]
        header = await pools.read.fetchrow(
            """SELECT g.id AS game_id, g.created_at, g.state, e.code AS exam, u.display_name AS host,
                      g.question_count, g.seconds_per_question
               FROM games g JOIN exams e ON e.id = g.exam_id JOIN users u ON u.id = g.host_user_id
               WHERE g.id = $1""", game_id)
        if header is None:
            return error_json(f"no game {game_id}")
        podium = await pools.read.fetch(
            """SELECT final_rank AS rank, nickname, final_score AS score
               FROM game_players WHERE game_id = $1 AND kicked_at IS NULL AND final_rank IS NOT NULL
               ORDER BY final_rank""", game_id)
        questions = await pools.read.fetch(
            """SELECT gq.position, q.stem, d.name AS domain,
                      count(a.id) AS answers,
                      count(a.id) FILTER (WHERE a.is_correct) AS correct,
                      CASE WHEN count(a.id) = 0 THEN NULL
                           ELSE round(avg(a.response_ms) / 1000.0, 1) END AS avg_response_s
               FROM game_questions gq
               JOIN questions q ON q.id = gq.question_id
               JOIN domains d ON d.id = q.domain_id
               LEFT JOIN answers a ON a.game_question_id = gq.id
               WHERE gq.game_id = $1 GROUP BY gq.position, q.stem, d.name ORDER BY gq.position""", game_id)
        from common import _jsonable
        return json.dumps({
            "game": {k: _jsonable(v) for k, v in dict(header).items()},
            "podium": [dict(r) for r in podium],
            "questions": [{k: _jsonable(v) for k, v in dict(r).items()} for r in questions],
        }, ensure_ascii=False)
    return await _logged("game_report", params.model_dump(), run())


class GroupPerformanceInput(BaseModel):
    model_config = ConfigDict(str_strip_whitespace=True, extra="forbid")
    exam_code: str = Field(..., min_length=4)
    since_date: str = Field(default="", description="ISO date; only count answers on/after it")


@mcp.tool(name="group_domain_performance", annotations={"title": "Group Domain Performance", "readOnlyHint": True, "openWorldHint": False})
async def group_domain_performance(params: GroupPerformanceInput) -> str:
    """The study group's accuracy per blueprint domain for one exam (all submitted answers,
    claimed or not). Call this for 'which domain is the group weakest in' and coverage reviews.
    Uses the same SQL as the application's analytics screen."""
    async def run():
        exam_id = await _exam_id(params.exam_code)
        if exam_id is None:
            return error_json(f"no exam matches '{params.exam_code}'")
        sql, order = load_shared_sql("group_domain_performance")
        args = {"exam_id": exam_id, "since": params.since_date or None}
        rows = await pools.read.fetch(sql, *[args[p] for p in order])
        return rows_to_json(rows, note="All submitted answers count, claimed or not (matches the Group Readiness screen).")
    return await _logged("group_domain_performance", params.model_dump(), run())


class MemberReadinessInput(BaseModel):
    model_config = ConfigDict(str_strip_whitespace=True, extra="forbid")
    member: str = Field(..., min_length=2, description="Member display name or email")
    exam_code: str = Field(..., min_length=4)


@mcp.tool(name="member_readiness", annotations={"title": "Member Readiness", "readOnlyHint": True, "openWorldHint": False})
async def member_readiness(params: MemberReadinessInput) -> str:
    """Is this member ready to book the exam? Per-domain accuracy plus the blueprint-weighted
    score on the exam's 100-1000 scale against the 720 passing bar, with blueprint coverage.
    Claimed answers only. Uses the same SQL and formula as the member readiness screen."""
    async def run():
        member = await _member(params.member)
        if member is None:
            return error_json(f"no member matches '{params.member}'")
        exam_id = await _exam_id(params.exam_code)
        if exam_id is None:
            return error_json(f"no exam matches '{params.exam_code}'")
        sql, order = load_shared_sql("member_readiness")
        args = {"user_id": member["id"], "exam_id": exam_id}
        rows = [dict(r) for r in await pools.read.fetch(sql, *[args[p] for p in order])]
        weighted = 0.0
        coverage = 0.0
        for r in rows:
            weight = float(r["weight_pct"])
            answered = int(r["answered_count"])
            accuracy = (int(r["correct_count"]) / answered) if answered else 0.0
            r["accuracy_pct"] = round(accuracy * 100) if answered else None
            r["weight_pct"] = weight
            weighted += weight * accuracy
            if answered:
                coverage += weight
        score = round(100 + 900 * weighted / 100)
        band = ("not_enough_data" if coverage < 60
                else "on_track" if score >= 720
                else "getting_close" if score >= 600 else "needs_work")
        return json.dumps({
            "member": member["display_name"], "exam": params.exam_code.upper(),
            "weighted_score": score, "passing_bar": 720,
            "blueprint_coverage_pct": round(coverage), "band": band,
            "domains": [{k: (float(v) if k == "weight_pct" else v) for k, v in r.items()
                         if k in ("domain_name", "weight_pct", "answered_count", "correct_count",
                                  "accuracy_pct", "seen_question_count", "active_question_count")}
                        for r in rows],
            "note": READINESS_NOTE,
        }, ensure_ascii=False)
    return await _logged("member_readiness", params.model_dump(), run())


class MemberGapsInput(BaseModel):
    model_config = ConfigDict(str_strip_whitespace=True, extra="forbid")
    member: str = Field(..., min_length=2)
    exam_code: str = Field(..., min_length=4)
    limit: int = Field(default=20, ge=1, le=100)


@mcp.tool(name="member_question_gaps", annotations={"title": "Member Question Gaps", "readOnlyHint": True, "openWorldHint": False})
async def member_question_gaps(params: MemberGapsInput) -> str:
    """What should this member study next: active questions they have never seen, and
    questions they have answered wrong more than once (claimed answers only)."""
    async def run():
        member = await _member(params.member)
        if member is None:
            return error_json(f"no member matches '{params.member}'")
        exam_id = await _exam_id(params.exam_code)
        if exam_id is None:
            return error_json(f"no exam matches '{params.exam_code}'")
        unseen = await pools.read.fetch(
            """SELECT q.id, d.name AS domain, q.stem, q.difficulty
               FROM questions q JOIN domains d ON d.id = q.domain_id
               WHERE q.exam_id = $1 AND q.status = 'active'
                 AND NOT EXISTS (
                     SELECT 1 FROM answers a
                     JOIN game_questions gq ON gq.id = a.game_question_id
                     JOIN game_players gp ON gp.id = a.game_player_id
                     WHERE gq.question_id = q.id AND gp.user_id = $2)
               ORDER BY d.display_order, q.id LIMIT $3""",
            exam_id, member["id"], params.limit)
        missed = await pools.read.fetch(
            """SELECT q.id, d.name AS domain, q.stem,
                      count(*) FILTER (WHERE NOT a.is_correct) AS times_missed, count(*) AS times_answered
               FROM answers a
               JOIN game_players gp ON gp.id = a.game_player_id
               JOIN game_questions gq ON gq.id = a.game_question_id
               JOIN questions q ON q.id = gq.question_id
               JOIN domains d ON d.id = q.domain_id
               WHERE gp.user_id = $1 AND q.exam_id = $2
               GROUP BY q.id, d.name, q.stem
               HAVING count(*) FILTER (WHERE NOT a.is_correct) >= 2
               ORDER BY count(*) FILTER (WHERE NOT a.is_correct) DESC LIMIT $3""",
            member["id"], exam_id, params.limit)
        return json.dumps({
            "member": member["display_name"],
            "never_seen": [dict(r) for r in unseen],
            "missed_repeatedly": [dict(r) for r in missed],
        }, ensure_ascii=False)
    return await _logged("member_question_gaps", params.model_dump(), run())


class MostMissedInput(BaseModel):
    model_config = ConfigDict(str_strip_whitespace=True, extra="forbid")
    exam_code: str = Field(default="")
    domain: str = Field(default="")
    limit: int = Field(default=10, ge=1, le=50)


@mcp.tool(name="most_missed_questions", annotations={"title": "Most Missed Questions", "readOnlyHint": True, "openWorldHint": False})
async def most_missed_questions(params: MostMissedInput) -> str:
    """The questions the group keeps getting wrong (minimum 3 plays), with the correct answer
    and explanation — the pre-exam review sheet. Uses the same SQL as the drill screen."""
    async def run():
        exam_id = 0
        if params.exam_code:
            exam_id = await _exam_id(params.exam_code) or -1
            if exam_id == -1:
                return error_json(f"no exam matches '{params.exam_code}'")
        domain_id = 0
        if params.domain:
            row = await pools.read.fetchrow(
                "SELECT id FROM domains WHERE name ILIKE '%' || $1 || '%' AND ($2 = 0 OR exam_id = $2) LIMIT 1",
                params.domain, exam_id)
            if row is None:
                return error_json(f"no domain matches '{params.domain}'")
            domain_id = row["id"]
        sql, order = load_shared_sql("most_missed_questions")
        args = {"exam_id": exam_id, "domain_id": domain_id, "limit": params.limit, "offset": 0}
        rows = await pools.read.fetch(sql, *[args[p] for p in order])
        enriched = []
        for r in rows:
            detail = await pools.read.fetchrow(
                """SELECT q.explanation, o.option_text AS correct_answer
                   FROM questions q JOIN question_options o ON o.question_id = q.id AND o.is_correct
                   WHERE q.id = $1""", r["id"])
            enriched.append(dict(r) | (dict(detail) if detail else {}))
        return rows_to_json(enriched, note="Minimum 3 plays (matches the drill screen).")
    return await _logged("most_missed_questions", params.model_dump(), run())


class LeaderboardInput(BaseModel):
    model_config = ConfigDict(str_strip_whitespace=True, extra="forbid")
    exam_code: str = Field(default="")


@mcp.tool(name="alltime_leaderboard", annotations={"title": "All-time Leaderboard", "readOnlyHint": True, "openWorldHint": False})
async def alltime_leaderboard(params: LeaderboardInput) -> str:
    """Members ranked by total points across all games (claimed identities only), with games
    played and average final rank. Call this for bragging rights and participation checks."""
    async def run():
        exam_id = 0
        if params.exam_code:
            exam_id = await _exam_id(params.exam_code) or -1
            if exam_id == -1:
                return error_json(f"no exam matches '{params.exam_code}'")
        sql, order = load_shared_sql("alltime_leaderboard")
        args = {"exam_id": exam_id}
        rows = await pools.read.fetch(sql, *[args[p] for p in order])
        return rows_to_json(rows, note="Claimed identities only — anonymous nicknames don't count here.")
    return await _logged("alltime_leaderboard", params.model_dump(), run())


class QuestionStatsInput(BaseModel):
    model_config = ConfigDict(str_strip_whitespace=True, extra="forbid")
    question_id: int | None = Field(default=None, ge=1)
    search: str = Field(default="", description="Stem fragment when you don't have the id")


@mcp.tool(name="question_answer_stats", annotations={"title": "Question Answer Stats", "readOnlyHint": True, "openWorldHint": False})
async def question_answer_stats(params: QuestionStatsInput) -> str:
    """Per-option pick distribution and response-time stats for one question across every
    play — which wrong option lures people. Provide question_id or a search fragment."""
    async def run():
        qid = params.question_id
        if qid is None:
            if not params.search:
                return error_json("provide question_id or search")
            row = await pools.read.fetchrow(
                "SELECT id FROM questions WHERE stem ILIKE '%' || $1 || '%' ORDER BY id LIMIT 1", params.search)
            if row is None:
                return error_json(f"no question stem matches '{params.search}'")
            qid = row["id"]
        header = await pools.read.fetchrow(
            """SELECT q.id, q.stem, e.code AS exam, d.name AS domain
               FROM questions q JOIN exams e ON e.id = q.exam_id JOIN domains d ON d.id = q.domain_id
               WHERE q.id = $1""", qid)
        if header is None:
            return error_json(f"no question {qid}")
        options = await pools.read.fetch(
            """SELECT o.display_order, o.option_text, o.is_correct,
                      count(a.id) AS picks,
                      CASE WHEN count(a.id) = 0 THEN NULL ELSE round(avg(a.response_ms)) END AS avg_response_ms
               FROM question_options o LEFT JOIN answers a ON a.option_id = o.id
               WHERE o.question_id = $1 GROUP BY o.id ORDER BY o.display_order""", qid)
        return json.dumps({
            "question": dict(header),
            "options": [{k: (float(v) if k == "avg_response_ms" and v is not None else v)
                         for k, v in dict(r).items()} for r in options],
        }, ensure_ascii=False)
    return await _logged("question_answer_stats", params.model_dump(), run())


class RecordsSearchInput(BaseModel):
    model_config = ConfigDict(str_strip_whitespace=True, extra="forbid")
    sql: str = Field(..., min_length=10, description="A single SELECT statement")


@mcp.tool(name="records_search", annotations={"title": "Records Search (SQL)", "readOnlyHint": True, "openWorldHint": False})
async def records_search(params: RecordsSearchInput) -> str:
    """Long-tail escape hatch: run one read-only SELECT when no purposeful tool answers the
    question. Runs as a read-only role, 5s timeout, 200-row cap. Schema: exams(id, code, name,
    official_question_count, duration_minutes, passing_score, price_usd) · domains(id, exam_id,
    name, weight_pct, display_order) · scenarios(id, exam_id, title, body, status) · questions(id,
    exam_id, domain_id, scenario_id, stem, explanation, difficulty, status, source, created_by) ·
    question_options(id, question_id, option_text, is_correct, display_order) · games(id, exam_id,
    host_user_id, pin, state, question_count, seconds_per_question, streak_bonus, current_position,
    started_at, ended_at, created_at) · game_questions(id, game_id, question_id, position,
    points_possible, started_at, deadline) · game_players(id, game_id, user_id, nickname,
    joined_at, kicked_at, final_score, final_rank) · answers(id, game_player_id, game_question_id,
    option_id, is_correct, response_ms, points_awarded, streak_after, created_at) · users(id,
    email, display_name, role, created_at — other columns are not readable)."""
    async def run():
        problem = guard_search_sql(params.sql)
        if problem:
            return error_json(problem)
        try:
            rows = await pools.read.fetch(wrap_with_limit(params.sql))
        except Exception as exc:
            return error_json(f"query failed: {exc}")
        return rows_to_json(rows)
    return await _logged("records_search", {"sql": params.sql[:300]}, run())


app = mcp.streamable_http_app(stateless_http=True, json_response=True)
app.add_middleware(make_auth_middleware(pools, "records"))

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="127.0.0.1", port=int(os.environ.get("RECORDS_MCP_PORT", "8901")), log_level="warning")
