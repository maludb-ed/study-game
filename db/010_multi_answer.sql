-- 010_multi_answer: support questions with 2+ correct options (all-or-nothing scoring).
-- A player's answer can now be a SET of options. The one-row-per-answer model stays
-- (answers keeps score/timing/streak); the selected set lives in answer_options.
-- option_id stays populated for single-select answers (back-compat) and is NULL for
-- multi-select answers.
alter table answers alter column option_id drop not null;

create table answer_options (
    answer_id bigint not null references answers(id) on delete cascade,
    option_id bigint not null references question_options(id),
    primary key (answer_id, option_id)
);
create index answer_options_option_idx on answer_options (option_id);
