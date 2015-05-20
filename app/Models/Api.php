<?php namespace App\Models;

class Api
{
    public static function getMethods() {
        return [
            'like' => [
                'fields' => [
                    'user_id', 'is_like',
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

        $params = $params[$method];

        $response = $client->$call_method(env('URL') . env('API_RELATIVE_URL') . '/' . $method, [
            'query' => $params + [
                'key' => $key,
            ],
        ]);

        $url = $response->getEffectiveUrl();

        if ($response->getStatusCode() == 200) {
            $response = (string) $response->getBody();
        } else {
            $response = $response->getStatusCode();
        }

        return [
            'response' => $response,
            'url' => $url,
        ];
    }
}
