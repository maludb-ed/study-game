-- 001_auth: users, external identities, TOTP 2FA, one-time tokens, login throttling.
-- Shapes per php-session-auth skill references (google-signin.md, totp-2fa.md).

create table users (
    id                 bigint generated always as identity primary key,
    email              text        not null unique,   -- stored normalized: strtolower(trim())
    email_verified_at  timestamptz,
    password_hash      text,                          -- null = external-identity-only account
    display_name       text        not null,
    role               text        not null default 'member' check (role in ('member','admin')),
    totp_secret        text,                          -- encrypted at rest (libsodium secretbox, key in env)
    totp_enabled_at    timestamptz,                   -- null = 2FA off
    totp_last_timestep bigint,                        -- replay guard
    created_at         timestamptz not null default now(),
    updated_at         timestamptz not null default now()
);
create trigger users_updated_at before update on users
    for each row execute function set_updated_at();

create table auth_identities (
    id                bigint generated always as identity primary key,
    user_id           bigint not null references users(id) on delete cascade,
    provider          text   not null,                -- 'google'
    provider_user_id  text   not null,                -- Google's stable 'sub' claim
    email_at_provider text   not null,
    created_at        timestamptz not null default now(),
    unique (provider, provider_user_id)
);

create table totp_recovery_codes (
    id         bigint generated always as identity primary key,
    user_id    bigint not null references users(id) on delete cascade,
    code_hash  text   not null,                       -- password_hash() of the code
    used_at    timestamptz,
    created_at timestamptz not null default now()
);

create table user_tokens (
    id         bigint generated always as identity primary key,
    user_id    bigint not null references users(id) on delete cascade,
    purpose    text   not null check (purpose in ('password_reset','email_verify')),
    token_hash text   not null,
    expires_at timestamptz not null,
    used_at    timestamptz,
    created_at timestamptz not null default now()
);
create index user_tokens_user_purpose_idx on user_tokens (user_id, purpose);

-- Brute-force throttle: per-account AND per-IP, for every credential-shaped surface.
create table login_attempts (
    id           bigint generated always as identity primary key,
    email        text,                                -- normalized; null when only IP applies (pin_join)
    ip           inet   not null,
    kind         text   not null default 'password'
                 check (kind in ('password','totp','google','password_reset','pin_join')),
    succeeded    boolean not null,
    attempted_at timestamptz not null default now()
);
create index login_attempts_email_idx on login_attempts (email, attempted_at desc);
create index login_attempts_ip_idx    on login_attempts (ip, attempted_at desc);
