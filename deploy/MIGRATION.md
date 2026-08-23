# Migrating Cert Arena to the Ubuntu development server

From a clone of `github.com/maludb-ed/study-game` to a running app with all three
Python services. Written for Ubuntu 24.04. Commands assume a sudo-capable user and
the app living at `/var/www/studygame` (adjust freely for a home-dir dev checkout).

The git repo carries everything EXCEPT: `config/env.php`, `services/.env.dev`,
`services/.venv/`, and `services/.dev-token` (all gitignored). Those are recreated
below — never copy the Mac's dev secrets to the server.

## 1. Packages

```bash
sudo apt update
sudo apt install -y git postgresql-17 php8.3-cli php8.3-fpm php8.3-pgsql php8.3-mbstring \
                    python3-venv apache2
```

- `php8.3-pgsql` provides pdo_pgsql (the only PHP extension the app needs beyond mbstring).
- Apache is only needed for the full setup in step 8; the quick run in step 7 works without it.
- The assistant needs Claude API access: either `ANTHROPIC_API_KEY` in the services env
  (recommended for servers) or an authenticated `claude` CLI for the service user.

## 2. Clone

```bash
sudo mkdir -p /var/www/studygame && sudo chown $USER /var/www/studygame
git clone git@github.com:maludb-ed/study-game.git /var/www/studygame
cd /var/www/studygame
```

(The repo is private — add the server's SSH key as a deploy key on GitHub, or use a
fine-grained PAT over HTTPS.)

## 3. Database

Generate the four role passwords first and keep them for steps 4–5:

```bash
for r in app records activity mcpauth; do echo "$r: $(openssl rand -hex 24)"; done
```

```bash
sudo -u postgres createuser --pwprompt studygame_app        # the PHP app's role
sudo -u postgres createdb -O studygame_app studygame
cd /var/www/studygame
export PGPASSWORD='<app password>'
psql -h 127.0.0.1 -U studygame_app -d studygame -v ON_ERROR_STOP=1 \
     -f db/000_base.sql -f db/001_auth.sql -f db/002_exams.sql -f db/003_questions.sql \
     -f db/004_games.sql -f db/005_activity_log.sql
sudo -u postgres psql -d studygame -v ON_ERROR_STOP=1 \
     -v records_ro_password='<records password>' -v activity_ro_password='<activity password>' \
     -f db/006_mcp_access.sql
psql -h 127.0.0.1 -U studygame_app -d studygame -v ON_ERROR_STOP=1 -f db/007_scenario_intro.sql
sudo -u postgres psql -d studygame -v ON_ERROR_STOP=1 \
     -v mcp_auth_password='<mcpauth password>' -f db/008_mcp_service_grants.sql
```

(006 and 008 create roles, so they run as postgres; grants they issue assume the tables
already exist, hence the order.)

Then EITHER seed fresh content:

```bash
psql -h 127.0.0.1 -U studygame_app -d studygame -v ON_ERROR_STOP=1 -f db/090_seed_ccao_questions.sql
```

OR carry the Mac's current data (questions, games, analytics history) instead of 000–090:
on the Mac `pg_dump -Fc -d studygame > certarena.dump`, copy it over, then
`sudo -u postgres pg_restore --no-owner --role=studygame_app -d studygame certarena.dump`
followed by ONLY db/006 and db/008 (roles/grants are not in the dump).

MaluDB: when its extensions are installed on this server, point the activity server's DSN
at the MaluDB query surface; until then the raw `activity_log` grant keeps every tool working.

## 4. PHP config

```bash
cp config/env.example.php config/env.php
php -r "echo 'TOTP_ENC_KEY=' . bin2hex(random_bytes(32)) . PHP_EOL, 'ACTION_TOKEN_SECRET=' . bin2hex(random_bytes(32)) . PHP_EOL;"
```

Edit `config/env.php`: `APP_ENV=dev`, `APP_URL=http://<server>:8080` (or the vhost URL),
`DB_USER=studygame_app` + its password, paste the two generated secrets, and set
`MALUMAIL_API_KEY` if this server should send real mail (empty logs to `storage/mail.log`).
The Phase 4 block at the bottom of the example is already correct for same-host services.

## 5. Python services

```bash
cd /var/www/studygame
python3 -m venv services/.venv
services/.venv/bin/pip install "mcp[cli]" asyncpg fastapi uvicorn httpx claude-agent-sdk
```

Create `config/services.env` (mode 600 — this is the systemd EnvironmentFile):

