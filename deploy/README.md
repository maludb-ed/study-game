# Cert Arena deployment (Ubuntu 24.04 + Apache + PostgreSQL 17)

1. App root at /var/www/studygame; PHP-FPM + Apache vhost serving public/ through
   public/router.php; TLS via certbot.
2. Apply db/*.sql in order (001..008; 006/008 take role passwords via -v vars).
   Load db/090 seed if starting fresh. MaluDB: install the extensions and point the
   activity server's DSN at its query surface (the raw activity_log grant works until then).
3. Python: python3 -m venv services/.venv && services/.venv/bin/pip install "mcp[cli]" asyncpg fastapi uvicorn httpx claude-agent-sdk
4. config/services.env: the three DSNs (read roles + mcp_auth), RECORDS/ACTIVITY_MCP_URL
   (localhost ports), APP_INTERNAL_URL, ASSISTANT_MCP_TOKEN (mint on settings-mcp),
   ACTION_TOKEN_SECRET (openssl rand -hex 32), ANTHROPIC_API_KEY, ASSISTANT_MODEL.
   config/env.php mirrors ACTION_TOKEN_SECRET + ASSISTANT_URL + PUBLIC_*_MCP_URL for PHP.
5. systemctl enable --now the three units in deploy/*.service; include deploy/apache-mcp.conf
   in the vhost; a2enmod proxy proxy_http.
6. Verify: /settings/mcp shows the public URLs; connect Claude Desktop with a token;
   the command bar answers "who won our last game?".
