-- auto-generated definition
create table users
(
    id       INTEGER not null
        constraint users_pk
            primary key autoincrement,
    username TEXT    not null,
    name     TEXT    not null,
    password TEXT    null,
    active   INTEGER default 1 not null,
    email    TEXT    not null
);

create unique index users_id_uindex
    on users (id);

-- auto-generated definition
create table sessions
(
    id         text    not null
        constraint sessions_pk
            primary key,
    created_at integer not null,
    expires_at integer not null,
    user_id    integer not null
        references users
            on update cascade on delete cascade,
    active     integer default 1 not null
);

create unique index sessions_key_uindex
    on sessions (id);

-- auto-generated definition
create table uploads
(
    id         integer not null
        constraint uploads_pk
            primary key autoincrement,
    created_at integer not null,
    map_name   text    not null,
    session_id text    not null
        references sessions
            on update cascade on delete cascade
);

create unique index uploads_id_uindex
    on uploads (id);

