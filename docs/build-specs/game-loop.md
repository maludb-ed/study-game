# Build spec: game-loop slice (S3)

Exemplar: questions (S1) for code shape; games-lobby (S2) for the polling contract.
Schema tables: games, game_questions, game_players, answers (+ read question_options).
RECOMMENDATION: planning-class builds this slice too — it is the only non-CRUD slice.

## The state machine (server-authoritative; the ONLY writer is advance.php + answer.php)

| From | Trigger | To | Side effects |
|---|---|---|---|
| lobby | host Start (≥1 player) | question | current_position=1; stamp game+game_question started_at/deadline (= now + seconds_per_question) |
| question | all live players answered OR deadline passed (checked lazily on any poll/advance) | reveal | score unanswered players 0; compute answer distribution |
| reveal | host Next | leaderboard | — |
| leaderboard | host Next (more questions) | question | current_position+1; stamp timestamps |
| leaderboard | host Next (last question) | podium | write final_score/final_rank on game_players |
| podium | host Finish | ended | ended_at=now |
| any live | host Abort (confirm) | aborted | ended_at=now |

- advance.php validates expected_state (from the manifest) against games.state; mismatch → 409 partial re-rendering the current host view (double-click safe).
- The question→reveal transition is lazy: host-state.php and state.php both check `deadline < now() OR all answered` and perform the transition inside a `select … for update` on the game row. No cron, no timers.

## Host console (game-host, per phase)
- question: question number/total, stem, 4 options in the fixed color grid (1 red-triangle, 2 blue-diamond, 3 yellow-circle, 4 green-square), countdown (CSS animation sized to seconds, restarted per question), live answered-count
- reveal: distribution bar per option (count), correct option highlighted, the explanation card (the teaching moment — always shown), Next button
- leaderboard: top 5 by total points with rank deltas, Next button
- podium: top 3 large + full ranking, Finish button
- Poll div#game-host-stage, contract exactly as S2 (204 unchanged / 286 terminal)

## Player screen (/play, per phase)
- question: four full-width color buttons (labels only — the stem lives on the host screen; buttons show option text at ≤60 chars, else the color/shape + first words), POST to answer.php on tap, then a "locked in" partial
- reveal: correct/incorrect + points earned + current streak
- leaderboard: own rank + points ("You're 4th — 2,340 pts")
- podium: own final rank; terminal 286
- Missed the question (no answer): reveal shows "Time's up — 0 pts"

## answer.php (the hot path)
- Auth: player_token cookie → game_players row → game in state=question and game_question.deadline ≥ now(); else 409 partial
- response_ms = now − game_question.started_at (server clocks, clamp ≥ 0); duplicate answer (unique violation) → re-render locked-in partial (idempotent)
- points: 1000 if response_ms < 500; else floor((1 − (response_ms/(seconds×1000))/2) × 1000); wrong = 0
- streak_after: previous streak+1 if correct else 0; +100 bonus when streak_bonus AND correct AND streak_after ≥ 2
- Trigger the lazy transition check after insert (last player in → instant reveal)

## Files (exactly these)
- public/games/advance.php · answer endpoint public/play/answer.php (extends S2's host.php/host-state.php/state.php with phase views — no new state endpoints)
- app/features/games/engine.php   — advance_game(PDO,…), check_question_complete(PDO,…), score_answer(pure fn), finalize_game(PDO,…)
- app/views/games/partials/host-question.php · host-reveal.php · host-leaderboard.php · host-podium.php
- app/views/play/question.php · locked-in.php · reveal.php · leaderboard.php · podium.php

## Query/engine functions (signatures fixed)
- advance_game(PDO, int game_id, int host_user_id, string expected_state): array
- check_question_complete(PDO, int game_id): bool          — performs lazy transition, row-locked
- insert_answer(PDO, int game_player_id, int game_question_id, int option_id): array
- score_answer(int response_ms, int seconds, bool correct, int prev_streak, bool streak_bonus): array{points, streak_after}  — pure, unit-tested
- find_host_stage(PDO, int game_id): array · find_player_stage(PDO, string player_token): array
- finalize_game(PDO, int game_id): void

## Action manifest entries
- game_advance (expected_state param) — already in the manifest; voice "next question" from the host works through it

## Activity log events
- game_started, question_shown (game_id, question position), answer_submitted (actor_type=player, details: correct, ms, points), question_revealed, game_finished (details: podium)

## Out of scope
- Scenario intro cards (S6). Reports (S4). Sound. Spectator view. SSE (polling per S2 contract only).

## Open Questions (must be EMPTY before a worker starts)
- (none)
