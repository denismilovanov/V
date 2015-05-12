<?php

require_once 'bootstrap.php';

use \App\Models\Abuses;

class AbuseTest extends TestCase {

    public function test1() {
        $pair = Bootstrap::gerMaleFemalePair();
        $test_female_id = $pair['test_female_id'];
        $test_male_id = $pair['test_male_id'];
        $male_key = $pair['male_key'];
        $female_key = $pair['female_key'];

        Bootstrap::log("male_key = $male_key, female_key = $female_key");

        // мальчик будет жаловаться на девочку

        Abuses::deleteAllFromTo($test_male_id, $test_female_id);

        //

        $result = Bootstrap::getFromApi('abuse', array(
            'key' => $male_key,
            'user_id' => $test_female_id,
            'text' => $abuse_message = 'TEST_ABUSE',
        ));
        $this->assertEquals($result['status'], 1);
        $this->assertTrue(isset($result['abuse_id']));

        $abuse_id = $result['abuse_id'];

        //$abuse = Abuses::model()->findByPK($abuse_id);

        //$this->assertEquals($abuse->message, $abuse_message);
    }

}
