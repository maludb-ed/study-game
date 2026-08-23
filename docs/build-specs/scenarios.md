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

## Exemplar-alignment amendments (planning-class — this slice was BUILT planning-class because it cuts into the S3 engine)

1. **Status vocabulary corrected** to the locked design-decisions mapping: draft → dark · active → success · retired → secondary (the table above had draft/retired swapped).
2. **Intro-phase mechanics (locked):** entering position N whose question's scenario_id is non-null and differs from position N−1's scenario_id sets `games.scenario_intro_for = scenario_id`, `state='question'`, and leaves `question_started_at`/`question_deadline` NULL (game_questions unstamped) — the clock does NOT run during reading. `check_question_complete` returns early while `question_deadline IS NULL`. `insert_answer` naturally rejects (timing check fails on NULL deadline). The host intro stage's Next posts `expected_state='scenario_intro'`; `advance_game` handles it when `state='question' AND scenario_intro_for IS NOT NULL`: clears the column, stamps both deadline pairs, logs `question_shown`. `find_game_version` coalesces the remaining-seconds component to 0 while unstamped. Stage resolution: `state='question' AND scenario_intro_for` → host-scenario.php / play/scenario.php.
3. **Draw sibling rule (locked):** per domain, after the normal target take — if any taken question has a scenario_id, its active same-domain siblings REPLACE lowest-priority non-scenario picks in that domain, never exceeding the domain target; the final shuffle shuffles GROUPS (scenario groups + singleton questions), keeping each group's questions consecutive in scenario-defined order (question id ascending).
4. **S1 form touch mechanics:** the question form's exam-dependent selects (domain + scenario) are wrapped in `div#question-form-field-dependent`; the exam select's fragment call moves from `/questions/domain-options` to `/questions/dependent-fields` (new file `public/questions/dependent-fields.php` REPLACES `domain-options.php` — file deleted, route replaced). The scenario select (`question-form-field-scenario`, optional, "No scenario") lists draft+active scenarios of the chosen exam.
5. **Scenario view shows** body + linked questions ordered by question id, each linking to `/questions/{id}`; question counts on the list are total (any status).
6. **delete_scenario** refuses (returns false) when any question references it — UI only offers Delete for drafts with zero linked questions, mirroring S1.