```
RECORDS_MCP_DSN=postgresql://studygame_records_ro:<records password>@127.0.0.1:5432/studygame
ACTIVITY_MCP_DSN=postgresql://studygame_activity_ro:<activity password>@127.0.0.1:5432/studygame
MCP_AUTH_DSN=postgresql://studygame_mcp_auth:<mcpauth password>@127.0.0.1:5432/studygame
RECORDS_MCP_URL=http://127.0.0.1:8901/mcp
ACTIVITY_MCP_URL=http://127.0.0.1:8902/mcp
APP_INTERNAL_URL=http://localhost:8080
ACTION_TOKEN_SECRET=<same value as config/env.php>
ASSISTANT_MODEL=claude-sonnet-5
ANTHROPIC_API_KEY=<key>
ASSISTANT_MCP_TOKEN=<minted in step 6>
```

`ACTION_TOKEN_SECRET` MUST match `config/env.php` (PHP mints, services relay), and
`APP_INTERNAL_URL` must match how PHP is actually reachable from localhost (see step 7's
note about `localhost` vs `127.0.0.1`).

## 6. Mint the assistant's service token

```bash
TOKEN="sgmcp_$(openssl rand -hex 24)"
HASH=$(printf '%s' "$TOKEN" | sha256sum | cut -d' ' -f1)
psql -h 127.0.0.1 -U studygame_app -d studygame \
     -c "INSERT INTO mcp_tokens (label, server, token_hash) VALUES ('assistant-service', 'both', '$HASH');"
echo "ASSISTANT_MCP_TOKEN=$TOKEN"   # paste into config/services.env
```

(Member-facing tokens are minted later on /settings/mcp — this one is the assistant's own.)

## 7. Quick dev run (no Apache)

Three terminals (or `tmux`), each after `set -a; source config/services.env; set +a`:

```bash
services/.venv/bin/python services/records_mcp.py
services/.venv/bin/python services/activity_mcp.py
services/.venv/bin/python services/assistant.py
```

and the app:

```bash
PHP_CLI_SERVER_WORKERS=8 php -S 0.0.0.0:8080 -t public public/router.php
```

`PHP_CLI_SERVER_WORKERS=8` is required — a single-worker dev server deadlocks the moment
the command bar (PHP waiting on the assistant) triggers an action (assistant calling PHP).
If you bind `0.0.0.0`, set `APP_INTERNAL_URL=http://127.0.0.1:8080`; if you bind
`localhost`, keep `localhost` (PHP may listen on IPv6 only in that case).

## 8. Full setup (Apache + systemd) — when the dev server should behave like production

```bash
sudo cp deploy/studygame-*.service /etc/systemd/system/
# Edit the three units if your path/user differ from /var/www/studygame + user 'studygame'.
sudo systemctl daemon-reload
sudo systemctl enable --now studygame-records-mcp studygame-activity-mcp studygame-assistant
sudo a2enmod proxy proxy_http rewrite
# Vhost: DocumentRoot /var/www/studygame/public, route everything through router.php,
# and Include /var/www/studygame/deploy/apache-mcp.conf for the /mcp/* endpoints.
sudo systemctl reload apache2
```

With Apache in front, set `APP_URL` (and the `PUBLIC_*_MCP_URL` defaults follow) to the
vhost URL, and `APP_INTERNAL_URL` in services.env to the same URL Apache serves locally.

## 9. First login + verification

1. Register at `/register`, then promote yourself:
   `psql ... -c "UPDATE users SET role='admin', email_verified_at=now() WHERE email='<you>';"`
2. Question bank: `/questions/` shows 69 active CCAO-F questions (fresh seed) or your
   migrated bank.
3. Game night: `/games/new` → create a CCAO-F game → join from a phone on the LAN at
   `http://<server>:8080/join` with the PIN → play a round end to end.
4. MCP: `/settings/mcp` → create a token → from any machine:
   `claude mcp add --transport http cert-arena-records http://<server>:8080/mcp/records --header "Authorization: Bearer <token>"`
   (quick check: an unauthenticated POST to `/mcp/records` must return 401).
5. Assistant: command bar → "who won our last game?" then "take me to group readiness
   for the associate exam" (should answer, then navigate).
6. Undo: "retire question seventy" then "undo that" — status returns to active, and both
   rows in `/analytics/` activity show YOUR name (not 'system').

## 10. Ongoing workflow

Develop anywhere, push to GitHub, `git pull` on the server, re-apply any NEW db/*.sql
(migrations are append-only — never edit an applied file), and `sudo systemctl restart
'studygame-*'` when `services/*.py` changed. The gitignored local files (`config/env.php`,
`config/services.env`) survive pulls untouched.
