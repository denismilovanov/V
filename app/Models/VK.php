<?php namespace App\Models;

class VK
{
    private static function getAccessToken() {
        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->get('https://oauth.vk.com/access_token?' .
                'client_id=' . env('VK_APP_ID') .
                '&client_secret=' . env('VK_APP_SECRET') .
                '&v=' . env('VK_API_VERSION') .
                '&grant_type=client_credentials');

            $result = (string) $response->getBody();

            $result_array = json_decode($result, 'assoc');

            if ($result_array === null or ! isset($result_array['access_token'])) {
                throw new \Exception('Не удалось декодировать: ' . $result);
            }

            return $result_array['access_token'];
        } catch (\Exception $e) {
            ErrorCollector::addError(
                'VK_API_ERROR',
                '',
                $e->getMessage()
            );
            return null;
        }
    }

    public static function checkVKAccessToken($access_token, $vk_id) {
        $server_access_token = self::getAccessToken();

        try {
            //GAUGE('checkVKAccessToken');

            $client = new \GuzzleHttp\Client();
            $response = $client->get('https://api.vk.com/method/secure.checkToken.json', [
                'query' => [
                    'access_token' => $server_access_token,
                    'client_id' => env('VK_APP_ID'),
                    'client_secret' => env('VK_APP_SECRET'),
                    'token' => $access_token,
                ],
            ]);

            $result = (string) $response->getBody();

            $result_array = json_decode($result, 'assoc');

            if ($result_array === null) {
                throw new \Exception('Не удалось декодировать: ' . $result);
            }

            if (! isset($result_array['response']) or
                ! isset($result_array['response']['success']) or
                $result_array['response']['success'] != '1')
            {
                throw new \Exception(json_encode($result_array, JSON_UNESCAPED_UNICODE));
            } else {
                // проверим, что токен соответствует юзеру
                return $result_array['response']['user_id'] === (int) $vk_id;
            }
        } catch (\Exception $e) {
            ErrorCollector::addError(
                'VK_API_ERROR',
                '',
                $e->getMessage()
            );
            return false;
        }

        return true;
    }

    public static function getUserAudioIds($user_id) {
        $user = Users::findById($user_id);

        if (! $user or ! $user->vk_access_token) {
            return [];
        }

        $client = new \GuzzleHttp\Client();
        $response = $client->get('https://api.vk.com/method/audio.get.json', [
            'query' => [
                'access_token' => $user->vk_access_token,
                'owner_id' => $user->vk_id,
            ],
        ]);

        $result = (string) $response->getBody();
        $result_array = @ json_decode($result, 'assoc')['response'];

        if (! $result_array) {
            return [];
        }

        array_shift($result_array);

        // будем действовать совсем уж напролом!
        // оптимизацию оставим на потом

        // сначала вставим всех исполнителей, про которых мы ничего не знаем
        foreach ($result_array as $audio) {
            \DB::select("
                INSERT INTO vk.audio_artists_vk
                    (name)
                    SELECT :artist
                        WHERE NOT EXISTS (
                            SELECT 1
                                FROM vk.audio_artists_vk
                                WHERE vk.prepare(name) = vk.prepare(:artist)
                        );
            ", [
                'artist' => $audio['artist'],
            ]);
        }

        // теперь вставим новые композиции
        foreach ($result_array as $audio) {
            \DB::select("
                WITH artist_id AS (
                    SELECT id
                        FROM vk.audio_artists_vk
                        WHERE vk.prepare(name) = vk.prepare(:artist) --uniq
                )
                INSERT INTO vk.audio_vk
                    (artist_id, name)
                    SELECT artist_id.id, :name
                        FROM artist_id
                        WHERE NOT EXISTS (
                            SELECT 1
                                FROM vk.audio_vk AS a
                                WHERE   a.artist_id = artist_id.id AND
                                        vk.prepare(a.name) = vk.prepare(:name)
                        )
                    RETURNING id;
            ", [
                'artist' => $audio['artist'],
                'name' => $audio['title'],
            ]);
        }

        // теперь извлечем композии для текущего пользователя
        foreach ($result_array as $audio) {
            $audio_ids []= \DB::select("
                WITH artist_id AS (
                    SELECT id
                        FROM vk.audio_artists_vk
                        WHERE vk.prepare(name) = vk.prepare(:artist) --uniq
                )
                SELECT a.id
                    FROM vk.audio_vk AS a, artist_id
                    WHERE   a.artist_id = artist_id.id AND
                            vk.prepare(a.name) = vk.prepare(:name);
            ", [
                'artist' => $audio['artist'],
                'name' => $audio['title'],
            ])[0]->id;
        }

        // все ок
        return $audio_ids;
    }
}
