
BEGIN;

DELETE FROM public.photos_sources;

INSERT INTO public.photos_sources
    VALUES
    (0, 'Закачаны пользователем', 'user'),
    (1, 'ВКонтакте', 'vk');

COMMIT;
