"""Claude Games unified assistant (chat-actions + AMA): one Claude Agent SDK service
behind both the command bar (/message) and the AMA page (/ask).

Reads go through the two client-facing MCP servers; actions go through an
IN-PROCESS actions MCP server whose tools call the application's own PHP
endpoints with a short-lived action token — same validation, same authorization,
same activity log as a human user. Localhost-only; PHP is the sole caller.
"""
from __future__ import annotations

import asyncio
import json
import os
import sys
from pathlib import Path
from typing import Any

sys.path.insert(0, str(Path(__file__).resolve().parent))

import asyncpg
import httpx
from fastapi import FastAPI
from pydantic import BaseModel

from claude_agent_sdk import (
    AssistantMessage, ClaudeAgentOptions, ClaudeSDKClient, TextBlock,
    create_sdk_mcp_server, tool,
)
from registry import SCREENS, resolve

APP_URL = os.environ.get("APP_INTERNAL_URL", "http://127.0.0.1:8080")
MODEL = os.environ.get("ASSISTANT_MODEL", "claude-sonnet-5")

# Per-turn state. The SDK runs tool handlers on tasks spawned from the client's
# own loop, so contextvars set per-request do NOT reach them — instead one turn
# runs at a time service-wide (global lock) and shares this module-level slot.
# Fine at study-group scale; revisit if turns ever need to overlap.
TURN: dict = {"action_token": "", "navigate": None, "trigger": None}
TURN_LOCK: "asyncio.Lock" = asyncio.Lock()

_resolver_pool: asyncpg.Pool | None = None


async def resolver_pool() -> asyncpg.Pool:
    """Read-only pool used ONLY to resolve names to ids (exam codes, domains, scenarios)."""
    global _resolver_pool
    if _resolver_pool is None:
        _resolver_pool = await asyncpg.create_pool(os.environ["RECORDS_MCP_DSN"], min_size=1, max_size=2)
    return _resolver_pool


def _result(payload: dict) -> dict:
    return {"content": [{"type": "text", "text": json.dumps(payload, ensure_ascii=False)}]}


async def _post_action(path: str, form: dict) -> dict:
    """POST to one of the app's own endpoints as the current user (action token)."""
    token = TURN["action_token"]
    if not token:
        return {"status": "error", "errors": ["no action token in this turn"]}
    try:
        async with httpx.AsyncClient(timeout=30) as http:
            response = await http.post(
                f"{APP_URL}{path}", data={k: v for k, v in form.items() if v is not None},
                headers={"X-Action-Token": token},
            )
    except httpx.HTTPError as exc:
        return {"status": "error", "errors": [f"could not reach the app at {path}: {exc}"]}
    try:
        return response.json()
    except Exception:
        return {"status": "error", "errors": [f"unexpected {response.status_code} response from {path}"]}


async def _exam_id(exam_code: str) -> int | None:
    pool = await resolver_pool()
    row = await pool.fetchrow("SELECT id FROM exams WHERE upper(code) = upper($1)", exam_code.strip())
    return None if row is None else row["id"]


TERMINAL = ("After success, confirm to the user in one short sentence and end the turn — "
            "no further tool calls for this command.")


@tool("navigate",
      "Go to a screen of the app for the user. Input: screen (a registry id) + optional params "
      "(id for record screens; exam_id/domain_id/status/q as query filters). Registry: "
      + "; ".join(f"{sid} — {s['desc']}" for sid, s in SCREENS.items()) + ". "
      "Compound utterances stay ONE call (put the pre-fill in params). At most one navigation "
      "per message; a later call replaces the earlier one. " + TERMINAL,
      {"screen": str, "params": dict})
async def navigate_tool(args: dict[str, Any]) -> dict:
    path, error = resolve(str(args.get("screen", "")), args.get("params") or {})
    if error:
        return _result({"status": "error", "error": error})
    TURN["navigate"] = {"path": path, "target": "#page-content"}
    return _result({"status": "success", "navigate": {"path": path, "target": "#page-content"}})


@tool("question_create",
      "Create a study question in the bank. options is the list of 2-4 answer texts; "
      "correct_index is 1-based into that list. difficulty easy|medium|hard (default medium); "
      "status draft|active (default draft). Resolves exam_code (CCAO-F etc.) and domain by name. "
      + TERMINAL,
      {"exam_code": str, "domain": str, "stem": str, "options": list, "correct_index": int,
       "explanation": str, "difficulty": str, "status": str})
