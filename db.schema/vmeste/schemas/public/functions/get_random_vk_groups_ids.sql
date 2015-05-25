CREATE OR REPLACE FUNCTION public.get_random_vk_groups_ids(
    i_limit integer
)
RETURNS integer[] AS
$BODY$
DECLARE
    ai_result integer[];
BEGIN

    WITH g AS (

        SELECT unnest(array[
            1,3305,40004,59027,193736,382321,1015651,1221700,1224089,1719791,2158488,2341630,4240560,5211185,
            5425797,5804997,6136139,6240646,6594530,6825591,7381536,7468865,7936490,8003142,8689353,9257275,9901762,
            9948570,10009135,10065204,10740309,10848598,11518480,11608706,12103207,12851571,12928058,13587210,
            14060510,14362913,14696546,14801674,15108470,15180791,15199107,15596970,16750222,16971037,
            7070637,17157755,17177399,17189573,18006058,18402959,19218751,20142137,20320244,20363494,21198711,
            21577029,22798006,22822305,23177512,23294824,23530727,23530818,23731702,25275670,25321310,25388481,
            25787080,25850251,26168543,26201892,26776509,27364306,27579984,27938604,28202415,28387068,28551727,
            28596638,28968152,29246653,29302425,29689780,29694340,30011765,30666517,30868069,31016558,31188165,
            31554488,32678621,32878584,34132960,35173531,35325859,36338110,36498847,36508769,36595078,37437348,37579890,
            39135287,40529013,40766972,41308662,41600377,41610263,41633702,43605395,43658654,45121868,45353280,45455068,
            46719536,48074454,48210134,48668307,48675425,50911295,53638034,54061894,55031798,55490033,56545744,56607481,
            57483232,57950162,59199907,60435296,60443833,66232343,69490176,79868985,82055876,84941077
        ]) AS id
        ORDER BY random()
        LIMIT i_limit

    )
    SELECT array_agg(g.id) INTO ai_result
        FROM g;


    ai_result := COALESCE(ai_result, array[]::integer[]);

    ai_result := ai_result + array[2,3,4,5,6,7,8,9,10];

    RETURN ai_result;

END
$BODY$
    LANGUAGE plpgsql VOLATILE;


