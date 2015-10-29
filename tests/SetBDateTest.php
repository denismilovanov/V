<?php

require_once 'bootstrap.php';

class SetBDateTest extends TestCase {

    public function test1() {

        $pair = Bootstrap::gerMaleFemalePair();
        $test_female_id = $pair['test_female_id'];
        $female_key = $pair['female_key'];

        Bootstrap::log("female_key = $female_key");

        $result = Bootstrap::getFromApi('setBDate', array(
            'bdate' => '2000-12-01',
            'key' => $female_key,
        ));
        $this->assertEquals($result['status'], 1);

        $result = Bootstrap::getFromApi('setBDate', array(
            'bdate' => '2000-13-01',
            'key' => $female_key,
        ));
        $this->assertEquals($result['status'], 0);

        $result = Bootstrap::getFromApi('setBDate', array(
            'bdate' => 20,
            'key' => $female_key,
        ));
        $this->assertEquals($result['status'], 1);

    }

}
