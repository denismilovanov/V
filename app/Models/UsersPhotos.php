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

    public static function getUserPhotos($user_id) {
        $photos_url = env('PHOTOS_URL');

        $photos_raw = \DB::select("
            SELECT * FROM public.get_user_photos(?);
        ", [$user_id]);

        $photos = array();

        foreach ($photos_raw as $photo) {
            if ($photo->source_id == 0) {
                $photo->url = $photos_url . $photo->url;
            }
            $photos []= array(
                'id' => $photo->id,
                'url' => $photo->url,
                'rank' => $photo->rank,
            );
        }

        return $photos;
    }

}
