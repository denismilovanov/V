-------------------------------------------------------------
-- жалобы

CREATE TABLE public.abuses (
    id integer NOT NULL PRIMARY KEY DEFAULT nextval('abuses_id_seq'::regclass),
    from_user_id integer NOT NULL REFERENCES public.users (id),
    to_user_id integer NOT NULL REFERENCES public.users (id),
    created_at timestamp with time zone NOT NULL DEFAULT now(),
    message text NOT NULL
);

CREATE INDEX fk_abuses_users1_idx
    ON public.abuses
    USING btree (to_user_id);

CREATE INDEX fk_abuses_users_idx
    ON public.abuses
    USING btree (from_user_id);