async def question_create(args: dict[str, Any]) -> dict:
    exam_id = await _exam_id(str(args.get("exam_code", "")))
    if exam_id is None:
        return _result({"status": "error", "errors": ["unknown exam_code; use CCAO-F, CCDV-F, CCAR-F, or CCAR-P"]})
    pool = await resolver_pool()
    domain = await pool.fetchrow(
        "SELECT id FROM domains WHERE exam_id = $1 AND name ILIKE '%' || $2 || '%' LIMIT 1",
        exam_id, str(args.get("domain", "")))
    if domain is None:
        return _result({"status": "error", "errors": [f"no domain of that exam matches '{args.get('domain')}'"]})
    options = [str(o) for o in (args.get("options") or [])][:4]
    form = {"exam_id": exam_id, "domain_id": domain["id"], "stem": args.get("stem", ""),
            "explanation": args.get("explanation", ""),
            "difficulty": args.get("difficulty") or "medium",
            "status": args.get("status") or "draft",
            "correct": args.get("correct_index") or 1, "source": "assistant"}
    for index, text in enumerate(options, start=1):
        form[f"option_{index}"] = text
    outcome = await _post_action("/questions/save", form)
    if outcome.get("status") == "success":
        TURN["trigger"] = "questionsChanged"
    return _result(outcome)


@tool("question_set_status",
      "Activate, retire, or re-draft a question by id. status: active|retired|draft. " + TERMINAL,
      {"question_id": int, "status": str})
async def question_set_status(args: dict[str, Any]) -> dict:
    outcome = await _post_action("/questions/status", {"id": args.get("question_id"), "status": args.get("status")})
    if outcome.get("status") == "success":
        TURN["trigger"] = "questionsChanged"
    return _result(outcome)


@tool("question_delete",
      "Delete a DRAFT question that was never played (played questions can only be retired). "
      "Destructive — only call after the user has explicitly confirmed in this conversation. " + TERMINAL,
      {"question_id": int})
async def question_delete(args: dict[str, Any]) -> dict:
    outcome = await _post_action("/questions/delete", {"id": args.get("question_id")})
    if outcome.get("status") == "success":
        TURN["trigger"] = "questionsChanged"
    return _result(outcome)


@tool("question_import_batch",
      "Bulk-import questions from a JSON array (same schema as the import screen: exam_code, "
      "domain, stem, options[{text,correct}], explanation, difficulty?). All-or-nothing; lands as drafts. " + TERMINAL,
      {"json": str})
async def question_import_batch(args: dict[str, Any]) -> dict:
    outcome = await _post_action("/questions/import", {"json": args.get("json", ""), "do": "import"})
    if isinstance(outcome, dict) and outcome.get("status") == "success":
        TURN["trigger"] = "questionsChanged"
    return _result(outcome if isinstance(outcome, dict) else {"status": "error", "errors": ["import failed"]})


@tool("scenario_create",
      "Create a scenario (CCAR-F-style reading passage that precedes linked questions). "
      "body is the passage (50-4000 chars); status draft|active (default draft). " + TERMINAL,
      {"exam_code": str, "title": str, "body": str, "status": str})
async def scenario_create(args: dict[str, Any]) -> dict:
    exam_id = await _exam_id(str(args.get("exam_code", "")))
    if exam_id is None:
        return _result({"status": "error", "errors": ["unknown exam_code"]})
    outcome = await _post_action("/scenarios/save", {
        "exam_id": exam_id, "title": args.get("title", ""), "body": args.get("body", ""),
        "status": args.get("status") or "draft"})
    if outcome.get("status") == "success":
        TURN["trigger"] = "scenariosChanged"
    return _result(outcome)


@tool("scenario_set_status", "Activate, retire, or re-draft a scenario by id. " + TERMINAL,
      {"scenario_id": int, "status": str})
async def scenario_set_status(args: dict[str, Any]) -> dict:
    outcome = await _post_action("/scenarios/status", {"id": args.get("scenario_id"), "status": args.get("status")})
    if outcome.get("status") == "success":
        TURN["trigger"] = "scenariosChanged"
    return _result(outcome)


@tool("scenario_delete",
      "Delete a DRAFT scenario with no linked questions. Destructive — only after explicit "
      "user confirmation in this conversation. " + TERMINAL, {"scenario_id": int})
async def scenario_delete(args: dict[str, Any]) -> dict:
    outcome = await _post_action("/scenarios/delete", {"id": args.get("scenario_id")})
    if outcome.get("status") == "success":
        TURN["trigger"] = "scenariosChanged"
    return _result(outcome)


