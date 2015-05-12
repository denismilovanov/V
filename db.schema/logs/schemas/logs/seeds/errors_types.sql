
BEGIN;

DELETE FROM logs.errors_types;

INSERT INTO logs.errors_types
    VALUES
    (0, 'Для автотестов', 'FOR_TESTS'),
    (1, 'Ошибка PHP', 'PHP_ERROR'),
    (2, 'Ошибка SQL', 'SQL_ERROR'),
    (3, 'Ошибка API Вконтакте', 'VK_API_ERROR'),
    (4, 'Ошибка API Apple', 'APPLE_API_ERROR'),
    (5, 'Ошибка API Google', 'GOOGLE_API_ERROR'),
    (6, 'Ошибка FS', 'FS_ERROR'),
    (7, 'Ошибка скрипта PushLikes', 'PUSH_LIKES_ERROR');

COMMIT;
