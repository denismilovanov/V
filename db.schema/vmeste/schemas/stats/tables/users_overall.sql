-------------------------------------------------------------
-- статистика

CREATE TABLE stats.users_overall (
    user_id integer NOT NULL PRIMARY KEY REFERENCES public.users (id) ON DELETE CASCADE ON UPDATE CASCADE,
    likes_count integer NOT NULL DEFAULT 0,
    dislikes_count integer NOT NULL DEFAULT 0,
    liked_count integer NOT NULL DEFAULT 0,
    disliked_count integer NOT NULL DEFAULT 0,
    matches_count integer NOT NULL DEFAULT 0
);

