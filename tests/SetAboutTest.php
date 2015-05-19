<?php

require_once 'bootstrap.php';

class SetAboutTest extends TestCase {

    public function test1() {

        $pair = Bootstrap::gerMaleFemalePair();
        $test_female_id = $pair['test_female_id'];
        $test_male_id = $pair['test_male_id'];
        $male_key = $pair['male_key'];
        $female_key = $pair['female_key'];

        Bootstrap::log("male_key = $male_key, female_key = $female_key");

        $result = Bootstrap::getFromApi('setAbout', array(
            'about' => 'I am female and wanna meet a nice guy',
            'key' => $female_key,
        ));
        $this->assertEquals($result['status'], 1);

        $result = Bootstrap::getFromApi('setAbout', array(
            'about' => 'Je cherche une femme',
            'key' => $male_key,
        ));
        $this->assertEquals($result['status'], 1);

    }

}
