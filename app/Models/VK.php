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
}
