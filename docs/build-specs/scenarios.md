# Build spec: scenarios slice (S6)

Exemplar to replicate: questions (S1) for CRUD; game-loop (S3) for the in-game part.
Schema tables: scenarios, questions (scenario_id), game_questions.

## Screens

| Screen id | Canonical URL | Purpose |
|---|---|---|
| scenario-list | /scenarios/ | List: title | exam | status | linked questions | updated |
| scenario-add | /scenarios/new | Form |
| scenario-edit | /scenarios/{id}/edit | Same form pre-filled |
| scenario-view | /scenarios/{id} | Body + its questions in order |

## Form
- exam_id | select | required | scenario-form-field-exam
- title | text | required 5–120 chars | scenario-form-field-title
- body | textarea 8 rows | required 50–4000 chars | scenario-form-field-body
- status | select draft/active/retired | scenario-form-field-status
- Questions are attached FROM the question form (S1's form gains a scenario select filtered by exam — the one sanctioned S1 touch, listed here so the worker changes exactly that)

## Game integration (extends S3)
- The draw keeps scenario questions together: when a drawn question has scenario_id, pull its sibling active questions (same scenario) into consecutive positions, capped at the domain target
- New state-machine wrinkle: entering a question whose scenario differs from the previous question's inserts a scenario intro phase — host shows title+body full screen, players see "Read the host screen"; host Next proceeds to the question. Implement as a nullable games.scenario_intro_for (bigint) column — THE ONE SCHEMA CHANGE, pre-approved here — set when entering, cleared on Next
- Reveal and scoring unchanged

## Files (exactly these)
- public/scenarios/index.php · form.php · save.php · status.php · delete.php
- app/features/scenarios/queries.php
- app/views/scenarios/page.php · partials/table.php · row.php · form.php · saved.php
- app/views/games/partials/host-scenario.php · app/views/play/scenario.php
- db/007_scenario_intro.sql (the pre-approved column)

## Query functions (signatures fixed)
- find_scenarios(PDO, exam_id=0, status='', page=1): array
- find_scenario(PDO, int id): ?array
- insert_scenario(PDO, int exam_id, string title, string body, string status, int created_by): array
- update_scenario(PDO, int id, …): array
- set_scenario_status(PDO, int id, string status): array
- delete_scenario(PDO, int id): bool   — refuses when questions reference it

## Action manifest entries
- Screens above; actions scenario_create/scenario_update/scenario_retire (undo per question pattern), scenario_delete (confirm)

## Activity log events
- scenario_created/updated/status_changed/deleted, scenario_shown (in game)

## Status vocabulary mapping
- draft → secondary · active → success · retired → dark

## Out of scope
- Scenario-only games. Timed reading. Images.

## Open Questions (must be EMPTY before a worker starts)
- (none)
