<?php namespace App\Models;

class Api
{
    public static function getMethods() {
        return [
            'checkIn' => [
                'fields' => [
                    'latitude', 'longitude',
                ],
                'method' => 'get',
            ],
            'searchAround' => [
                'fields' => [
                    'limit',
                ],
                'method' => 'get',
            ],
            'getUserProfile' => [
                'fields' => [
                    'user_id',
                ],
                'method' => 'get',
            ],
            'like' => [
                'fields' => [
                    'user_id', 'is_like', 'weight_level',
                ],
                'method' => 'get',
            ],
            'sendMessageToUser' => [
                'fields' => [
                    'user_id', 'text',
                ],
                'method' => 'get',
            ],
            'getMyMessages' => [
                'fields' => [
                ],
                'method' => 'get',
            ],
            'getMessagesWithUser' => [
                'fields' => [
                    'user_id',
                ],
                'method' => 'get',
            ],
            'getMySettings' => [
                'fields' => [
                ],
                'method' => 'get',
            ],
            'setMySettings' => [
                'fields' => [
                    'sex', 'age_from', 'age_to', 'radius', 'is_show_male', 'is_show_female',
                    'is_notification', 'is_notification_likes', 'is_notification_messages',
                ],
                'method' => 'get',
            ],

        ];
    }

    public static function callApiMethod($user_id, $method, $params) {
        $client = new \GuzzleHttp\Client();

        $key = Users::getExistingAccessKey($user_id);

        $call_method = self::getMethods()[$method]['method'];

        $params = isset($params[$method]) ? $params[$method] : [];

        $response = $client->$call_method(env('URL') . env('API_RELATIVE_URL') . '/' . $method, [
            'query' => $params + [
                'key' => $key,
            ],
            'verify' => false,
        ]);

        $url = $response->getEffectiveUrl();

        if ($response->getStatusCode() == 200) {
            $response = (string) $response->getBody();
            $response = json_decode($response);
            $response = json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } else {
            $response = $response->getStatusCode();
        }

        return [
            'response' => $response,
            'url' => $url,
        ];
    }
}
