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

        if (! $users_ids or ! ($users_ids = $users_ids[0]->users_ids)) {
            return;
        }

        \DB::select("
            UPDATE public.users_index AS i
                SET last_updated_at = now(),
                    popularity = uo.liked_count::numeric / (uo.liked_count + uo.disliked_count + 1),
                    friendliness = uo.likes_count::numeric / (uo.likes_count + uo.dislikes_count + 1),
                    age = COALESCE(extract('year' from age(u.bdate)), :default_ages)
                FROM    stats.users_overall AS uo,
                        users AS u
                WHERE   i.user_id IN ($users_ids) AND
                        uo.user_id = i.user_id AND
                        u.id = uo.user_id;
        ", [
            'default_ages' => 25,
        ]);

        return $users_ids;
    }

    public static function removeUser($user_id) {
        \DB::select("
            UPDATE public.users_index
                SET region_id = NULL
                WHERE user_id = ?;
        ", [$user_id]);
    }

    public static function updateAudioVkIds($user_id) {
        \DB::select("
            UPDATE public.users_profiles_vk
                SET last_audio_processed_at = now()
                WHERE user_id = ?;
        ", [$user_id]);

        $audio = VK::getUserAudioIds($user_id);
        if (! $audio) {
            return $audio;
        }

        $audio_ids = implode(", ", $audio);

        \DB::select("
            UPDATE public.users_index
                SET audio_vk_ids = array[$audio_ids]::integer[]
                WHERE user_id = ?;
        ", [$user_id]);

        return true;
    }

    public static function updateProfileVk($user_id) {
        \DB::select("
            UPDATE public.users_profiles_vk
                SET last_profile_processed_at = now()
                WHERE user_id = ?;
        ", [$user_id]);

        $profile = VK::processProfileVk($user_id);
        if (! $profile) {
            return $profile;
        }

        foreach (['universities_ids', 'activities_ids', 'interests_ids', 'books_ids', 'games_ids', 'movies_ids', 'music_ids'] as $key) {
            $$key = implode(", ", $profile[$key]);
        }

        \DB::select("
            UPDATE public.users_index
                SET universities_vk_ids = array[$universities_ids]::integer[],
                    activities_vk_ids= array[$activities_ids]::integer[],
                    interests_vk_ids= array[$interests_ids]::integer[],
                    books_vk_ids= array[$books_ids]::integer[],
                    games_vk_ids= array[$games_ids]::integer[],
                    movies_vk_ids= array[$movies_ids]::integer[],
                    music_vk_ids= array[$music_ids]::integer[]
                WHERE user_id = ?;
        ", [$user_id]);

        return true;
    }

}
