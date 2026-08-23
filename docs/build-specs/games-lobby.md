# Build spec: games-lobby slice (S2)

Exemplar to replicate: questions (S1). Schema tables: games, game_questions, game_players (+ read exams, domains, questions).

## Screens

| Screen id | Canonical URL | Purpose |
|---|---|---|
| game-new | /games/new | Form: exam, question_count, seconds_per_question, streak_bonus |
| game-list | /games/ | Games list: date, exam, host, state, players, winner |
| game-host | /games/{id}/host | Host console — lobby phase: PIN display (huge), joined players, Kick, Start |
| join | /join | PUBLIC: PIN entry → nickname entry |
| play | /play | PUBLIC: lobby holding screen ("You're in — watch the host screen") |

## game-new form
- exam_id | select (active exams with ≥ question_count active questions; disabled otherwise with coverage hint) | required | game-form-field-exam
- question_count | number 5–30 default 10 | required | game-form-field-count
- seconds_per_question | select 10/20/30/60 default 20 | required | game-form-field-seconds
- streak_bonus | checkbox default on | game-form-field-streak
- Submit → creates game (state=lobby, 6-digit PIN unique among live games) + performs THE DRAW → redirects to game-host

## The draw (fixed algorithm, in insert_game)
1. target[domain] = round(weight_pct/100 × question_count); fix rounding drift by adding/removing from largest-remainder domains until Σ = question_count.
2. Within each domain, order active questions by: times_played ASC, miss_rate DESC, random(). Take target[domain].
3. If a domain lacks enough active questions, fill the shortfall from other domains in weight order; record shortfall in activity log details.
4. Shuffle the combined set; insert game_questions with position 1..n. All in one transaction with the game row.

## Join flow (public, no login)
- /join: PIN input (join-field-pin, numeric, 6 digits) → server checks a game in state=lobby (also allows rejoin: any live state if the player_token cookie matches a player of that game)
- Nickname step: join-field-nickname, 2–20 chars, unique in game (case-insensitive), profanity-free (simple denylist)
- On join: insert game_players, set player_token cookie (32-byte hex, SameSite=Lax, HttpOnly), redirect to /play
- Rate limit: 10 PIN attempts per IP per minute (login_attempts, kind=pin_join) → 429 partial
- Members already logged in see a "claim identity" select (own name preselected) — sets game_players.user_id

