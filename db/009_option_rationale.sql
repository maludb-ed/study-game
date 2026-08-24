-- 009_option_rationale: per-option "why" text. For the correct option it explains why
-- it's right; for distractors, why they're wrong. Surfaced in practice mode on a wrong
-- answer (the question-level `explanation` remains the live-game reveal text).
alter table question_options add column rationale text not null default '';
