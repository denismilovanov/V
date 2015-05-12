CREATE OR REPLACE FUNCTION public.send_broadcast_message__test(message_text text)
 RETURNS void
 LANGUAGE plpgsql
AS $function$
DECLARE
  user_item RECORD;
BEGIN

	FOR user_item IN
SELECT DISTINCT ON (users.id) users.id
	    FROM users
	    INNER JOIN users_devices ON users_devices.user_id = users.id
	    WHERE 
		1 = 1 
		AND users.id <> 1
--		AND users_devices.device_token IS NOT NULL 
		AND users.id IN (10470)
	LOOP
		INSERT INTO messages (from_user_id, to_user_id, message)
			VALUES(1, user_item.id, message_text);
	    BEGIN	
		INSERT INTO messages_last(from_user_id, to_user_id, message, is_new, is_send) 
			VALUES (1, user_item.id, message_text, true, false);
	      CONTINUE;
	    EXCEPTION WHEN unique_violation THEN
		UPDATE messages_last
		      SET is_new = true, is_send = false
		      WHERE 
			from_user_id = 1 
			AND to_user_id = user_item.id;
	    END;
	    /**/
	END LOOP;
END;
$function$
