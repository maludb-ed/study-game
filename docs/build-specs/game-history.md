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

## Exemplar-alignment amendments (planning-class, pre-build — workers follow these)

1. **Router:** add regex `#^/games/(\d+)$#` → `/games/view.php` (id into `$_GET['id']`), placed with the existing games regex routes. All other routes untouched.
2. **Sanctioned S2 touch:** in `app/views/games/partials/row.php`, ended/aborted rows change their first-cell link (and the actions cell icon link) from the S2 tooltip-placeholder to `/games/{id}` (hx-get into #page-content, explicit hx-push-url `/games/{id}`, per the exemplar's row pattern). Live rows keep linking to `/games/{id}/host`. No other change to that file.
3. **Access:** `require_login()` — any member may view any report (reports are the group's shared study record). Not host-gated, not admin-gated.
4. **Ranking source:** for `state='ended'` use `final_rank`/`final_score` (written by finalize). For aborted/live games compute totals live — `COALESCE(sum(points_awarded),0)` over live (non-kicked) players ranked with `RANK() OVER (ORDER BY total DESC)` — and show the state badge prominently. Do NOT modify engine.php; report-queries.php is self-contained.
5. **Correct rate per question** = correct answers / live-player count at report time (not per answers-received), so unanswered players count against the rate — that is the study-relevant number. Also show avg response seconds (1 decimal) over received answers only; em-dash when no answers.
6. **Distribution mini-bars:** per option, progress bar of picks as % of answers received for that question (inline `style="width: N%"` on `.progress-bar` is the sanctioned template pattern), correct option's bar `bg-success`, others `bg-secondary`; option color dot + text ≤40 chars truncated. Show "no answers" muted text when a question got none.
7. **Report layout ids:** screen id `game-report`; containers `game-report-header-card`, `game-report-ranking`, `game-report-questions`; rows `game-report-rank-row-{player id}`, `game-report-question-row-{position}`. Stem cell links to `/questions/{question id}` (question-view) per the exemplar's link pattern.
8. **game-report breadcrumb/title:** "Game Report" with breadcrumb Home / Games / {exam code} · {Mon d, Y}. Header card fields: exam badge, played date (created_at), host name, settings line ("10 questions · 20s · streak bonus on/off"), player count, state badge (vocabulary: ended → success, aborted → danger — live states render their normal colors).
