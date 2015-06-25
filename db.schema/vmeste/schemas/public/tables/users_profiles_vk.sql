-------------------------------------------------------------
--

CREATE TABLE public.users_profiles_vk (
    -- пользователь
    user_id integer NOT NULL REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    --
    occupation text NULL DEFAULT NULL,
    activities text NULL DEFAULT NULL,
    interests text NULL DEFAULT NULL,

    music text NULL DEFAULT NULL,
    movies text NULL DEFAULT NULL,
    tv text NULL DEFAULT NULL,

    books text NULL DEFAULT NULL,
    games text NULL DEFAULT NULL,

    quotes text NULL DEFAULT NULL,
    personal jsonb NULL DEFAULT NULL
);