@tool("game_create",
      "Start a new game night: creates the game and returns the join PIN and host-console URL. "
      "question_count 5-30 (default 10), seconds_per_question 10|20|30|60 (default 20), "
      "streak_bonus default true. The reply MUST include the PIN. " + TERMINAL,
      {"exam_code": str, "question_count": int, "seconds_per_question": int, "streak_bonus": bool})
async def game_create(args: dict[str, Any]) -> dict:
    exam_id = await _exam_id(str(args.get("exam_code", "")))
    if exam_id is None:
        return _result({"status": "error", "errors": ["unknown exam_code"]})
    form = {"exam_id": exam_id,
            "question_count": args.get("question_count") or 10,
            "seconds_per_question": args.get("seconds_per_question") or 20}
    if args.get("streak_bonus", True):
        form["streak_bonus"] = "on"
    outcome = await _post_action("/games/save", form)
    if outcome.get("status") == "success":
        TURN["trigger"] = "gamesChanged"
        TURN["navigate"] = {"path": outcome.get("url", "/games/"), "target": "#page-content"}
    return _result(outcome)


@tool("game_advance",
      "Advance a live game's state machine as the host: expected_state is the CURRENT state "
      "(lobby=start the game, reveal=show leaderboard, leaderboard=next question or podium, "
      "podium=finish, scenario_intro=start the question after the scenario reading). " + TERMINAL,
      {"game_id": int, "expected_state": str})
async def game_advance(args: dict[str, Any]) -> dict:
    outcome = await _post_action(f"/games/{int(args.get('game_id', 0))}/advance",
                                 {"expected_state": args.get("expected_state", "")})
    return _result(outcome)


@tool("game_kick_player",
      "Remove a player from a live game (host only). Needs the numeric player id — "
      "find it via the records server's game tools if you only have a nickname. " + TERMINAL,
      {"game_id": int, "player_id": int})
async def game_kick_player(args: dict[str, Any]) -> dict:
    outcome = await _post_action(f"/games/{int(args.get('game_id', 0))}/kick",
                                 {"player_id": args.get("player_id")})
    return _result(outcome)


@tool("game_abort",
      "Abort a live game (host only). Destructive — only after explicit user confirmation "
      "in this conversation. " + TERMINAL, {"game_id": int})
async def game_abort(args: dict[str, Any]) -> dict:
    outcome = await _post_action(f"/games/{int(args.get('game_id', 0))}/abort", {})
    if outcome.get("status") == "success":
        TURN["trigger"] = "gamesChanged"
    return _result(outcome)


@tool("mcp_token_create",
      "Create an MCP bearer token for connecting external AI tools. scope: both|records|activity. "
      "The reply MUST include the one-time plaintext token so the user can copy it. " + TERMINAL,
      {"label": str, "scope": str})
async def mcp_token_create(args: dict[str, Any]) -> dict:
    outcome = await _post_action("/settings/mcp/save",
                                 {"label": args.get("label", ""), "server": args.get("scope") or "both"})
    if outcome.get("status") == "success":
        TURN["trigger"] = "mcpTokensChanged"
    return _result(outcome)


@tool("mcp_token_revoke",
      "Revoke an MCP bearer token by id. Destructive — only after explicit user confirmation. " + TERMINAL,
      {"token_id": int})
async def mcp_token_revoke(args: dict[str, Any]) -> dict:
    outcome = await _post_action("/settings/mcp/revoke", {"id": args.get("token_id")})
    if outcome.get("status") == "success":
        TURN["trigger"] = "mcpTokensChanged"
    return _result(outcome)


@tool("undo_last",
      "Undo the user's most recent undoable action (creates, edits, status changes, game "
      "creation, token creation — within the last hour). Call when the user says 'undo that' "
      "or similar. " + TERMINAL, {})
async def undo_last(args: dict[str, Any]) -> dict:
    outcome = await _post_action("/assistant/undo", {})
    TURN["trigger"] = "recordsChanged"
    return _result(outcome)


actions_server = create_sdk_mcp_server(
    name="actions", version="1.0.0",
    tools=[navigate_tool, question_create, question_set_status, question_delete,
           question_import_batch, scenario_create, scenario_set_status, scenario_delete,
           game_create, game_advance, game_kick_player, game_abort,
           mcp_token_create, mcp_token_revoke, undo_last],
)


