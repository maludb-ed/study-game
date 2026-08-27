# Build spec: questions slice (S1 — THE EXEMPLAR)

Built by the planning-class model; every later slice replicates this one.
Schema tables: exams, domains, questions, question_options (never modify).

## Screens

| Screen id | Canonical URL | Purpose |
|---|---|---|
| question-list | /questions/ | List per the canonical table pattern |
| question-add | /questions/new | Full-container form (empty) |
| question-edit | /questions/{id}/edit | Same form, pre-filled |
| question-view | /questions/{id} | Detail: stem, options (correct marked), explanation, play stats |
| question-import | /questions/import | Textarea for a JSON batch + validation report |
| exam-list | /exams/ | The four exams with active-question counts |
| exam-view | /exams/{id} | Domains, weights, bank coverage per domain (target = weight × 10-question game) |

## List screen
- Columns: stem (truncated 80 chars, links to question-view) | exam code (badge) | domain | difficulty | status (dot+badge) | updated (date, medium)
- Filters (form above table): exam select, domain select (repopulated from exam via HTMX), status select, search input
- Search matches: stem, explanation. Sort allowlist: updated_at (default desc), stem, difficulty. Page size: 25
- Row link target: question-view
- Row actions (admin only): Activate (draft → active) / Deactivate (active → draft) — POST /questions/status, swaps just that `<tr>`; edit icon

## Form (question-add / question-edit)
- exam_id | select (active exams) | required | on change reloads domain select | question-form-field-exam
- domain_id | select (domains of chosen exam) | required | must belong to exam | question-form-field-domain
- stem | textarea 3 rows | required, 10–1000 chars | question-form-field-stem
- options 1–4 | text inputs, fixed four rows, rows 3–4 optional | ≥2 filled | question-form-field-option-{1..4}
- correct | radio group next to options | required, must point at a filled option | question-form-field-correct-{1..4}
- explanation | textarea 3 rows | required, 10–4000 chars | question-form-field-explanation
- difficulty | select easy/medium/hard, default medium | required | question-form-field-difficulty
- status | select draft/active/retired, default draft | required | question-form-field-status
- source | text input | optional | question-form-field-source
- No tabs. Options are saved as delete-and-reinsert inside one transaction (display_order = input position).

## Import screen (question-import)
- Textarea question-import-field-json + Validate button (POST, returns report partial) + Import button (enabled only after a clean validation)
- JSON schema: `[{exam_code, domain, stem, options: [{text, correct}], explanation, difficulty?, source?}]`
- Validation: every exam_code and domain must resolve; 2–6 options; exactly one correct; all-or-nothing transaction; imported rows get status=draft and source prefixed `import {date}:`
- Result partial lists per-row errors by index, or "N imported as drafts"

## Files (exactly these)
- public/questions/index.php · view.php · form.php · save.php · status.php · delete.php · import.php · domain-options.php (Pattern A fragment: domain <option> list for a chosen exam)
- public/exams/index.php · view.php
- app/features/questions/queries.php · app/features/exams/queries.php
- app/views/questions/page.php · partials/table.php · row.php · form.php · view.php · saved.php · import.php · import-report.php
- app/views/exams/page.php · partials/exams-table.php · coverage.php

## Query functions (signatures fixed)
- find_questions(PDO, exam_id=0, domain_id=0, status='', search='', sort='updated_at', page=1): array
- find_question(PDO, int id): ?array          — joins options ordered by display_order, play stats
- insert_question(PDO, int exam_id, int domain_id, string stem, array options, int correct_index, string explanation, string difficulty, string status, string source, int created_by): array
- update_question(PDO, int id, …same explicit fields): array   — returns before+after for undo/log
- set_question_status(PDO, int id, string status): array
- delete_question(PDO, int id): bool          — refuses if the question appears in game_questions
- find_exams_with_counts(PDO): array
- find_exam_coverage(PDO, int exam_id, int game_size=10): array
- find_domains_for_exam(PDO, int exam_id): array

## Action manifest entries
Screens: the seven above. Actions: question_create, question_update, question_activate, question_deactivate, question_retire, question_delete (confirm), question_import_batch — endpoints/params/undo per docs/plan/phase-1-action-manifest.md.

## Activity log events
- screen_view (every GET), question_created, question_updated (before/after), question_status_changed, question_deleted, questions_imported (details: count, import id)

## Status vocabulary mapping (per design-decisions.md locked vocabulary)
- draft → dark · active → success · retired → secondary

## Seed content (part of this slice)
- db/090_seed_ccao_questions.sql: 69 CCAO-F questions (built), status=active, source='claude-generated 2026-08', distributed per blueprint (21/16/15/14/12/12/10 → 15/11/10/10/8/8/7 of 69), each with 4 options + explanation. Generated by the planning-class model from public Anthropic documentation; never copied from real exam items.

## Out of scope for this slice
- Scenario CRUD (S6). Multi-correct options. Question images. Any game/analytics screens.

## Open Questions (must be EMPTY before a worker starts)
- (none)

## Exemplar build notes (amendments made while building — workers follow THIS version)
- saved.php is a success banner component; save.php responds with the view screen (saved banner shown) + HX-Push-Url to the record's canonical URL. Non-HTMX saves redirect (Location) to the record.
- List sub-swaps (search/filter/pagination) are detected via the HX-Target request header equalling the results region id (`{entity}-list-results`); nav-level swaps re-render the whole screen partial.
- Exam filter change re-renders the whole screen into #page-content (repopulates the dependent domain filter); other filters swap only the results region.
- Mutations (save/status/delete/import) require admin; read screens require login.
