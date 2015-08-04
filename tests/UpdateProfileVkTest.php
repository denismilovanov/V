<?php

require_once 'bootstrap.php';

use \App\Models\VK;
use \App\Models\UsersIndex;


class UpdateProfileVkTest extends TestCase {

    public function test1() {

        $this->assertTrue(UsersIndex::updateProfileVk(env('VK_APP_TEST_USER_ID_OUR_ID')));

    }

}
