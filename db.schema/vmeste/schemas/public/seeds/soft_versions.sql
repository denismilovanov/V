
BEGIN;

DELETE FROM public.soft_versions;

INSERT INTO public.soft_versions
    VALUES
    (20000, 1, now(), TRUE, '2.0.0');

COMMIT;
