CREATE OR REPLACE FUNCTION public.get_random_vk_friends_ids(
    i_limit integer
)
RETURNS integer[] AS
$BODY$
DECLARE
    ai_result integer[];
BEGIN

    WITH f AS (

        SELECT unnest(array[
            1828,4249,11153,16575,19727,19798,32866,77786,78630,88527,101948,125983,148709,152685,175236,
            184920,189314,223770,234439,301748,308890,340314,362563,407707,485804,525594,566614,691697,
            776045,780618,955923,1072521,1333927,1944461,5208930,5332523,5722411,5801229,6133705,7823588,
            8924749,10041745,11352487,11447021,11477340,13469956,15058424,15545571,16027755,17603852,18750542,
            23726259,24622309,53112973,58977956,58985308,75263312,76224665,76526089,85748995,87152084,91278759,
            93451227,109485536,130335475,141493879,141763365,144329989,146850521,155138330,169936928,196045269,
            198145169,225242647,240180863,240607598,241759052,245400859,246704147,249038675,256365903,259354623,
            263108592,264193283,265605973,296385881
        ]) AS id
        ORDER BY random()
        LIMIT i_limit

    )
    SELECT array_agg(f.id) INTO ai_result
        FROM f;


    ai_result := COALESCE(ai_result, array[]::integer[]);

    ai_result := ai_result + array[1,2,3,4,5,6,7,8,9,10];

    RETURN ai_result;

END
$BODY$
    LANGUAGE plpgsql VOLATILE;


