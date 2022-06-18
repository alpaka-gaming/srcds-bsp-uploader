create table users
(
    id       integer not null
        constraint users_pk
            primary key autoincrement,
    username text    not null,
    email    text    not null,
    name     text    not null,
    password text    not null,
    active   integer default 1 not null
);

create unique index users_id_uindex
    on users (id);

create table sessions
(
    id         integer not null
        constraint sessions_pk
            primary key autoincrement,
    key        text    not null,
    created_at integer not null,
    expires_at integer not null,
    user_id    integer not null
        references users
            on update cascade on delete cascade,
    active     integer default 1 not null
);

create unique index sessions_id_uindex
    on sessions (id);

INSERT INTO users (id, username, name, password, active, email)
VALUES (1, 'admin@srcds.com', 'Administrator', '$2y$10$NTYVuAo4kcZUJ9.K3CmcgusiWc8d2LmLjPvWMlN1JofrzbRU31GJ2', 1, 'admin@srcds.com');
