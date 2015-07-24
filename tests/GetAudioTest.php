<?php

require_once 'bootstrap.php';

use \App\Models\VK;

class GetAudioTest extends TestCase {

    public function test1() {

        $audion_ids = VK::getUserAudioIds(env('VK_APP_TEST_USER_ID_OUR_ID'));
        $this->assertTrue(sizeof($audion_ids) > 0);

    }

}
