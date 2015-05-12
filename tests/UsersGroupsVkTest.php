<?php

require_once 'bootstrap.php';

class UsersGroupsVkTest extends TestCase {

    public function test1() {

        return;

        $user_id = 12885;
        $groups_ids = array(44233743, 46252034);

        UsersGroupsVk::setUserGroupsVk(
            $user_id,
            $groups_ids
        );

    }

}
