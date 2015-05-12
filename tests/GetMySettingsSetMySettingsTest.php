<?php

require_once 'bootstrap.php';

class GetMySettingsSetMySettingsTest extends TestCase {

    public function test1() {
        $pair = Bootstrap::gerMaleFemalePair();
        $test_male_id = $pair['test_male_id'];
        $male_key = $pair['male_key'];

        Bootstrap::log("male_key = $male_key");

        // запрашиваем настройки

        $result = $old_result = Bootstrap::getFromApi('getMySettings', array(
            'key' => $male_key,
            'user_id' => $test_male_id,
        ));

        $this->assertEquals($result['status'], 1);

        // устананиваем новые

        $sex = 1;
        $radius = 777;
        $age_from = 25;
        $age_to = 35;
        $is_show_male = 1;
        $is_show_female = 0;
        $is_notification = 0;
        $is_notification_likes = 0;
        $is_notification_messages = 0;

        $result = Bootstrap::getFromApi('setMySettings', array(
            'key'                      => $male_key,
            'sex'                      => $sex,
            'radius'                   => $radius,
            'age_from'                 => $age_from,
            'age_to'                   => $age_to,
            'is_show_male'             => $is_show_male,
            'is_show_female'           => $is_show_female,
            'is_notification'          => $is_notification,
            'is_notification_likes'    => $is_notification_likes,
            'is_notification_messages' => $is_notification_messages,
        ));

        $this->assertEquals($result['status'], 1);

        // запрашиваем настройки

        $result = Bootstrap::getFromApi('getMySettings', array(
            'key' => $male_key,
            'user_id' => $test_male_id,
        ));

        // сравниваем

        $this->assertEquals($result['status'], 1);
        $this->assertEquals($result['sex'], $sex);
        $this->assertEquals($result['radius'], $radius);
        $this->assertEquals($result['age_from'], $age_from);
        $this->assertEquals($result['is_show_male'] ? 1 : 0, $is_show_male);
        $this->assertEquals($result['is_show_female'] ? 1: 0, $is_show_female);
        $this->assertEquals($result['is_notification'] ? 1 : 0, $is_notification);
        $this->assertEquals($result['is_notification_likes'] ? 1: 0, $is_notification_likes);
        $this->assertEquals($result['is_notification_messages'] ? 1: 0, $is_notification_messages);

        // устананиваем старые

        $result = Bootstrap::getFromApi('setMySettings', array(
            'key'                      => $male_key,
            'sex'                      => $old_result['sex'],
            'radius'                   => $old_result['radius'],
            'age_from'                 => $old_result['age_from'],
            'age_to'                   => $old_result['age_to'],
            'is_show_male'             => $old_result['is_show_male'] ? 1 : 0,
            'is_show_female'           => $old_result['is_show_female'] ? 1 : 0,
            'is_notification'          => $old_result['is_notification'] ? 1 : 0,
            'is_notification_likes'    => $old_result['is_notification_likes'] ? 1 : 0,
            'is_notification_messages' => $old_result['is_notification_messages'] ? 1 : 0
        ));

        $this->assertEquals($result['status'], 1);

    }

}
