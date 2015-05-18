<?php

require_once 'bootstrap.php';

class SetDeviceTokenTest extends TestCase {

    public function test1() {

        return;

        $access = Bootstrap::getAccess($this);

        // устанавливаем новый токен

        $result = Bootstrap::getFromApi('setDeviceToken', array(
            'key' => $access['key'],
            'device_token' => 'TOKEN', // см. getAccess
            'device_type' => 1, // iOS
        ));

        $this->assertEquals($result['status'], 1);

    }

}
