-------------------------------------------------------------
-- статистика

CREATE TABLE stats.users_overall (
    user_id integer NOT NULL PRIMARY KEY REFERENCES public.users (id),
    likes_count integer NOT NULL DEFAULT 0,
    dislikes_count integer NOT NULL DEFAULT 0,
    liked_count integer NOT NULL DEFAULT 0,
    disliked_count integer NOT NULL DEFAULT 0
);

