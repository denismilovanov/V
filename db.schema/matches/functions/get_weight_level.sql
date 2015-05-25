CREATE OR REPLACE FUNCTION public.get_weight_level(
    i_mutual_groups_vk_count integer,
    i_mutual_friends_vk_count integer
)
RETURNS integer AS
$BODY$
DECLARE
    d_result decimal;
BEGIN

    d_result := (i_mutual_groups_vk_count / 10. + i_mutual_friends_vk_count / 5.) / 2.;

    IF d_result > 1.0 THEN
        d_result := 1.0;
    END IF;

    RETURN (d_result * 100)::integer;

END
$BODY$
    LANGUAGE plpgsql IMMUTABLE;


