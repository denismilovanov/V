<?php

require_once 'bootstrap.php';

use \App\Models\Pusher;

class PusherTest extends TestCase {

    public function test1() {
        $pusher = Pusher::getApplePusher();

        $deviceToken = '258e9cb8085bdf8b846ca554b83af4efbc8bd510506af51b35d047bcd70ed10d';
        $text = 'Hello from a unit-test';
        $badge = 1;

        $message = new \ApnsPHP_Message($deviceToken);

        $message->setText($text);
        $message->setBadge($badge);
        $message->setSound();

        $pusher->add($message);

        $pusher->send();
    }

}
