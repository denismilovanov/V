<?php namespace App\Models;

use App\Models\ErrorCollector;

class UsersPhotos {

    public static function uploadPhoto($user_id, $content, $extension) {
        $content = base64_decode($content);

        $i = imagecreatefromstring($content);

        if (! $i) {
            return false;
        }

        $extension = strtolower($extension);
        if ($extension == 'jpeg') {
            $extension = 'jpg';
        }

        if (! in_array($extension, array('jpg', 'png', 'gif'))) {
            return false;
        }

        // сохраняем картинку на файловую систему

        $hash = sha1(time() . $user_id . $content);

        $rootPath = config('database.images_storage_path'); # .../i

        $userSharded = sprintf("%09d", $user_id);

        $relativeUrl =  '/' . substr($userSharded, 0, 3) .
                        '/' . substr($userSharded, 3, 3) .
                        '/' . substr($userSharded, 6, 3); # /000/012/855

        $userPath = $rootPath . $relativeUrl;

        if (! file_exists($userPath)) {
            if (! mkdir($userPath, 0755, true)) {
                ErrorCollector::addError(
                    'FS_ERROR',
                    'Не удалось создать ' . $userPath,
                    ''
                );
                return false;
            }
        }

        $tail = '/' . $hash . '.' . $extension;
        $filePath = $userPath . $tail;
        $relativeUrl .= $tail;

        if (! file_put_contents($filePath, $content) or ! file_exists($filePath)) {
            ErrorCollector::addError(
                'FS_ERROR',
                'Не удалось создать ' . $filePath,
                ''
            );
            return false;
        }

        // сохраняем картинку в базу

        return \DB::select("
            SELECT public.add_user_photo(?, 0, ?, ?, ?);
        ", [$user_id, $relativeUrl, $hash, $extension])[0]->add_user_photo;
    }

    public static function removePhoto($user_id, $photo_id) {
        $photo = \DB::select("
            DELETE FROM public.users_photos
                WHERE id = ? AND
                        user_id = ?
                RETURNING *;
        ", [$photo_id, $user_id]);

        if (! $photo or ! isset($photo[0])) {
            return false;
        }

        \Queue::push('remove_photos', $photo, 'remove_photos');

        return true;
    }

    public static function getPhotosForTests($user_id, $sex) {
        $photos_server_url = env('PHOTOS_URL');
        if ($sex == 1) {
            $photos = [];
            foreach (range(1, 10) as $num) {
                $photos []= $photos_server_url . '/test/female' . $num . '.jpg';
            }
            return $photos;
        } else if ($sex == 2) {
            return [$photos_server_url . '/test/male1.jpg'];
        }
    }

    public static function correctAvatar($avatar_url, $user_id, $sex) {
        $photos_server_url = env('PHOTOS_URL');

        if (! $avatar_url) {
            $photos = self::getUserPhotos($user_id, null, 1, $sex);
            if ($photos) {
                $avatar_url = $photos[0]['url'];
            }
        }

        if ($avatar_url) {
            if (strpos($avatar_url, 'http') !== 0) {
                $avatar_url = $photos_server_url . $avatar_url;
            }
        } else if (Users::isTestUser($user_id)) {
            $avatar_url = self::getPhotosForTests($user_id, $sex)[0];
        }

        return $avatar_url;
    }

    public static function getUserPhotos($user_id, $source_id = null, $limit = null, $sex = null) {
        $photos_url = env('PHOTOS_URL');

        if (! $limit or $limit > 100) {
            $limit = 100;
        }

        $photos_raw = \DB::select("
            SELECT *
                FROM public.users_photos
                WHERE user_id = ?
                ORDER BY rank, id
                LIMIT ?
        ", [$user_id, $limit]);

        $photos = array();

        foreach ($photos_raw as $photo) {
            if ($photo->source_id == 0) {
                $photo->url = $photos_url . $photo->url;
            }
            if ($source_id != null and $photo->source_id != $source_id) {
                continue;
            }
            $photos []= array(
                'id' => $photo->id,
                'url' => $photo->url,
                'rank' => $photo->rank,
            );
        }

        if (Users::isTestUser($user_id) and ! $photos) {
            $rank = 0;

            foreach (self::getPhotosForTests($user_id, $sex) as $url) {
                $photos []= [
                    'id' => $rank,
                    'url' => $url,
                    'rank' => $rank ++,
                ];
            }

            srand($user_id);
            shuffle($photos);
            $photos = array_slice($photos, 0, rand(2, 6));
        }

        return $photos;
    }

    public static function setPhotosVK($user_id, $photos) {
        // придет массив фотографий, у каждой указан rank - порядок в списке
        // проблема в том, что фотография в списке может дублироваться
        // в этом случае требуется поменять rank на rank удаленной
        // если дублей нет, то все будет ок
        $added = false;

        $photos_ids = [0];

        // запросим фотографии
        $photos_now = self::getUserPhotos($user_id, 1);
        $removed_photos = [];
        foreach ($photos_now as $photo) {
            $removed_photos[$photo['url']] = $photo;
        }

        // выкинем фотографии, которые остались
        foreach ($photos as $photo) {
            if (isset($removed_photos[$photo['url']])) {
                unset($removed_photos[$photo['url']]);
            }
        }
        // теперь в pn фотографии, которые исчезли

        // берем одну, так как приложение не удаляет по две
        $removed = array_pop($removed_photos);
        $rank_for_repeat = 0;
        if ($removed) {
            // ранк удаленной фотографии
            $rank_for_repeat = $removed['rank'];
        }

        $already_upserted = [];
        $repeat = false;
        $repeat_exists = false;

        foreach ($photos as $photo) {
            if (isset($photo['url'], $photo['rank'])) {
                $url = $photo['url'];
                $rank = intval($photo['rank']);

                $repeat = in_array($url, $already_upserted);
                $repeat_exists = $repeat_exists || $repeat;
                if ($rank_for_repeat and $repeat) {
                    // фотография повторяется
                    $rank = $rank_for_repeat;
                }

                $photo_id = \DB::select("
                    SELECT public.upsert_photo_vk(?, ?, ?);
                ", [$user_id, $url, $rank])[0]->upsert_photo_vk;

                $photos_ids []= $photo_id;

                $added = true;

                $already_upserted []= $url;
            }
        }

        // удаляем то, что отсутствует в переданном списке
        \DB::select("
            DELETE FROM public.users_photos
                WHERE   user_id = ? AND
                        source_id = 1 AND -- vk
                        id NOT IN (" . implode(', ', $photos_ids) . ")
        ", [$user_id]);

        // требуется перенумеровать
        if ($repeat_exists) {
            \DB::select("
                DO $$
                DECLARE
                    i_id bigint;
                    i_rank integer := 1;
                BEGIN

                    FOR i_id IN SELECT * FROM public.users_photos WHERE user_id = ? ORDER BY rank
                    LOOP
                        UPDATE public.users_photos
                            SET rank = i_rank
                            WHERE id = i_id;

                        i_rank := i_rank + 1;
                    END LOOP;

                END;$$;
            ", [$user_id]);
        }

        return $added;
    }

}
