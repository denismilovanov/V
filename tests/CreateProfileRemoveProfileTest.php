<?php

require_once 'bootstrap.php';

class CreateProfileRemoveProfileTest extends TestCase {

    public function test1() {

        return;

        $vk_id = - mt_rand(1e6, 1e7);

        $auth = self::getFromApi('authorizeVK', array(
            'access_token' => $access['access_token'],
            'vk_id' => $vk_id,
            'sex' => 1,
            'name' => 'TEST',
            'bdate' => '1990-01-01',
            'about' => 'about',
            'avatar_url' => '',
            'device_type' => 1,
            'device_token' => 'TOKEN',
            'api_version' => '2.0',
            'soft_version' => '2.0.0',
        ));

        $test->assertEquals($auth['status'], 1);
        $test->assertTrue(isset($auth['is_new']));


    }

}
