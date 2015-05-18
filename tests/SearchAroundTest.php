<?php

require_once 'bootstrap.php';

use \App\Models\UsersGroupsVk;
use \App\Models\GroupsVk;
use \App\Models\Likes;

class SearchAroundTest extends TestCase {

    public function test1() {

        $pair = Bootstrap::gerMaleFemalePair();
        $test_female_id = $pair['test_female_id'];
        $test_male_id = $pair['test_male_id'];
        $male_key = $pair['male_key'];
        $female_key = $pair['female_key'];

        Bootstrap::log("male_key = $male_key, female_key = $female_key");

        // удаляем лайки между ними

        Likes::deleteAllBetween($test_male_id, $test_female_id);

        // передаем координаты парня и девушки

        $result = Bootstrap::getFromApi('checkIn', array(
            'latitude' => 59.935787,
            'longitude' => 30.323009,
            'key' => $male_key,
        ));
        $this->assertEquals($result['status'], 1);

        $result = Bootstrap::getFromApi('checkIn', array(
            'latitude' => 59.935787,
            'longitude' => 30.323009,
            'key' => $female_key,
        ));
        $this->assertEquals($result['status'], 1);

        // сделаем им одинаковый набор групп

        $groups_ids = GroupsVk::getRandomGroupsIds(200);

        UsersGroupsVk::setUserGroupsVk(
            $test_male_id,
            $groups_ids
        );

        UsersGroupsVk::setUserGroupsVk(
            $test_female_id,
            $groups_ids
        );

        // обновляем им настройки

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
            'key' => $male_key,
        ));
        $this->assertEquals($result['status'], 1);

        $result = Bootstrap::getFromApi('setMySettings', array(
            'sex'                      => 1,
            'radius'                   => 20,
            'age_from'                 => 20,
            'age_to'                   => 30,
            'is_show_male'             => 1,
            'is_show_female'           => 0,
            'is_notification'          => 1,
            'is_notification_likes'    => 1,
            'is_notification_messages' => 1,
            'key' => $female_key,
        ));
        $this->assertEquals($result['status'], 1);

        // парень ищет девушек

        $result = Bootstrap::getFromApi('searchAround', array(
            'key' => $male_key,
        ));
        $this->assertEquals($result['status'], 1);
        $this->assertTrue(is_array($result['users']));
    }

}
