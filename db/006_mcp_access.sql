-- 006_mcp_access: read-only roles for the two MCP servers + bearer-token registry.
-- Run with psql variables: psql -v records_ro_password='…' -v activity_ro_password='…'
-- The MCP server processes hold ONLY these credentials — never write access.

create role studygame_records_ro login password :'records_ro_password';
alter role studygame_records_ro set default_transaction_read_only = on;
alter role studygame_records_ro set statement_timeout = '5s';
grant connect on database studygame to studygame_records_ro;
grant usage on schema public to studygame_records_ro;
grant select on users, exams, domains, scenarios, questions, question_options,
                games, game_questions, game_players, answers
    to studygame_records_ro;
-- Deliberately NOT granted: password/secret-bearing columns are still exposed via
-- table grant on users — records tools must select explicit column lists, and the
-- records_search guard rejects queries touching users' credential columns.
revoke select on users from studygame_records_ro;
grant select (id, email, display_name, role, created_at) on users to studygame_records_ro;

create role studygame_activity_ro login password :'activity_ro_password';
alter role studygame_activity_ro set default_transaction_read_only = on;
alter role studygame_activity_ro set statement_timeout = '5s';
grant connect on database studygame to studygame_activity_ro;
grant usage on schema public to studygame_activity_ro;
grant select on activity_log to studygame_activity_ro;
grant select (id, display_name) on users to studygame_activity_ro;
-- At deploy, the activity server points at MaluDB's query surface instead of the raw
-- table where the MaluDB installation provides one; the raw activity_log grant keeps
-- the tools working before/without that.

-- Per-client bearer tokens for the client-facing MCP endpoints (SaaS Plus+).
-- Managed on the settings-mcp screen; checked (hashed) by the MCP services.
create table mcp_tokens (
    id           bigint generated always as identity primary key,
    label        text not null,                    -- 'Ed - Claude Desktop'
    server       text not null default 'both' check (server in ('records','activity','both')),
    token_hash   text not null,                    -- hash of the random token; plaintext shown once
    created_by   bigint references users(id),
    created_at   timestamptz not null default now(),
    revoked_at   timestamptz,
    last_used_at timestamptz
);
