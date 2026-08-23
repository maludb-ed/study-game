-- 004_games: live sessions. The game row is the authoritative state machine;
-- state changes only on host action or timer expiry. All timing is server-clocked.

create table games (
    id                   bigint generated always as identity primary key,
    exam_id              bigint not null references exams(id),
    host_user_id         bigint not null references users(id),
    pin                  text not null,               -- 6-digit numeric, server-generated
    state                text not null default 'lobby'
        check (state in ('lobby','question','reveal','leaderboard','podium','ended','aborted')),
    current_position     int not null default 0,      -- 0 = not started; else position in game_questions
    question_count       int not null check (question_count between 1 and 100),
    seconds_per_question int not null default 20 check (seconds_per_question between 5 and 240),
    streak_bonus         boolean not null default true,
    question_started_at  timestamptz,                 -- for the current question
    question_deadline    timestamptz,
    created_at           timestamptz not null default now(),
    started_at           timestamptz,
    ended_at             timestamptz,
    updated_at           timestamptz not null default now()
);
create trigger games_updated_at before update on games
    for each row execute function set_updated_at();
-- A PIN is unique among live games only; ended games free their PIN.
create unique index games_active_pin_uniq on games (pin) where state not in ('ended','aborted');

-- Snapshot of the blueprint-weighted draw, in play order. Bank edits after the
-- draw never affect a played game.
create table game_questions (
    id              bigint generated always as identity primary key,
    game_id         bigint not null references games(id) on delete cascade,
    question_id     bigint not null references questions(id),
    position        int not null,
    points_possible int not null default 1000,
    started_at      timestamptz,
    deadline        timestamptz,
    unique (game_id, position),
    unique (game_id, question_id)
);

create table game_players (
    id           bigint generated always as identity primary key,
    game_id      bigint not null references games(id) on delete cascade,
    user_id      bigint references users(id),   -- claimed roster identity (optional, feeds analytics)
    nickname     text not null,
    player_token text not null unique,          -- random 32-byte hex in the player's cookie; rejoin key
    joined_at    timestamptz not null default now(),
    kicked_at    timestamptz,
    final_score  int,
    final_rank   int,
    unique (game_id, nickname)
);
create index game_players_user_idx on game_players (user_id);

-- Scoring (server-side only):
--   response_ms = clamp(now() - question_started_at, 0, seconds*1000); late/after-deadline rejected.
--   points = 1000 when response_ms < 500, else floor((1 - (response_ms / (seconds*1000)) / 2) * 1000).
--   streak bonus (if games.streak_bonus): +100 when this correct answer makes streak_after >= 2.
create table answers (
    id               bigint generated always as identity primary key,
    game_player_id   bigint not null references game_players(id) on delete cascade,
    game_question_id bigint not null references game_questions(id) on delete cascade,
    option_id        bigint not null references question_options(id),
    is_correct       boolean not null,
    response_ms      int not null check (response_ms >= 0),
    points_awarded   int not null default 0,
    streak_after     int not null default 0,
    answered_at      timestamptz not null default now(),
    unique (game_player_id, game_question_id)
);
create index answers_game_question_idx on answers (game_question_id);
