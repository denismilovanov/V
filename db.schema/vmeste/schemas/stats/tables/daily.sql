
CREATE TABLE stats.daily (
    date date NOT NULL PRIMARY KEY,
    active_males_count integer NOT NULL DEFAULT 0,
    active_females_count integer NOT NULL DEFAULT 0,
    male_likes_female_count integer NOT NULL DEFAULT 0,
    female_likes_male_count integer NOT NULL DEFAULT 0,
    male_likes_male_count integer NOT NULL DEFAULT 0,
    female_likes_female_count integer NOT NULL DEFAULT 0,
    likes_count integer NOT NULL DEFAULT 0,
    matches_count integer NOT NULL DEFAULT 0
);
