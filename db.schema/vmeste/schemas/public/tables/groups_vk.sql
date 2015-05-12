-------------------------------------------------------------
-- группы ВК

CREATE TABLE groups_vk (
    id integer NOT NULL PRIMARY KEY DEFAULT nextval('groups_vk_id_seq'::regclass),
    name character varying(255),
    photo_url character varying(255)
);
