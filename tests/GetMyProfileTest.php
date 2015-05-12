<?php

require_once 'bootstrap.php';

class GetMyProfileTest extends TestCase {

    public function test1() {

        $access = Bootstrap::getAccess($this);

        // получаем профиль

        $result = Bootstrap::getFromApi('getMyProfile', array(
            'key' => $access['key'],
        ));

    }

}
