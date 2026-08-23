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
