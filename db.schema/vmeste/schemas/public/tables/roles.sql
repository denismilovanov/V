-------------------------------------------------------------
-- роли пользователей (черт знает зачем отдельно...)

CREATE TABLE public.roles (
    user_id integer NOT NULL PRIMARY KEY REFERENCES public.users(id),
    role_id integer
);
