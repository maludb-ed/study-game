# Phase 1 — MCP tool surface

Two client-facing read servers (Python + FastMCP, streamable HTTP, read-only roles from
`db/006_mcp_access.sql`), per the mcp-servers skill. Every Phase 0 question maps to a tool;
one guarded long-tail search tool per server. All tools: `readOnlyHint: true`, Pydantic v2
inputs with `extra="forbid"`, limit/offset pagination, JSON results.

## Record server — `studygame_records_mcp` (PostgreSQL, role `studygame_records_ro`)

| Tool | Answers | Input (all optional unless marked) | Returns |
|---|---|---|---|
| `list_questions` | R1 | exam_code, domain, status, difficulty, search, limit=20, offset | Question rows (id, exam, domain, stem, status, difficulty, times_played, miss_rate) |
| `bank_coverage` | R2 | exam_code **(req)**, game_size=10 | Per domain: blueprint weight, active count, target count for a game, shortfall flag |
| `list_games` | R3 | exam_code, limit=10, offset | Games with date, exam, host, player count, winner |
| `game_report` | R4 | game_id (default: latest finished) | Podium, final scores, per-question correct rate |
| `group_domain_performance` | R5 | exam_code **(req)**, since_date | Per domain: answers, accuracy, avg response ms, trend vs prior period |
| `member_readiness` | R6 | member **(req: name or email)**, exam_code **(req)** | Per domain accuracy, blueprint-weighted score scaled to 100–1000 vs the 720 bar, questions seen/unseen |
| `member_question_gaps` | R8 | member **(req)**, exam_code **(req)**, limit=20 | Questions never seen + questions missed repeatedly |
| `most_missed_questions` | R7 | exam_code, domain, limit=10 | Questions by miss rate (min 3 plays), with correct answer + explanation |
| `alltime_leaderboard` | R9 | exam_code | Members by total points, games played, avg rank (claimed identities only) |
| `question_answer_stats` | R10 | question_id or search **(one req)** | Per-option pick distribution across all plays, response-time stats |
| `records_search` | long tail | sql **(req, single SELECT)**, limit cap 200 | Rows. Validated in code: SELECT-only, rejects users' credential columns; schema summary embedded in the tool description |

`member_readiness` scoring note (fixed formula, stated in the tool description): weighted
score = Σ(domain accuracy × domain weight), scaled to the 100–1000 band as `100 + 900 × weighted_accuracy`;
"ready" = sustained ≥ 720 with ≥ 60% of the exam's blueprint seen. It is an indicator, not a Pearson VUE predictor.

## Activity server — `studygame_activity_mcp` (MaluDB / activity_log, role `studygame_activity_ro`)

| Tool | Answers | Input | Returns |
|---|---|---|---|
| `play_cadence` | A1 | exam_code, since_date | Game events over time: when played, gaps, frequency per exam |
| `game_night_attendance` | A2 | date **(req)** | Who hosted, who joined, join/leave times |
| `member_activity_timeline` | A3 | member **(req)**, since_date, limit=50 | That member's activity stream (screens, actions, games) |
| `record_history` | A4 | entity **(req)**, entity_id **(req)** | Who created/edited it and when, with before/after values |
| `game_timeline` | A5 | game_id **(req)** | Full event sequence for one game (joins, advances, answers, kicks) |
| `activity_search` | long tail | sql **(req, single SELECT)**, limit cap 200 | Rows from activity_log; same guard as records_search |

## Deployment

Systemd units `studygame-records-mcp` / `studygame-activity-mcp`, localhost ports, Apache
reverse proxy at `/mcp/records` and `/mcp/activity` under TLS, bearer tokens from
`mcp_tokens` (hashed, revocable, `last_used_at` stamped). Both URLs + token management
published on the `settings-mcp` screen. Every tool call logs to `activity_log` (actor_type
`agent`). Verify with MCP Inspector: each Phase 0 question answerable before Phase 4 closes.
