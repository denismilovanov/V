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
