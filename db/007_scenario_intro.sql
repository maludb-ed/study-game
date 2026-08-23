-- S6 (pre-approved in the build spec): a live game showing a scenario intro screen
-- records which scenario it is introducing; NULL = normal question flow.
ALTER TABLE games ADD COLUMN scenario_intro_for bigint REFERENCES scenarios(id);
