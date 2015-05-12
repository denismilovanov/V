<?php

require_once 'bootstrap.php';

class SearchAroundTest extends TestCase {

    public function test1() {

        // имитируем авторизацию ВК, то есть получение токена

        $access = Bootstrap::getAccess($this);

        // передаем координаты

        $result = Bootstrap::getFromApi('checkIn', array(
            'latitude' => 59.935787,
            'longitude' => 30.323009,
            'key' => $access['key'],
        ));
        $this->assertEquals($result['status'], 1);

        // обновляем настройки

        $result = Bootstrap::getFromApi('setMySettings', array(
            'sex'                      => 2,
            'radius'                   => 20,
            'age_from'                 => 20,
            'age_to'                   => 30,
            'is_show_male'             => 0,
            'is_show_female'           => 1,
            'is_notification'          => 1,
            'is_notification_likes'    => 1,
            'is_notification_messages' => 1,
            'key' => $access['key'],
        ));
        $this->assertEquals($result['status'], 1);

        // ищем

        $result = Bootstrap::getFromApi('searchAround', array(
            'key' => $access['key'],
        ));
        $this->assertEquals($result['status'], 1);
        $this->assertTrue(is_array($result['users']));
    }

}