## Lobby live updates (polling contract, used again in S3)
- game-host lobby region: div#game-host-players hx-get="/games/{id}/host-state.php" hx-trigger="every 1s" hx-swap="morph or innerHTML"
- /play lobby region polls /play/state.php every 2s (returns holding partial until state leaves lobby)
- Both state endpoints receive ?v={games.updated_at epoch}: unchanged state returns 204 (no swap); a finished/aborted game returns 286 (HTMX stops polling) with a terminal partial
- Host actions: Start (POST advance.php, lobby→question — S3 owns what happens next; in this slice Start is present but gated: enabled only when ≥1 player), Kick (POST kick.php, confirm, sets kicked_at; kicked player's poll gets a "removed by host" terminal partial)

## Files (exactly these)
- public/games/index.php · form.php · save.php · host.php · host-state.php · kick.php · abort.php
- public/join/index.php · join.php
- public/play/index.php · state.php
- app/features/games/queries.php
- app/views/games/page.php · partials/table.php · row.php · form.php · host-lobby.php · host-players.php
- app/views/play/join-pin.php · join-nickname.php · lobby.php · terminal.php

## Query functions (signatures fixed)
- find_games(PDO, exam_id=0, page=1): array
- find_game(PDO, int id): ?array
- insert_game(PDO, int exam_id, int host_user_id, int question_count, int seconds, bool streak_bonus): array  — includes the draw; throws DomainException('insufficient_bank') if total active < question_count
- find_game_by_pin(PDO, string pin): ?array
- insert_game_player(PDO, int game_id, string nickname, ?int user_id, string token): array
- find_game_players(PDO, int game_id): array
- kick_game_player(PDO, int game_id, int player_id): bool
- abort_game(PDO, int id): bool

## Action manifest entries
Screens above; actions game_create, game_kick_player (confirm), game_abort (confirm) per the manifest.

## Activity log events
- game_created (details: draw targets, shortfalls), player_joined (actor_type=player), identity_claimed, player_kicked, game_aborted, screen_view

## Status vocabulary mapping (games.state)
- lobby → info · question/reveal/leaderboard → warning · podium/ended → success · aborted → danger

## Out of scope
- Everything after Start is pressed (S3). Reports (S4). No spectator screen.

## Open Questions (must be EMPTY before a worker starts)
- (none)

## Exemplar-alignment amendments (planning-class, pre-build — workers follow these)

1. **Public player shell:** add `app/views/play/layout.php` — minimal public wrapper modeled on `app/views/auth/layout.php` (same asset includes plus `/assets/vendors/js/htmx.min.js` before `vendors.min.js`; no nxl navigation/header/footer, no command bar). All /join and /play screens render inside it. Full-page navigations between join steps; polling regions are HTMX swaps inside the page.
2. **Start button in S2:** render Start ACTIVE (disabled only while 0 live players), posting `hx-post` to `/games/{id}/advance` with `hx-vals '{"expected_state": "lobby"}'`, target `#game-host-stage`. S2 ships `public/games/advance.php` as a PLACEHOLDER: `require_post` + CSRF + host check, then re-renders the host lobby partial with an info alert "The live game loop ships with the game-loop slice." S3 replaces this file; nothing else changes.
3. **Identity claim is a checkbox, not a select** (a select would let players claim other members): if the joining browser has a logged-in session, the nickname step shows checkbox `join-field-claim` — "Count this game toward my readiness (<display name>)" — checked by default; when checked, `game_players.user_id` = session user id. Anonymous joiners never see it.
4. **Nickname denylist (exact list, case-insensitive substring):** fuck, shit, bitch, cunt, penis, nazi. Reject with "Pick a different nickname."
5. **Host console stage region:** the whole below-PIN area of game-host is `div#game-host-stage` (players list + Start button + notices). `host-state.php` returns this region; the S2 poll targets it. (S3 swaps richer stages into the same id.)
6. **Polling versioning:** state endpoints take `?v=` = `extract(epoch from games.updated_at)::bigint || '-' || (count of live players)` so lobby joins/kicks bump the version even though joins don't touch the games row. Compute both sides server-side; the partial stamps the current `v` into the poller's `hx-get` URL (attribute swap comes free with the region swap).
7. **Router table (exact):** map `'/games/' => '/games/index.php'`, `'/games/new' => '/games/form.php'`, `'/games/save' => '/games/save.php'`, `'/join' => '/join/index.php'`, `'/join/submit' => '/join/join.php'`, `'/play' => '/play/index.php'`, `'/play/state' => '/play/state.php'`; regex `#^/games/(\d+)/host$#` → host.php, `#^/games/(\d+)/host-state$#` → host-state.php, `#^/games/(\d+)/advance$#` → advance.php, `#^/games/(\d+)/kick$#` → kick.php, `#^/games/(\d+)/abort$#` → abort.php (id into `$_GET['id']`). Remove `/games/`, `/games/new`, `/join`, `/play` from the router's stub table.
8. **Ordering stats for the draw:** `times_played` = count of `game_questions` rows for the question; `miss_rate` = wrong answers / total answers for the question (0 when unanswered). Order candidates by `times_played ASC, miss_rate DESC, random()`.
9. **Auth split:** game-new/game-list/game-host and all host actions require login (any member — hosting is not admin-gated); /join and /play are public (no session), identified by the `player_token` cookie (`HttpOnly, SameSite=Lax, Secure when HTTPS`, 12-hour lifetime). CSRF: join/play POSTs are public and cookie-less until join completes — join.php and play answer posts are exempt from `verify_csrf` (no session to protect) but ARE rate-limited (pin_join: 10/min/IP) and validated; host actions keep full CSRF.
