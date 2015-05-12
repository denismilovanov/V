
BEGIN;

DELETE FROM public.push_types;

INSERT INTO public.push_types
    VALUES
    (1, 'Совпадение', 'MATCH'),
    (2, 'Сообщение', 'MESSAGE');

COMMIT;
