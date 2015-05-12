<?php

require_once 'bootstrap.php';

class GetUserProfileTest extends TestCase {

    public function test1() {

        // имитируем авторизацию ВК, то есть получение токена

        $access = Bootstrap::getAccess($this);

        // инфа о себе же

        $result = Bootstrap::getFromApi('getUserProfile', array(
            'key' => $access['key'],
            'user_id' => $access['user_id'],
        ));

        $this->assertEquals($result['status'], 1);
        $this->assertTrue(isset($result['name']));
        $this->assertTrue(isset($result['photos']));

    }

}
