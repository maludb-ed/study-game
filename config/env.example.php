<?php
// Copy to config/env.php (gitignored) and fill in. Production uses real env vars.
putenv('APP_ENV=dev');                 // dev | production
putenv('APP_NAME=Cert Arena');
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
