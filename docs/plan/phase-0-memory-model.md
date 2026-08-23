# Phase 0 — Memory-first plan: Cert Arena (working title)

Kahoot-style live quiz game for a Claude Partner Network study group preparing for the
Anthropic certification exams. Host-paced game show: host screen + player devices, PIN
join, speed-based scoring. Every game targets exactly one exam; the group targets all
four. First exam seeded: **CCAO-F (Claude Certified Associate, Foundations)**.

## 1. Memory model

What the application remembers, before what it does.

### Entities

| Entity | Remembers | Key relationships |
|---|---|---|
| **user** | Study-group member: name, email, auth identities (password / Google), TOTP, role (`admin` curates bank & exams; any member may host) | hosts games; claims game_player identities; authors questions |
| **exam** | One of the four certifications: code (CCAO-F, CCDV-F, CCAR-F, CCAR-P), name, official question count, duration, passing score (720/1000), active flag | 1—* domain, question, game, scenario |
| **domain** | One blueprint domain of an exam: name, official weight %, display order | exam 1—* domain; domain 1—* question |
| **question** | A study question: stem, type (multiple choice; true/false = 2-option MC), difficulty, **explanation shown at reveal** (the teaching moment), status (draft/active/retired), source, author, optional scenario | belongs to exam + domain; 1—* question_option |
| **question_option** | Answer option: text, is_correct, display order (order determines Kahoot-style color/shape) | question 1—2..6 options |
| **scenario** | Case-study intro for architect-style scenario rounds (schema now, feature later) | exam 1—*; scenario 1—* question |
| **game** | One live session: exam, host, join PIN, state (`lobby → question → reveal → leaderboard → … → podium → ended`), current position, settings (question count, seconds per question, streak bonus on/off), timestamps | exam 1—*; user(host) 1—* |
| **game_question** | Ordered snapshot of the questions drawn for a game: position, points possible, started_at, answer deadline. Snapshot so later bank edits never corrupt history | game 1—*; question 1—* |
| **game_player** | A participant in one game: nickname, optional claimed user id, joined_at, final score, final rank | game 1—*; user 0..1—* |
| **answer** | One player's answer to one game question: option picked, correct?, response ms (server-clocked), points earned, streak after | unique per game_player × game_question |
| **activity log** | Every screen entered, record changed, and game action by every actor (who/what/when/where/which + before/after) — ingested into MaluDB from day one | feeds activity memory |

### Facts that drive design

- Scoring is server-authoritative: `floor((1 − (t/T)/2) × 1000)` on server timestamps, optional +100 streak bonus.
- Question draw for a game is weighted to the selected exam's official blueprint and biased toward unseen/most-missed questions.
- Blueprint weights live in `domain` rows as data, not code — exam revisions are row updates.

## 2. The question list

### Record questions (PostgreSQL → screens + record-memory MCP tools)

- R1. Which questions do we have for exam X (by domain, status, difficulty)?
- R2. Do we have enough active questions per domain to cover exam X's blueprint?
- R3. Which games have we played, and what happened in each?
- R4. Who won game G — podium and final scores?
- R5. Which domains does the group score worst in for exam X?
- R6. Is member M ready to book exam X? (blueprint-weighted domain accuracy vs the 720/1000 bar)
- R7. Which questions does the group miss most often?
- R8. Which questions has member M never seen, or keep missing?
- R9. All-time leaderboard across games?
- R10. How did the group's answers distribute on question Q when played?

### Activity questions (logs → MaluDB → activity-memory MCP tools)

- A1. When did we last play exam X, and how often do we play?
- A2. Who hosted and who joined game night on date D?
- A3. When did member M start studying, and what is their cadence?
- A4. Who added or edited question Q, and when?
- A5. What happened during game G — join/leave/advance timeline?

## 3. Feature list (Phase 3 vertical-slice order)

| # | Slice | Contents |
|---|---|---|
| S1 | **Question bank** (exemplar slice) | Exam/domain reference screens (seeded data), question list + filters, create/edit with options + explanation, JSON bulk import for Claude-generated batches, retire/restore. Includes seed content: CCAO-F domains + initial ~70-question batch weighted per blueprint. |
| S2 | **Game creation & lobby** | New game (pick exam + settings), blueprint-weighted freshness-aware question draw, PIN join page, nickname + optional roster claim, live lobby. |
| S3 | **Live game loop** | Host screen state machine (question → reveal with distribution + explanation → leaderboard → podium), player answer screens, server-side speed scoring + streaks, ~1s HTMX polling. |
| S4 | **Game history & reports** | Past games, per-game podium and answers, per-question distributions. |
| S5 | **Readiness analytics** | Member exam×domain readiness grid vs passing bar, group weak-domain dashboard, most-missed drill list. |
| S6 | **Scenario rounds** | Scenario intro cards + linked question blocks in the game loop (architect exams). |

## Deliberate deviations from the default pattern

1. **PIN-join players are unauthenticated** (the Kahoot model — zero-friction join on phones). Guarded by: PIN + game-in-lobby state, host lobby control (kick), rate limiting. Players may optionally claim their roster identity so results feed their personal readiness analytics. All other screens require login.
2. **Live updates via short-interval HTMX polling (~1s)** against a tiny game-state endpoint. At study-group scale this is trivial load; SSE is the documented upgrade if polling ever feels laggy. The host-paced state machine means state changes only on host action or timer expiry.
