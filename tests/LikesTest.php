<?php

require_once 'bootstrap.php';

use \App\Models\Likes;

class LikesTest extends TestCase {

    public function test1() {
        $pair = Bootstrap::gerMaleFemalePair();
        $test_female_id = $pair['test_female_id'];
        $test_male_id = $pair['test_male_id'];
        $male_key = $pair['male_key'];
        $female_key = $pair['female_key'];

        Bootstrap::log("male_key = $male_key, female_key = $female_key");

        // удаляем лайки между ними

        Likes::deleteAllBetween($test_male_id, $test_female_id);

        // male likes female

        $result = Bootstrap::getFromApi('like', array(
            'key' => $male_key,
            'user_id' => $test_female_id,
            'is_like' => 1,
        ));
        $this->assertEquals($result['status'], 1);
        $this->assertEquals($result['mutual'], 0);

        // female likes male

        $result = Bootstrap::getFromApi('like', array(
            'key' => $female_key,
            'user_id' => $test_male_id,
            'is_like' => 1,
        ));
        $this->assertEquals($result['status'], 1);
        $this->assertEquals($result['mutual'], 1);

        // удаляем лайки между ними

        Likes::deleteAllBetween($test_male_id, $test_female_id);

        // male likes female

        $result = Bootstrap::getFromApi('like', array(
            'key' => $male_key,
            'user_id' => $test_female_id,
            'is_like' => 1,
        ));
        $this->assertEquals($result['status'], 1);
        $this->assertEquals($result['mutual'], 0);

        // female dislikes male

        $result = Bootstrap::getFromApi('like', array(
            'key' => $female_key,
            'user_id' => $test_male_id,
            'is_like' => 0,
        ));
        $this->assertEquals($result['status'], 1);
        $this->assertEquals($result['mutual'], 0);


    }

}
