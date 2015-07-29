CREATE OR REPLACE FUNCTION vk.prepare(
    s_name varchar
)
RETURNS varchar AS
$BODY$
DECLARE

BEGIN

    RETURN lower(regexp_replace(s_name, '[^A-ZА-Яa-zа-я\d]', '', 'g'));

END
$BODY$
    LANGUAGE plpgsql IMMUTABLE;


