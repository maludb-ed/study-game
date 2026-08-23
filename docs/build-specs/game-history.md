# Build spec: game-history slice (S4)

Exemplar to replicate: questions (S1). Read-only slice.
Schema tables (read only): games, game_questions, game_players, answers, questions, question_options, exams, domains.

## Screens

| Screen id | Canonical URL | Purpose |
|---|---|---|
| game-list | /games/ | (extends S2's list) row link goes to game-report for finished games, game-host for live ones |
| game-report | /games/{id} | Podium, full ranking, per-question breakdown |

## game-report layout (single page, three sections)
1. Header card: exam badge, date, host, settings, player count
2. Podium: top 3 + full ranking table — columns: rank | nickname (+ member name if claimed) | points | correct/total | best streak
3. Per-question table — columns: # | stem (links question-view) | domain | correct % | avg response s | distribution mini-bars per option (correct highlighted)
- Unfinished/aborted games render sections 1 + whatever data exists, with the state badge

## Files (exactly these)
- public/games/view.php
- app/features/games/report-queries.php
- app/views/games/report.php · partials/report-ranking.php · report-questions.php

## Query functions (signatures fixed)
- find_game_report(PDO, int id): ?array           — header + ranking
- find_game_question_stats(PDO, int id): array    — per question: correct rate, avg ms, per-option counts

## Action manifest entries
- Screens: game-report ("when the user wants the results of a game night"). No new actions.

## Activity log events
- screen_view only (read-only slice)

## Out of scope
- Cross-game analytics (S5). Export. Editing anything.

## Open Questions (must be EMPTY before a worker starts)
- (none)
