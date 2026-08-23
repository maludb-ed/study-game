-- 008: narrow service role for the MCP servers' cross-cutting needs.
-- The READ pools stay on the strictly read-only roles from 006; this role exists
-- only to (a) verify bearer tokens + stamp last_used_at, and (b) write the
-- tool-call rows to the activity log ("MCP usage is itself activity memory").
-- Run: psql -v mcp_auth_password='…' -f db/008_mcp_service_grants.sql

create role studygame_mcp_auth login password :'mcp_auth_password';
grant connect on database studygame to studygame_mcp_auth;
grant usage on schema public to studygame_mcp_auth;
grant select (id, label, server, token_hash, revoked_at) on mcp_tokens to studygame_mcp_auth;
grant update (last_used_at) on mcp_tokens to studygame_mcp_auth;
grant select (id) on mcp_tokens to studygame_mcp_auth;   -- for the UPDATE's WHERE
grant insert on activity_log to studygame_mcp_auth;
grant usage on all sequences in schema public to studygame_mcp_auth;

-- Activity questions reference games by exam and members by email; give the
-- activity read role the minimal extra metadata it needs.
grant select (id, exam_id, state, created_at, ended_at, host_user_id) on games to studygame_activity_ro;
grant select (id, code, name) on exams to studygame_activity_ro;
revoke select on users from studygame_activity_ro;
grant select (id, email, display_name) on users to studygame_activity_ro;
