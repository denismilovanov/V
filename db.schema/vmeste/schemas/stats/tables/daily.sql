
CREATE TABLE stats.daily (
    date date NOT NULL PRIMARY KEY,
    active_males_count integer NOT NULL DEFAULT 0,
    active_females_count integer NOT NULL DEFAULT 0,
    male_likes_female_count integer NOT NULL DEFAULT 0,
    female_likes_male_count integer NOT NULL DEFAULT 0,
    male_likes_male_count integer NOT NULL DEFAULT 0,
    female_likes_female_count integer NOT NULL DEFAULT 0,
    likes_count integer NOT NULL DEFAULT 0,
    matches_count integer NOT NULL DEFAULT 0,
    -- 1 мэтч, 2-5 мэтчей, 5-10 мэтчей, более 10 мэтчей
    matches_group1_count integer NOT NULL DEFAULT 0,
    matches_group2_count integer NOT NULL DEFAULT 0,
    matches_group3_count integer NOT NULL DEFAULT 0,
    matches_group4_count integer NOT NULL DEFAULT 0
);
