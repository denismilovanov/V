CREATE OR REPLACE FUNCTION public.get_processing_level(
    i_groups_vk_count integer,
    i_friends_vk_count integer,
    i_radius integer,
    i_distance integer
)
RETURNS integer AS
$BODY$
DECLARE
    d_result decimal;
BEGIN

    d_result := (i_groups_vk_count / 100. + i_friends_vk_count / 100. + (i_radius - i_distance) / i_radius::decimal) / 3.;

    IF d_result > 1.0 THEN
        d_result := 1.0;
    END IF;

    RETURN (d_result * 100)::integer;

END
$BODY$
    LANGUAGE plpgsql IMMUTABLE;