SYSTEM_PROMPT = """You are the Claude Games assistant. Claude Games is a Kahoot-style study app a
Claude Partner Network group uses to prepare for the four Anthropic certification exams
(120 min, pass 720 on a 100-1000 scale): CCAO-F 'Claude Certified Associate, Foundations'
(say 'associate'), CCDV-F 'Claude Certified Developer, Foundations' ('developer'), CCAR-F
'Claude Certified Architect, Foundations' ('architect'), CCAR-P 'Claude Certified Architect,
Professional' ('architect professional'). Resolve those spoken names to codes yourself —
never ask which exam when the utterance names one of them.

Surfaces: 'command_bar' turns are voice-first commands dictated from any screen — parse
dictation tolerantly (spelled-out numbers, units, filler words, no punctuation), act, and
reply in ONE short sentence that echoes your interpretation. 'ama' turns are the Ask-Me-
Anything page — answer conversationally, still concise, and say which memory the answer
came from (records vs activity).

Tools: the records server answers questions about the current truth (questions, games,
readiness, leaderboards); the activity server answers what happened and when; the actions
server navigates and performs actions AS the user through the app's own endpoints. Never
claim to have done something without a successful tool result. Creates and updates execute
immediately and your confirmation is the readback; DESTRUCTIVE actions (delete, abort,
revoke) need the user's explicit yes first — ask in one short question if you don't have it.
If an utterance is ambiguous, ask one short question instead of guessing. When a screen
context is provided ('that question', 'this scenario'), resolve pronouns against it.

Member readiness: weighted score = sum(domain accuracy x blueprint weight) scaled
100-1000; ready means sustained >=720 with >=60% blueprint coverage — an indicator, not a
guarantee. Never present it as a Pearson VUE prediction."""


def build_options(user: dict) -> ClaudeAgentOptions:
    token = os.environ["ASSISTANT_MCP_TOKEN"]
    return ClaudeAgentOptions(
        model=MODEL,
        system_prompt=SYSTEM_PROMPT + f"\n\nCurrent user: {user['display_name']} "
                      f"(user id {user['user_id']}, role {user['role']}).",
        mcp_servers={
            "records":  {"type": "http", "url": os.environ["RECORDS_MCP_URL"],
                         "headers": {"Authorization": f"Bearer {token}"}},
            "activity": {"type": "http", "url": os.environ["ACTIVITY_MCP_URL"],
                         "headers": {"Authorization": f"Bearer {token}"}},
            "actions":  actions_server,
        },
        disallowed_tools=["Bash", "Read", "Write", "Edit", "Glob", "Grep",
                          "WebFetch", "WebSearch", "Task", "NotebookEdit"],
        permission_mode="bypassPermissions",
        max_turns=12,
        setting_sources=[],
    )


class Turn(BaseModel):
    surface: str = "command_bar"
    user_id: int
    display_name: str = ""
    role: str = "member"
    message: str
    screen: str = ""
    entity: str = ""
    record_id: int | None = None
    action_token: str = ""


app = FastAPI()
_clients: dict[int, ClaudeSDKClient] = {}


async def run_turn(turn: Turn) -> dict:
    async with TURN_LOCK:
        TURN["action_token"] = turn.action_token
        TURN["navigate"] = None
        TURN["trigger"] = None
        client = _clients.get(turn.user_id)
        if client is None:
            client = ClaudeSDKClient(build_options({
                "user_id": turn.user_id, "display_name": turn.display_name, "role": turn.role}))
            await client.connect()
            _clients[turn.user_id] = client

        context = ""
        if turn.screen:
            context = f"[surface: {turn.surface}; current screen: {turn.screen}"
            if turn.entity and turn.record_id:
                context += f"; viewing {turn.entity} #{turn.record_id}"
            context += "]\n"
        elif turn.surface:
            context = f"[surface: {turn.surface}]\n"

        await client.query(context + turn.message)
        reply_parts: list[str] = []
        async for message in client.receive_response():
            if isinstance(message, AssistantMessage):
                for block in message.content:
                    if isinstance(block, TextBlock):
                        reply_parts.append(block.text)

        outcome = {
            "reply": (reply_parts[-1].strip() if reply_parts else "Done."),
            "navigate": TURN["navigate"],
            "trigger": TURN["trigger"],
        }
        TURN["action_token"] = ""
        return outcome


@app.post("/message")
async def message(turn: Turn) -> dict:
    return await run_turn(turn)


@app.post("/ask")
async def ask(turn: Turn) -> dict:
    turn.surface = "ama"
    return await run_turn(turn)


@app.get("/health")
async def health() -> dict:
    return {"ok": True, "model": MODEL, "sessions": len(_clients)}


if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="127.0.0.1", port=int(os.environ.get("ASSISTANT_PORT", "8765")), log_level="warning")
