-- 000_base: shared plumbing. PostgreSQL 17.
create function set_updated_at() returns trigger language plpgsql as $$
begin
    new.updated_at := now();
    return new;
end;
$$;
