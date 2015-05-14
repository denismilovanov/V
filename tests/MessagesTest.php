<?php

require_once 'bootstrap.php';

use \App\Models\Likes;
use \App\Models\Messages;


class MessagesTest extends TestCase {

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

        // парень пишет девушке

        $result = Bootstrap::getFromApi('sendMessageToUser', array(
            'key' => $male_key,
            'user_id' => $test_female_id,
            'text' => $message1 = 'MESSAGE' . time(),
        ));
        $this->assertEquals($result['status'], 1);

        // сообщения с этой девушкой

        $result = Bootstrap::getFromApi('getMessagesWithUser', array(
            'key' => $male_key,
            'user_id' => $test_female_id,
            'later_than' => 0,
            //'older_than' => (1 << 31) - 1,
        ));
        $this->assertEquals($result['status'], 1);
        $this->assertTrue(sizeof($result['messages']) == 1);
        $this->assertTrue($result['messages'][0]['direction'] == 2); // "я написал"

        // список чатов девушки

        $result = Bootstrap::getFromApi('getMyMessages', array(
            'key' => $female_key
        ));
        $this->assertEquals($result['status'], 1);
        $this->assertTrue(sizeof($result['messages']) > 0);
        $this->assertTrue($result['messages'][0]['is_new']);

        // сообщения с этим парнем

        $result = Bootstrap::getFromApi('getMessagesWithUser', array(
            'key' => $female_key,
            'user_id' => $test_male_id,
            'later_than' => 0,
            'older_than' => (1 << 31) - 1,
        ));
        $this->assertEquals($result['status'], 1);
        $this->assertTrue(sizeof($result['messages']) == 1);
        $this->assertTrue($result['messages'][0]['direction'] == 1); // "мне написали"

        // список чатов парня

        $result = Bootstrap::getFromApi('getMyMessages', array(
            'key' => $male_key
        ));
        $this->assertEquals($result['status'], 1);
        $this->assertTrue(sizeof($result['messages']) > 0);
        $this->assertFalse($result['messages'][0]['is_new']);

        // девушка отвечает

        $result = Bootstrap::getFromApi('sendMessageToUser', array(
            'key' => $female_key,
            'user_id' => $test_male_id,
            'text' => $message2 = 'ANSWER' . time(),
        ));
        $this->assertEquals($result['status'], 1);

        // список сообщений в чате парня

        $result = Bootstrap::getFromApi('getMessagesWithUser', array(
            'key' => $male_key,
            'user_id' => $test_female_id,
        ));
        $this->assertEquals($result['status'], 1);
        $this->assertTrue(sizeof($result['messages']) == 2);

    }

}
