<?php

require_once 'bootstrap.php';

class SyncFriendsVKActionTest extends TestCase {

    public function test1() {

        // имитируем авторизацию ВК, то есть получение токена

        $access = Bootstrap::getAccess($this);

        // имитируем мобильное устройство, которое запросит данные у ВК

        $data = Bootstrap::getFriendsGroupsVK($access['access_token']);

        // а теперь мобилка посылает серверу друзей

        $result = Bootstrap::postFromApi('syncFriendsVK', array(
            'key' => $access['key'],
            'friends' => json_encode($data['friends']),
        ));

        $this->assertEquals($result['status'], 1);

        // и посылаем группы

        $result = Bootstrap::postFromApi('syncGroupsVK', array(
            'key' => $access['key'],
            'groups' => json_encode($data['groups']),
        ));

        // и фотографии

        $result = Bootstrap::postFromApi('setPhotosVK', array(
            'key' => $access['key'],
            'photos' => json_encode($data['photos']),
        ));

        $this->assertEquals($result['status'], 1);

        $result = Bootstrap::getFromApi('getPhotosVK', array(
            'key' => $access['key'],
            'photos' => json_encode($data['photos']),
        ));

        $this->assertEquals(sizeof($result['photos']), sizeof($data['photos']));

    }

}
