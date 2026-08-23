-- 002_exams: the four Anthropic certifications and their blueprint domains.
-- Blueprints live as DATA: an exam revision is a row update, never a code change.
-- Weights below are from third-party summaries of the official exam guides
-- (Aug 2026); verify against the official exam-guide PDFs before relying on them.

create table exams (
    id                      bigint generated always as identity primary key,
    code                    text not null unique,     -- 'CCAO-F', 'CCDV-F', 'CCAR-F', 'CCAR-P'
    name                    text not null,
    audience                text not null default '',
    official_question_count int  not null,
    duration_minutes        int  not null default 120,
    passing_score           int  not null default 720,  -- on the 100–1000 scale
    price_usd               numeric(6,2),
    is_active               boolean not null default true,
    created_at              timestamptz not null default now(),
    updated_at              timestamptz not null default now()
);
create trigger exams_updated_at before update on exams
    for each row execute function set_updated_at();

create table domains (
    id            bigint generated always as identity primary key,
    exam_id       bigint not null references exams(id),
    name          text not null,
    weight_pct    numeric(5,2) not null check (weight_pct > 0),
    display_order smallint not null,
    created_at    timestamptz not null default now(),
    updated_at    timestamptz not null default now(),
    unique (exam_id, name),
    unique (exam_id, display_order),
    unique (id, exam_id)   -- composite FK target: a question's domain must belong to its exam
);
create trigger domains_updated_at before update on domains
    for each row execute function set_updated_at();

insert into exams (code, name, audience, official_question_count, price_usd) values
('CCAO-F', 'Claude Certified Associate, Foundations',  'Business professionals (operations, marketing, PM, education)', 60,  99.00),
('CCDV-F', 'Claude Certified Developer, Foundations',  'Software engineers building on the API, SDKs, and Agent SDK',   53, 125.00),
('CCAR-F', 'Claude Certified Architect, Foundations',  'Solution architects designing production systems',              60, 125.00),
('CCAR-P', 'Claude Certified Architect, Professional', 'Senior architects delivering production AI systems',            63, 175.00);

insert into domains (exam_id, name, weight_pct, display_order)
select e.id, d.name, d.weight_pct, d.display_order
from exams e
join (values
    ('CCAO-F', 'Output Evaluation and Validation',                    21.0, 1),
    ('CCAO-F', 'Workflow Integration and Solution Design',            16.0, 2),
    ('CCAO-F', 'Governance, Risk, and Responsible Use',               15.0, 3),
    ('CCAO-F', 'Prompting and Task Execution',                        14.0, 4),
    ('CCAO-F', 'Product and Model Selection',                         12.0, 5),
    ('CCAO-F', 'Configuration and Knowledge Management',              12.0, 6),
    ('CCAO-F', 'Troubleshooting and Optimization',                    10.0, 7),
    ('CCDV-F', 'Applications and Integration',                        33.1, 1),
    ('CCDV-F', 'Model Selection and Optimization',                    16.8, 2),
    ('CCDV-F', 'Agents and Workflows',                                14.7, 3),
    ('CCDV-F', 'Prompt and Context Engineering',                      11.0, 4),
    ('CCDV-F', 'Tools and MCPs',                                      10.6, 5),
    ('CCDV-F', 'Security and Safety',                                  8.1, 6),
    ('CCDV-F', 'Claude Code',                                          3.1, 7),
    ('CCDV-F', 'Eval, Testing, and Debugging',                         2.6, 8),
    ('CCAR-F', 'Agentic Architecture and Orchestration',              27.0, 1),
    ('CCAR-F', 'Claude Code Configuration and Workflows',             20.0, 2),
    ('CCAR-F', 'Prompt Engineering and Structured Output',            20.0, 3),
    ('CCAR-F', 'Tool Design and MCP Integration',                     18.0, 4),
    ('CCAR-F', 'Context Management and Reliability',                  15.0, 5),
    ('CCAR-P', 'Integration',                                         19.0, 1),
    ('CCAR-P', 'Solution Design and Architecture',                    17.0, 2),
    ('CCAR-P', 'Evaluation, Testing and Optimization',                16.0, 3),
    ('CCAR-P', 'Governance, Safety and Risk Management',              14.0, 4),
    ('CCAR-P', 'Stakeholder Communication and Lifecycle Management',  14.0, 5),
    ('CCAR-P', 'Claude Models, Prompting and Context Engineering',    13.0, 6),
    ('CCAR-P', 'Developer Productivity and Operational Enablement',    7.0, 7)
) as d(exam_code, name, weight_pct, display_order) on d.exam_code = e.code;
