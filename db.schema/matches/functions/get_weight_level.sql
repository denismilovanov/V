CREATE OR REPLACE FUNCTION public.get_weight_level(
    i_common_groups_vk_count integer,
    i_common_friends_vk_count integer,
    i_radius integer,
    i_distance integer
)
RETURNS integer AS
$BODY$
DECLARE
    d_result decimal(8, 2);
    ad_weights decimal(3, 2)[] := array[0.45, 0.45, 0.1];
BEGIN

    d_result := ad_weights[1] * i_common_groups_vk_count / 10. +
                ad_weights[2] * i_common_friends_vk_count / 5. +
                ad_weights[3] * (i_radius - i_distance) / i_radius::decimal;

    IF d_result > 1.00 THEN
        RETURN 100;
    ELSIF d_result < 0.00 THEN
        RETURN 0;
    END IF;

    RETURN (d_result * 100)::integer;

END
$BODY$
    LANGUAGE plpgsql IMMUTABLE;


