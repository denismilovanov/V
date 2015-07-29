<?php

require_once 'bootstrap.php';

use \App\Models\VK;
use \App\Models\UsersIndex;


class GetAudioTest extends TestCase {

    public function test1() {

        $audio_ids = VK::getUserAudioIds($user_id = env('VK_APP_TEST_USER_ID_OUR_ID'));
        $this->assertTrue(sizeof($audio_ids) > 0);

        $this->assertTrue(UsersIndex::updateAudioVkIds($user_id));

    }

}
