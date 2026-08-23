# Build spec: analytics slice (S5)

Exemplar to replicate: questions (S1). Read-only slice; every number here must be
computed by the SAME SQL the record-MCP tools use (shared queries file) so screens
and agent answers never disagree.
Schema tables (read only): all game + question tables, users.

## Screens

| Screen id | Canonical URL | Purpose |
|---|---|---|
| analytics-group | /analytics/ | Group readiness per exam |
| analytics-member | /analytics/members/{id} | One member's readiness grid |
| drill-list | /analytics/drill | Most-missed questions to review together |

## analytics-group
- Exam tabs (4). Per exam: domain table — columns: domain | weight | group accuracy | answers | trend arrow (vs previous 30 days) | weakest-member hint
- Headline stat tiles: games played, questions answered, group weighted score (100–1000), members ≥720
- Members table: name | games | weighted score (dot+badge vs 720) | last played → links analytics-member

## analytics-member
- Header: member, exams played, claimed-answer count. Note when unclaimed nicknames exist ("3 games played unclaimed — claim on next join to count them")
- Grid: rows = domains of the selected exam (tab per exam), columns: weight | accuracy | seen/total active | weighted contribution
- Readiness banner per exam: weighted score scaled 100–1000 vs 720 bar + coverage % (formula fixed in phase-1-mcp-tools.md — same SQL as member_readiness)

## drill-list
- Filters: exam, domain. Table: stem (links question-view) | domain | plays | miss rate | avg response s
- Min 3 plays to appear; sorted miss rate desc; page size 25

## Files (exactly these)
- public/analytics/index.php · member.php · drill.php
- app/features/analytics/queries.php        — ALSO imported by the records MCP server's SQL (single source of truth: each function's SQL lives in app/features/analytics/sql/{name}.sql, loaded by PHP and by Python)
- app/views/analytics/group.php · member.php · drill.php · partials/domain-table.php · readiness-banner.php

## Query functions (signatures fixed)
- group_domain_performance(PDO, int exam_id, ?string since): array
- member_readiness(PDO, int user_id, int exam_id): array
- most_missed_questions(PDO, int exam_id=0, int domain_id=0, int page=1): array
- alltime_leaderboard(PDO, int exam_id=0): array

## Action manifest entries
- Screens above ("when the user wants to know how ready the group/a member is", "what to study next"). No new actions.

## Activity log events
- screen_view only

## Status vocabulary mapping (readiness vs 720)
- ≥720 → success · 600–719 → warning · <600 → danger · insufficient data (<60% blueprint seen) → secondary

## Out of scope
- Charts libraries (tables + Bootstrap progress bars only). PDF export. Per-question review mode.

## Open Questions (must be EMPTY before a worker starts)
- (none)

## Exemplar-alignment amendments (planning-class, pre-build — workers follow these)

1. **Shared SQL files:** create `app/features/analytics/sql/` with exactly four files — `group_domain_performance.sql`, `member_readiness.sql`, `most_missed_questions.sql`, `alltime_leaderboard.sql` — using `:name` placeholders. `queries.php` loads each with `file_get_contents(__DIR__ . '/sql/{name}.sql')` (path literal, never from input) and prepares it; the Phase 4 records-MCP server will load the same files from Python. Any additional small SQL (headline tiles, trend windows) may live inline in queries.php.
2. **Readiness formula (locked, from docs/plan/phase-1-mcp-tools.md):** domain_accuracy = correct/answered per domain (claimed answers only); weighted_accuracy = Σ(weight_pct × domain_accuracy) / 100 with unseen domains contributing 0; score = round(100 + 900 × weighted_accuracy); coverage_pct = Σ(weight_pct of domains with ≥1 answered) . Bands: score ≥720 AND coverage ≥60 → success "On track"; coverage <60 → secondary "Not enough data yet"; else 600–719 warning / <600 danger.
3. **Whose answers count:** member analytics + alltime leaderboard use CLAIMED answers only (`game_players.user_id` set). Group analytics (domain table, tiles, drill list) use ALL answers. State this in each screen's muted footnote line.
4. **Trend arrow:** accuracy over the last 30 days vs accuracy over everything before; ≥ +5 points → up/success, ≤ −5 → down/danger, else — muted. Omit the arrow entirely (em-dash) when either window has no answers.
5. **Exam tabs:** card-header nav-tabs pattern (design-system components); each tab is an hx-get to the canonical URL with `?exam_id=N` into #page-content with explicit hx-push-url. Default exam = lowest-id exam having any answers, else exam id 1. Same param on member screen; drill list uses plain filter selects like the questions exemplar.
6. **Access:** `require_login()` everywhere; read-only; only `log_screen_view` calls ('analytics-group', 'analytics-member', 'drill-list').
7. **Router:** `'/analytics/' => '/analytics/index.php'`, `'/analytics/drill' => '/analytics/drill.php'`, regex `#^/analytics/members/(\d+)$#` → `/analytics/member.php` (id into `$_GET['id']`). Remove `/analytics/` and `/analytics/drill` from the router stub table.
8. **Files (final list):** public/analytics/index.php · member.php · drill.php; app/features/analytics/queries.php + sql/ (amendment 1); app/views/analytics/group.php · member.php · drill.php · partials/domain-table.php · readiness-banner.php.
9. **Sparse data is the normal case right now** (one ended game, unclaimed player; zero claimed answers). Every table/tile/banner must render clean empty states ("No games yet", "No claimed answers yet — check 'count this game toward my readiness' when joining"). Verify against the live database exactly as it is.
10. **Ids:** screens `analytics-group`, `analytics-member`, `drill-list`; containers `analytics-group-tabs`, `analytics-group-tiles`, `analytics-group-domain-table`, `analytics-group-members-table`, `analytics-member-grid`, `analytics-member-banner`, `drill-list-table`; loop rows `analytics-domain-row-{domain id}`, `analytics-member-row-{user id}`, `drill-row-{question id}`.
