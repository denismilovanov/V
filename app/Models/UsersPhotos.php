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

    public static function correctAvatar($avatar_url, $user_id, $sex) {
        $photos_server_url = env('PHOTOS_URL');

        if (! $avatar_url) {
            $photos = self::getUserPhotos($user_id, null, 1);
            if ($photos) {
                $avatar_url = $photos[0]['url'];
            }
        }

        if ($avatar_url) {
            if (strpos($avatar_url, 'http') !== 0) {
                $avatar_url = $photos_server_url . $avatar_url;
            }
        } else if (Users::isTestUser($user_id)) {
            if ($sex == 1) {
                $avatar_url = $photos_server_url . '/test/female1.jpg';
            } else if ($sex == 2) {
                $avatar_url = $photos_server_url . '/test/male1.jpg';
            }
        }

        return $avatar_url;
    }

    public static function getUserPhotos($user_id, $source_id = null, $limit = null) {
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

        return $photos;
    }

    public static function setPhotosVK($user_id, $photos) {
        $added = false;

        $photos_ids = [0];

        foreach ($photos as $photo) {
            if (isset($photo['url'], $photo['rank'])) {
                $url = $photo['url'];
                $rank = intval($photo['rank']);

                $photo_id = \DB::select("
                    SELECT public.upsert_photo_vk(?, ?, ?);
                ", [$user_id, $url, $rank])[0]->upsert_photo_vk;

                $photos_ids []= $photo_id;

                $added = true;
            }
        }

        // удаляем то, что отсутствует в переданном списке
        \DB::select("
            DELETE FROM public.users_photos
                WHERE   user_id = ? AND
                        source_id = 1 AND -- vk
                        id NOT IN (" . implode(', ', $photos_ids) . ")
        ", [$user_id]);

        return $added;
    }

}
