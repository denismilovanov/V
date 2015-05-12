<?php

require_once 'bootstrap.php';

class LogoutTest extends TestCase {

    public function test1() {
        $pair = Bootstrap::gerMaleFemalePair();
        $test_male_id = $pair['test_male_id'];
        $male_key = $pair['male_key'];

        Bootstrap::log("male_key = $male_key");

        // запрашиваем инфу о себе

        $result = Bootstrap::getFromApi('getUserProfile', array(
            'key' => $male_key,
            'user_id' => $test_male_id,
        ));
        $this->assertEquals($result['status'], 1);

        // логаут

        $result = Bootstrap::getFromApi('logout', array(
            'key' => $male_key,
        ));
        $this->assertEquals($result['status'], 1);

        // снова

        $result = Bootstrap::getFromApi('getUserProfile', array(
            'key' => $male_key,
            'user_id' => $test_male_id,
        ));
        $this->assertEquals($result['status'], 2);

    }

}
