<?php

require_once 'bootstrap.php';

class GroupsVkTest extends TestCase {

    public function test1() {

        return;

        $id = 3912125;
        $name = 'PoTeM';
        $photo_url = 'http://cs247.vk.me/g3912125/a_681a3bd8.jpg';

        $new_id = GroupsVk::upsert(
            $id,
            $name,
            $photo_url
        );

        $this->assertEquals($id, $new_id);

    }

}
