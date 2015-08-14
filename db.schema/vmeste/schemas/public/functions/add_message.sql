CREATE OR REPLACE FUNCTION public.add_message(
    i_me_id integer,
    i_buddy_id integer,
    t_text varchar,
    b_i boolean
)
RETURNS bigint AS
$BODY$
DECLARE
    i_id bigint;
BEGIN

    INSERT INTO public.messages_new
        (me_id, buddy_id, message, i, is_new, is_read)
        VALUES (
            i_me_id,
            i_buddy_id,
            t_text,
            b_i,
            NOT b_i, -- если не я написал, то сообщение новое
            b_i -- если я писал, то сообщение мною "прочитано"
        )
        RETURNING id INTO i_id;

    UPDATE public.messages_dialogs
        SET updated_at = now(),
            last_message = t_text,
            last_message_i = b_i,
            is_new = NOT b_i -- если не я написал, то сообщение новое
        WHERE   me_id = i_me_id AND
                buddy_id = i_buddy_id;

    RETURN i_id;

END
$BODY$
    LANGUAGE plpgsql VOLATILE;


