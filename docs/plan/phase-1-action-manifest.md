# Phase 1 — Action manifest

The routing table for the chat command bar (chat-actions skill): the screen registry
(`navigate` resolves against it) and the action registry (each action = one tool on the
localhost-only `studygame_actions_mcp`, calling the app's own PHP endpoints with a signed
action token). A screen or action missing here is unreachable by voice — unfinished design.

## Screen registry

| Screen id | Canonical URL | When the user wants… |
|---|---|---|
| dashboard | `/` | the home screen: next game night, latest results, readiness snapshot |
| question-list | `/questions/` | to browse, search, or filter the question bank |
| question-add | `/questions/new` | to write a new question |
| question-edit | `/questions/{id}/edit` | to change an existing question or its options |
| question-view | `/questions/{id}` | to read one question with its options, explanation, and play stats |
| question-import | `/questions/import` | to bulk-import a JSON batch of generated questions |
| scenario-list | `/scenarios/` | to browse scenario rounds (CCAR-F format) |
| scenario-add | `/scenarios/new` | to write a new scenario |
| scenario-edit | `/scenarios/{id}/edit` | to change a scenario's text, exam, or status |
| scenario-view | `/scenarios/{id}` | to read a scenario and its linked questions |
| exam-list | `/exams/` | to see the four certifications and blueprint coverage |
| exam-view | `/exams/{id}` | one exam's domains, weights, and bank coverage |
| game-new | `/games/new` | to set up a new game night (pick exam, settings) |
| game-list | `/games/` | past and active games |
| game-host | `/games/{id}/host` | the host console: lobby control and question advancing (the projected screen) |
| game-report | `/games/{id}` | the report of a finished game: podium, scores, per-question results |
| join | `/join` | **(public)** to enter a PIN and nickname and get into a game |
| play | `/play` | **(public)** the player's in-game screen: answer buttons |
| analytics-group | `/analytics/` | group readiness: weak domains per exam, trends |
| analytics-member | `/analytics/members/{id}` | one member's exam×domain readiness grid |
| drill-list | `/analytics/drill` | the most-missed questions to review together |
| ama | `/ama` | to ask the assistant a full question about the group's data |
| settings-profile | `/settings/profile` | name, email, password |
| settings-2fa | `/settings/2fa` | to enroll or disable the authenticator app |
| settings-mcp | `/settings/mcp` | MCP endpoint URLs and access tokens for their own AI tools |

Public screens (`join`, `play`) are session-scoped to a `game_players.player_token` cookie —
no login, no command bar (players get game chrome only).

## Action registry

Policy (locked): creates/updates execute immediately with an Undo control; destructive
actions always confirm; ambiguity asks one short question.

| Action | Endpoint | Params | Undo | Confirm? |
|---|---|---|---|---|
| question_create | POST `/questions/save.php` | exam_code, domain, stem, options[2..6] (one correct), explanation, difficulty, status=draft | delete the created draft | no |
| question_update | POST `/questions/save.php` (id) | id + any field above | restore before-values from the action result | no |
| question_activate | POST `/questions/status.php` | id, status=active | set back to draft | no |
| question_retire | POST `/questions/status.php` | id, status=retired | restore prior status | no |
| question_delete | POST `/questions/delete.php` | id (drafts only; played questions retire instead) | none — hence confirm | **yes** |
| question_import_batch | POST `/questions/import.php` | json payload (validated per import schema) | delete the batch (import id) | no |
| scenario_create | POST `/scenarios/save.php` | exam_code, title, body, status=draft | delete the created draft | no |
| scenario_update | POST `/scenarios/save.php` (id) | id + any field above | restore before-values from the action result | no |
| scenario_retire | POST `/scenarios/status.php` | id, status=retired | restore prior status | no |
| scenario_delete | POST `/scenarios/delete.php` | id (drafts with no linked questions) | none — hence confirm | **yes** |
| game_create | POST `/games/save.php` | exam_code, question_count=10, seconds_per_question=20, streak_bonus=true | abort the lobby game | no |
| game_advance | POST `/games/{id}/advance.php` | expected_state (guard vs double-fire) | none — a live game only moves forward | no (host console button) |
| game_kick_player | POST `/games/{id}/kick.php` | player nickname | none (rejoin is possible) | **yes** |
| game_abort | POST `/games/{id}/abort.php` | id | none | **yes** |
| mcp_token_create | POST `/settings/mcp/save.php` | label, server | revoke the token | no |
| mcp_token_revoke | POST `/settings/mcp/revoke.php` | id | none | **yes** |
| navigate | (registry above) | screen, params | n/a | no |
| undo_last | (inverse from manifest + activity log) | — | n/a | no |

Notes: `game_advance` exists for the host console UI and voice ("next question"); it is
host-only and validated against the state machine (see the game-loop build spec).
Player answering is NOT an action-manifest action — players have no command bar.
