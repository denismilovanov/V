<?php

require_once 'bootstrap.php';

use \App\Models\Likes;
use \App\Models\Messages;


class BlockUserTest extends TestCase {

    public function test1() {
        $pair = Bootstrap::gerMaleFemalePair();
        $test_female_id = $pair['test_female_id'];
        $test_male_id = $pair['test_male_id'];
        $male_key = $pair['male_key'];
        $female_key = $pair['female_key'];

        Bootstrap::log("male_key = $male_key, female_key = $female_key");

        // удаляем лайки между ними

        Likes::deleteAllBetween($test_male_id, $test_female_id);
        Messages::deleteAllBetween($test_male_id, $test_female_id);

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

        // а теперь девочка решает заблокировать мальчика

        $result = Bootstrap::getFromApi('blockUser', array(
            'key' => $female_key,
            'user_id' => $test_male_id,
        ));
        $this->assertEquals($result['status'], 1);

        // заново

        Likes::deleteAllBetween($test_male_id, $test_female_id);

        // male likes female

        $result = Bootstrap::getFromApi('like', array(
            'key' => $male_key,
            'user_id' => $test_female_id,
            'is_like' => 1,
        ));
        $this->assertEquals($result['status'], 1);
        $this->assertEquals($result['mutual'], 0);

        // снова девочка блокирует мальчика, но она его не лайкала, поэтому в ответе 0

        $result = Bootstrap::getFromApi('blockUser', array(
            'key' => $female_key,
            'user_id' => $test_male_id,
            'reason' => 1,
        ));
        $this->assertEquals($result['status'], 0);


    }

}
