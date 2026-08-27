<?php
// Copy to config/env.php (gitignored) and fill in. Production uses real env vars.
putenv('APP_ENV=dev');                 // dev | production
putenv('APP_NAME=Claude Games');
putenv('APP_TZ=America/Chicago');           // PHP display timezone (storage stays UTC)
putenv('APP_URL=http://localhost:8080');
putenv('DB_HOST=127.0.0.1');
putenv('DB_PORT=5432');
putenv('DB_NAME=studygame');
putenv('DB_USER=');                    // dev default: current OS user
putenv('DB_PASSWORD=');
putenv('TOTP_ENC_KEY=');               // 64 hex chars: php -r "echo bin2hex(random_bytes(32));"
putenv('MALUMAIL_API_KEY=');           // empty in dev -> emails land in storage/mail.log
putenv('MAIL_FROM=noreply@example.com');
putenv('GOOGLE_CLIENT_ID=');           // empty -> Google button hidden
putenv('GOOGLE_CLIENT_SECRET=');

// ---- Phase 4 (MCP servers + assistant) ----
putenv('ACTION_TOKEN_SECRET=');        // 64 hex chars: php -r "echo bin2hex(random_bytes(32));"
putenv('ASSISTANT_URL=http://127.0.0.1:8765');
putenv('RECORDS_MCP_URL=http://127.0.0.1:8901/mcp');    // internal (assistant-facing)
putenv('ACTIVITY_MCP_URL=http://127.0.0.1:8902/mcp');
putenv('PUBLIC_RECORDS_MCP_URL=');     // shown on /settings/mcp; default APP_URL . '/mcp/records'
putenv('PUBLIC_ACTIVITY_MCP_URL=');    // default APP_URL . '/mcp/activity'
