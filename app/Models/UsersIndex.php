<?php namespace App\Models;

class UsersIndex {

    public static function updateBatch() {
        $users_ids = \DB::select("
            WITH users_ids AS (
                SELECT user_id
                    FROM public.users_index
                    WHERE last_updated_at < now() - interval '1 day'
                    ORDER BY last_updated_at
                    LIMIT 500
            )
            SELECT string_agg(user_id::varchar, ',') AS users_ids
                FROM users_ids;
        ");

        if (! $users_ids) {
            return;
        }

        $users_ids = $users_ids[0]->users_ids;

        \DB::select("
            UPDATE public.users_index AS i
                SET last_updated_at = now(),
                    popularity = uo.liked_count / (uo.liked_count + uo.disliked_count + 1),
                    friendliness = uo.likes_count / (uo.likes_count + uo.dislikes_count + 1),
                    age = extract('year' from age(u.bdate))
                FROM    stats.users_overall AS uo,
                        users AS u
                WHERE   i.user_id IN ($users_ids) AND
                        uo.user_id = i.user_id AND
                        u.id = uo.user_id;
        ");

        return $users_ids;
    }

}
