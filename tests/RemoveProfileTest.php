<?php

require_once 'bootstrap.php';

use \App\Models\Users;

class RemoveProfileTest extends TestCase {

    public function test1() {
        $pair = Bootstrap::gerMaleFemalePair();
        $test_male_id = $pair['test_male_id'];
        $male_key = $pair['male_key'];

        Bootstrap::log("male_key = $male_key");

        //

        $result = Bootstrap::getFromApi('removeProfile', array(
            'key' => $male_key,
            'user_id' => $test_male_id,
            'test' => 1,
        ));
        $this->assertEquals($result['status'], 1);

        //

        $result = Bootstrap::getFromApi('getUserProfile', array(
            'key' => $male_key,
            'user_id' => $test_male_id,
        ));
        $this->assertTrue($result['is_deleted']);

        //

        Users::unremove($test_male_id);

    }

}
