----------------------------------------------------------------------------
--

CREATE OR REPLACE FUNCTION public.get_access_key(
    i_user_id integer,
    s_device_token varchar,
    i_device_type integer,
    i_soft_version integer
)
    RETURNS varchar AS
$BODY$
DECLARE
    s_key varchar;
BEGIN

    IF s_device_token = '' THEN
        s_device_token := NULL;
    END IF;

    s_key := encode(gen_random_bytes(20), 'hex');

    UPDATE public.users_devices
        SET key = s_key,
            device_token = s_device_token,
            soft_version = i_soft_version,
            updated_at = now()
        WHERE   user_id = i_user_id AND
                device_type = i_device_type;


    IF NOT FOUND THEN
        BEGIN
            INSERT INTO public.users_devices
                (user_id, key, created_at, updated_at, device_token, device_type, soft_version)
                VALUES (
                    i_user_id,
                    s_key,
                    now(), now(),
                    s_device_token,
                    i_device_type,
                    i_soft_version
                );
        EXCEPTION WHEN unique_violation THEN
            UPDATE public.users_devices
                SET key = s_key,
                    device_token = s_device_token,
                    soft_version = i_soft_version,
                    updated_at = now()
                WHERE   user_id = i_user_id AND
                        device_type = i_device_type;
        END;
    END IF;

    RETURN s_key;

END
$BODY$
    LANGUAGE plpgsql VOLATILE;

