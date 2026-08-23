"""Cert Arena activity-memory MCP server (client-facing, read-only).

Answers 'what happened, who, when' from the activity log through the
studygame_activity_ro role. In production this points at MaluDB's query surface;
the raw activity_log grant keeps the tools working before/without it.
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
    Pools, ToolTimer, error_json, guard_search_sql,
    log_tool_call, make_auth_middleware, rows_to_json, wrap_with_limit,
)

SERVER = "studygame_activity_mcp"
mcp = MCPServer(SERVER)
pools = Pools("ACTIVITY_MCP_DSN")


async def _member(member: str) -> dict | None:
    row = await pools.read.fetchrow(
        """SELECT id, display_name FROM users
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


class PlayCadenceInput(BaseModel):
    model_config = ConfigDict(str_strip_whitespace=True, extra="forbid")
    exam_code: str = Field(default="", description="CCAO-F, CCDV-F, CCAR-F, or CCAR-P")
    since_date: str = Field(default="", description="ISO date; only count games on/after it")


@mcp.tool(name="play_cadence", annotations={"title": "Play Cadence", "readOnlyHint": True, "openWorldHint": False})
async def play_cadence(params: PlayCadenceInput) -> str:
    """How often the group actually plays: game events over time per exam — dates, gaps,
    weekly frequency. Call this for 'are we keeping up the study cadence' questions."""
    async def run():
        rows = await pools.read.fetch(
            """SELECT date_trunc('week', g.created_at)::date AS week, e.code AS exam,
                      count(*) AS games,
                      count(*) FILTER (WHERE g.state = 'ended') AS finished
               FROM games g JOIN exams e ON e.id = g.exam_id
               WHERE ($1 = '' OR upper(e.code) = upper($1))
                 AND ($2 = '' OR g.created_at >= $2::timestamptz)
               GROUP BY week, e.code ORDER BY week DESC, e.code""",
            params.exam_code, params.since_date,
        )
        return rows_to_json(rows)
    return await _logged("play_cadence", params.model_dump(), run())


class AttendanceInput(BaseModel):
    model_config = ConfigDict(str_strip_whitespace=True, extra="forbid")
    date: str = Field(..., pattern=r"^\d{4}-\d{2}-\d{2}$", description="ISO date of the game night, e.g. 2026-08-23")


@mcp.tool(name="game_night_attendance", annotations={"title": "Game Night Attendance", "readOnlyHint": True, "openWorldHint": False})
async def game_night_attendance(params: AttendanceInput) -> str:
    """Who showed up on a given date: hosts, joiners (with claimed identity when known),
    join times, and kicks, reconstructed from the activity log."""
    async def run():
        from datetime import date as date_type
        try:
            night = date_type.fromisoformat(params.date)
        except ValueError:
            return error_json(f"'{params.date}' is not an ISO date (YYYY-MM-DD)")
        rows = await pools.read.fetch(
            """SELECT l.occurred_at, l.action, l.actor_type, l.actor_label, l.game_id,
                      u.display_name AS claimed_member
               FROM activity_log l
               LEFT JOIN users u ON u.id = l.actor_id AND l.actor_type = 'user'
               WHERE l.occurred_at::date = $1
                 AND l.action IN ('game_created', 'game_started', 'player_joined',
                                  'identity_claimed', 'player_kicked', 'game_finished', 'game_aborted')
               ORDER BY l.occurred_at""",
            night,
        )
        return rows_to_json(rows, note="Reconstructed from the activity log; player rows show nicknames.")
    return await _logged("game_night_attendance", params.model_dump(), run())


class TimelineInput(BaseModel):
    model_config = ConfigDict(str_strip_whitespace=True, extra="forbid")
    member: str = Field(..., min_length=2, description="Member display name or email")
    since_date: str = Field(default="")
    limit: int = Field(default=50, ge=1, le=200)


