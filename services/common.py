"""Shared plumbing for the Claude Games MCP services.

The READ pool runs on a strictly read-only role (006); the AUTH pool runs on the
narrow studygame_mcp_auth role (008) and is used only for bearer-token checks and
activity logging. No service process ever holds write credentials to record data.
"""
from __future__ import annotations

import hashlib
import json
import os
import re
import time
from datetime import date, datetime
from decimal import Decimal
from pathlib import Path

import asyncpg
from starlette.middleware.base import BaseHTTPMiddleware
from starlette.requests import Request
from starlette.responses import JSONResponse

APP_ROOT = Path(__file__).resolve().parent.parent
SQL_DIR = APP_ROOT / "app" / "features" / "analytics" / "sql"

_PARAM_RE = re.compile(r"(?<!:):([a-zA-Z_][a-zA-Z0-9_]*)")


def load_shared_sql(name: str) -> tuple[str, list[str]]:
    """Load one of the shared analytics SQL files (single source of truth with PHP)
    and convert PDO-style :name placeholders to asyncpg $N. Returns (sql, param order)."""
    text = (SQL_DIR / f"{name}.sql").read_text()
    order: list[str] = []

    def repl(match: re.Match[str]) -> str:
        param = match.group(1)
        if param not in order:
            order.append(param)
        return f"${order.index(param) + 1}"

    return _PARAM_RE.sub(repl, text), order


def _jsonable(value):
    if isinstance(value, (datetime, date)):
        return value.isoformat()
    if isinstance(value, Decimal):
        return float(value)
    return value


def rows_to_json(rows, note: str | None = None) -> str:
    data = [{k: _jsonable(v) for k, v in dict(r).items()} for r in rows]
    payload: dict = {"rows": data, "count": len(data)}
    if note:
        payload["note"] = note
    return json.dumps(payload, ensure_ascii=False)


def error_json(message: str) -> str:
    return json.dumps({"error": message})


class Pools:
    """Lazily-created pools: read (read-only role) + auth (token check / logging)."""

    def __init__(self, read_dsn_env: str):
        self._read_dsn = os.environ[read_dsn_env]
        self._auth_dsn = os.environ["MCP_AUTH_DSN"]
        self.read: asyncpg.Pool | None = None
        self.auth: asyncpg.Pool | None = None

    async def ensure(self) -> None:
        if self.read is None:
            self.read = await asyncpg.create_pool(self._read_dsn, min_size=1, max_size=5)
        if self.auth is None:
            self.auth = await asyncpg.create_pool(self._auth_dsn, min_size=1, max_size=2)


def make_auth_middleware(pools: Pools, server_name: str, mcp_path: str = "/mcp"):
    """Bearer-token middleware for the client-facing servers. Tokens live hashed in
    mcp_tokens (managed on the settings-mcp screen); a token scoped 'records' or
    'activity' only opens its own server, 'both' opens either."""

    class BearerAuthMiddleware(BaseHTTPMiddleware):
        async def dispatch(self, request: Request, call_next):
            if not request.url.path.startswith(mcp_path):
                return await call_next(request)
            header = request.headers.get("authorization", "")
            if not header.lower().startswith("bearer ") or len(header) < 20:
                return JSONResponse({"error": "missing bearer token"}, status_code=401)
            token_hash = hashlib.sha256(header[7:].strip().encode()).hexdigest()
            await pools.ensure()
            row = await pools.auth.fetchrow(
                """SELECT id, label FROM mcp_tokens
                   WHERE token_hash = $1 AND revoked_at IS NULL
                     AND server IN ('both', $2)""",
                token_hash, server_name,
            )
            if row is None:
                return JSONResponse({"error": "invalid or revoked token"}, status_code=401)
            request.state.mcp_token_label = row["label"]
            await pools.auth.execute(
                "UPDATE mcp_tokens SET last_used_at = now() WHERE id = $1", row["id"]
            )
            return await call_next(request)

    return BearerAuthMiddleware


async def log_tool_call(pools: Pools, server: str, tool: str, arguments: dict,
                        duration_ms: int, caller: str = "") -> None:
    """Every MCP tool call is activity memory."""
    try:
        await pools.ensure()
        await pools.auth.execute(
            """INSERT INTO activity_log (actor_type, actor_label, action, screen, entity, details)
               VALUES ('agent', $1, 'mcp_tool_called', $2, 'mcp', $3::jsonb)""",
            caller or server, server,
            json.dumps({"tool": tool, "arguments": arguments, "duration_ms": duration_ms}),
        )
    except Exception:
        pass  # logging must never break a read


class ToolTimer:
    def __enter__(self):
        self._start = time.monotonic()
        return self

    def __exit__(self, *exc):
        self.duration_ms = int((time.monotonic() - self._start) * 1000)
        return False


_FORBIDDEN_SEARCH = re.compile(
    r"\b(insert|update|delete|merge|alter|drop|create|grant|revoke|truncate|copy|vacuum|"
    r"password_hash|totp_secret|totp_recovery_codes|token_hash|player_token)\b",
    re.IGNORECASE,
)


def guard_search_sql(sql: str) -> str | None:
    """Validate the long-tail search SQL: one SELECT/WITH statement, no writes, no
    credential columns. Returns an error message or None when acceptable. The DB
    role is read-only with a 5s statement_timeout regardless — this is defense in depth."""
    stripped = sql.strip().rstrip(";").strip()
    if ";" in stripped:
        return "one statement only — remove inner semicolons"
    if not re.match(r"^(select|with)\b", stripped, re.IGNORECASE):
        return "only SELECT (or WITH … SELECT) statements are accepted"
    match = _FORBIDDEN_SEARCH.search(stripped)
    if match:
        return f"'{match.group(0)}' is not allowed in search queries"
    return None


def wrap_with_limit(sql: str, limit: int = 200) -> str:
    stripped = sql.strip().rstrip(";")
    return f"SELECT * FROM (\n{stripped}\n) AS guarded_search LIMIT {limit}"
