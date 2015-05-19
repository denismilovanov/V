<?php namespace App\Models;

class Api
{
    public static function getMethods() {
        return [
            [
                'name' => 'sendMessageToUser',
                'fields' => [
                    'user_id', 'text',
                ],
            ],
        ];
    }
}