@mcp.tool(name="member_activity_timeline", annotations={"title": "Member Activity Timeline", "readOnlyHint": True, "openWorldHint": False})
async def member_activity_timeline(params: TimelineInput) -> str:
    """One member's activity stream — screens visited, records changed, games hosted and
    played — newest first. Call this for 'when did X last…' and engagement questions."""
    async def run():
        member = await _member(params.member)
        if member is None:
            return error_json(f"no member matches '{params.member}'")
        rows = await pools.read.fetch(
            """SELECT occurred_at, action, screen, entity, entity_id, game_id, details
               FROM activity_log
               WHERE actor_type = 'user' AND actor_id = $1
                 AND ($2 = '' OR occurred_at >= $2::timestamptz)
               ORDER BY occurred_at DESC LIMIT $3""",
            member["id"], params.since_date, params.limit,
        )
        return rows_to_json(rows, note=f"Timeline for {member['display_name']}.")
    return await _logged("member_activity_timeline", params.model_dump(), run())


class RecordHistoryInput(BaseModel):
    model_config = ConfigDict(str_strip_whitespace=True, extra="forbid")
    entity: str = Field(..., min_length=2, description="questions, scenarios, games, answers, users, mcp")
    entity_id: int = Field(..., ge=1)


@mcp.tool(name="record_history", annotations={"title": "Record History", "readOnlyHint": True, "openWorldHint": False})
async def record_history(params: RecordHistoryInput) -> str:
    """Who created and edited one record and when, with before/after values from the log.
    Call this for 'who changed this question' style provenance questions."""
    async def run():
        rows = await pools.read.fetch(
            """SELECT l.occurred_at, l.action, l.actor_type, l.actor_label,
                      u.display_name AS member, l.before_data, l.after_data, l.details
               FROM activity_log l
               LEFT JOIN users u ON u.id = l.actor_id AND l.actor_type = 'user'
               WHERE l.entity = $1 AND l.entity_id = $2
               ORDER BY l.occurred_at""",
            params.entity.strip().lower(), params.entity_id,
        )
        if not rows:
            return error_json(f"no history for {params.entity} #{params.entity_id}")
        return rows_to_json(rows)
    return await _logged("record_history", params.model_dump(), run())


class GameTimelineInput(BaseModel):
    model_config = ConfigDict(extra="forbid")
    game_id: int = Field(..., ge=1)


@mcp.tool(name="game_timeline", annotations={"title": "Game Timeline", "readOnlyHint": True, "openWorldHint": False})
async def game_timeline(params: GameTimelineInput) -> str:
    """The full event sequence of one game — creation, joins, question starts, scenario
    intros, answers, reveals, kicks, finish — in order. The forensic view of a game night."""
    async def run():
        rows = await pools.read.fetch(
            """SELECT occurred_at, action, actor_type, actor_label, entity, entity_id, details
               FROM activity_log WHERE game_id = $1 ORDER BY occurred_at, id""",
            params.game_id,
        )
        if not rows:
            return error_json(f"no events for game {params.game_id}")
        return rows_to_json(rows)
    return await _logged("game_timeline", params.model_dump(), run())


class ActivitySearchInput(BaseModel):
    model_config = ConfigDict(str_strip_whitespace=True, extra="forbid")
    sql: str = Field(..., min_length=10, description="A single SELECT statement")


@mcp.tool(name="activity_search", annotations={"title": "Activity Search (SQL)", "readOnlyHint": True, "openWorldHint": False})
async def activity_search(params: ActivitySearchInput) -> str:
    """Long-tail escape hatch over the activity log: one read-only SELECT, 5s timeout,
    200-row cap. Schema: activity_log(id, occurred_at, actor_type[user|player|agent|system],
    actor_id, actor_label, action, screen, entity, entity_id, game_id, before_data jsonb,
    after_data jsonb, details jsonb, ip) · users(id, email, display_name) ·
    games(id, exam_id, state, created_at, ended_at, host_user_id) · exams(id, code, name).
    Common actions: screen_view, question_created/updated, game_created/started/finished,
    player_joined, answer_submitted, question_shown/revealed, scenario_shown, mcp_tool_called."""
    async def run():
        problem = guard_search_sql(params.sql)
        if problem:
            return error_json(problem)
        try:
            rows = await pools.read.fetch(wrap_with_limit(params.sql))
        except Exception as exc:
            return error_json(f"query failed: {exc}")
        return rows_to_json(rows)
    return await _logged("activity_search", {"sql": params.sql[:300]}, run())


app = mcp.streamable_http_app(stateless_http=True, json_response=True)
app.add_middleware(make_auth_middleware(pools, "activity"))

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="127.0.0.1", port=int(os.environ.get("ACTIVITY_MCP_PORT", "8902")), log_level="warning")
