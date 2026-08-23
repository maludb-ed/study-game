-- 005_activity_log: the emission point for activity memory. Every screen entry,
-- record change, and game action writes a row; an ingestion job tails this table
-- into MaluDB (activity memory cannot be backfilled — this exists from day one).

create table activity_log (
    id          bigint generated always as identity primary key,
    occurred_at timestamptz not null default now(),         -- when
    actor_type  text not null check (actor_type in ('user','player','system','agent')),
    actor_id    bigint,                                     -- who: users.id or game_players.id
    actor_label text not null default '',                   -- display name/nickname at the time
    action      text not null,                              -- what: 'question_created', 'game_advanced', 'screen_view', …
    screen      text not null default '',                   -- where: screen id from the action manifest
    entity      text not null default '',                   -- which: table name
    entity_id   bigint,
    game_id     bigint,                                     -- denormalized for game-timeline questions
    before_data jsonb,                                      -- changed fields only
    after_data  jsonb,
    details     jsonb,                                      -- anything else (e.g. utterance for agent actions)
    ip          inet
);
create index activity_log_occurred_idx on activity_log (occurred_at desc);
create index activity_log_entity_idx   on activity_log (entity, entity_id);
create index activity_log_actor_idx    on activity_log (actor_type, actor_id, occurred_at desc);
create index activity_log_game_idx     on activity_log (game_id) where game_id is not null;

-- Bookmark for the MaluDB ingestion job: reads rows with id > last_ingested_id,
-- ships them, advances the cursor. Runs continuously (systemd timer/service).
-- MaluDB's own extension/connection setup is host-specific and happens at deploy.
create table activity_ingest_cursor (
    id               smallint primary key default 1 check (id = 1),
    last_ingested_id bigint not null default 0,
    updated_at       timestamptz not null default now()
);
insert into activity_ingest_cursor (last_ingested_id) values (0);
