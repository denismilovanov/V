<?php

require_once 'bootstrap.php';

class GetMyMessagesTest extends TestCase {

    public function test1() {
        $pair = Bootstrap::gerMaleFemalePair();
        $test_male_id = $pair['test_male_id'];
        $male_key = $pair['male_key'];

        Bootstrap::log("male_key = $male_key");

        $result = Bootstrap::getFromApi('getMyMessages', array(
            'key' => $male_key
        ));
        $this->assertEquals($result['status'], 1);
    }

}
