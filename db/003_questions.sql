-- 003_questions: the question bank. Questions belong to one exam + one domain;
-- the composite FK guarantees the domain belongs to the same exam.

create table scenarios (
    id         bigint generated always as identity primary key,
    exam_id    bigint not null references exams(id),
    title      text not null,
    body       text not null,
    status     text not null default 'draft' check (status in ('draft','active','retired')),
    created_by bigint references users(id),
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now()
);
create trigger scenarios_updated_at before update on scenarios
    for each row execute function set_updated_at();

create table questions (
    id          bigint generated always as identity primary key,
    exam_id     bigint not null references exams(id),
    domain_id   bigint not null,
    scenario_id bigint references scenarios(id),
    stem        text not null,
    explanation text not null default '',   -- shown at reveal: why the right answer is right
    difficulty  text not null default 'medium' check (difficulty in ('easy','medium','hard')),
    status      text not null default 'draft' check (status in ('draft','active','retired')),
    source      text not null default '',   -- e.g. 'claude-generated 2026-08', 'official sample'
    created_by  bigint references users(id),
    created_at  timestamptz not null default now(),
    updated_at  timestamptz not null default now(),
    foreign key (domain_id, exam_id) references domains (id, exam_id)
);
create trigger questions_updated_at before update on questions
    for each row execute function set_updated_at();
create index questions_active_by_domain_idx on questions (exam_id, domain_id) where status = 'active';

-- 2–6 options per question, exactly one is_correct in v1 (enforced in the app layer;
-- multi-correct is a schema-free future change). display_order drives the Kahoot-style
-- color/shape assignment (1=red/triangle, 2=blue/diamond, 3=yellow/circle, 4=green/square).
create table question_options (
    id            bigint generated always as identity primary key,
    question_id   bigint not null references questions(id) on delete cascade,
    option_text   text not null,
    is_correct    boolean not null default false,
    display_order smallint not null check (display_order between 1 and 6),
    created_at    timestamptz not null default now(),
    unique (question_id, display_order)
);
