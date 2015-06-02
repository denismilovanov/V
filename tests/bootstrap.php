<?php

define('APP_ENV', env('APP_ENV'));

class TestCase extends Laravel\Lumen\Testing\TestCase {
    /**
     * Creates the application.
     *
     * @return \Laravel\Lumen\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';
        return $app;
    }

    public function setUp() {
        parent::setUp();
        $this->createApplication();
    }
}

class Bootstrap {
    private static $curl;
    private static $cookie_file = '/tmp/cookie.txt';

    public static function log($message) {
        echo $message . PHP_EOL;
        flush();
    }

    public static function getFromApi($method, $params) {
        $url = env('URL') . env('API_RELATIVE_URL') . '/' . $method;
        $result = self::get($url, $params);
        $result = self::stripNonJSON($result);
        return json_decode($result, 'assoc');
    }

    public static function postFromApi($method, $params) {
        $url = env('URL') . env('API_RELATIVE_URL') . '/' . $method;
        $result = self::post($url, $params);
        $result = self::stripNonJSON($result);
        return json_decode($result, 'assoc');
    }

    private static function stripNonJSON($data) {
        $data = trim(preg_replace('~<\!.+>~uixs', '', $data));
        return $data;
    }

    public static function curl() {
        if (self::$curl) {
            return self::$curl;
        }
        self::$curl = curl_init();
        curl_setopt(self::$curl, CURLOPT_COOKIEFILE, self::$cookie_file);
        curl_setopt(self::$curl, CURLOPT_COOKIEJAR, self::$cookie_file);
        curl_setopt(self::$curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt(self::$curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt(self::$curl, CURLOPT_HEADER, false);
        curl_setopt(self::$curl, CURLOPT_USERAGENT, 'https://vmeste-app.ru;+unit-tests');
        return self::$curl;
    }

    public static function get($url, $data = array()) {
        $ch = self::curl();
        curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($data));
        curl_setopt($ch, CURLOPT_POST, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        self::log('GET ' . $url . '?' . http_build_query($data));
        $result = curl_exec($ch);
        return $result;
    }

    public static function access_token($url, $data = array()) {
        $ch = self::curl();
        curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($data));
        curl_setopt($ch, CURLOPT_HEADER, true);
        self::log('HEAD ' . $url . '?' . http_build_query($data));
        $result = curl_exec($ch);
        $matches = array();
        preg_match("~access_token=(.+?)&~ix", $result, $matches);
        $access_token = '';
        if (isset($matches[1])) {
            $access_token = $matches[1];
        }
        preg_match("~user_id=(\d+)~ix", $result, $matches);
        $user_id = '';
        if (isset($matches[1])) {
            $user_id = $matches[1];
        }
        return array(
            'access_token' => $access_token,
            'user_id' => $user_id,
        );
    }

    public static function post($url, $data) {
        $ch = self::curl();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_HEADER, false);
        self::log('POST ' . $url); // . '?' . http_build_query($data));
        $result = curl_exec($ch);
        return $result;
    }

    public static function getAccess($test) {
        if (! file_exists(self::$cookie_file) or filemtime(self::$cookie_file) < time() - 3600) {
            self::log('Login VK');
            $url = 'https://login.vk.com/?act=login';
            $data = self::post($url, array(
                'email' => 'me@uelen.ru',
                'pass' => 'vmesteTEST',
            ));
        }

        $url = 'https://oauth.vk.com/authorize';
        self::log('Authorize VK');
        $access_token = self::access_token($url, array(
            'client_id' => env('VK_APP_ID'),
            'scope' => 'offline,friends,groups,photos',
            'redirect_uri' => 'https://oauth.vk.com/blank.html',
            'response_type' => 'token',
        ));
        self::log('Access token = ' . $access_token['access_token']);
        self::log('User id = ' . $access_token['user_id']);

        $access = array(
            'access_token' => $access_token['access_token'],
            'user_id' => $access_token['user_id'],
            'vk_id' => 176338986,
        );

        $auth = self::getFromApi('authorizeVK', array(
            'access_token' => $access['access_token'],
            'vk_id' => $access['vk_id'],
            'sex' => 1,
            'name' => 'Денис',
            'bdate' => '1990-01-01',
            'about' => 'about',
            'avatar_url' => '',
            'device_type' => 1,
            'device_token' => '3' . sprintf("%063d", $access['vk_id']),
            'api_version' => '2.0',
            'soft_version' => '2.0.0',
        ));

        $test->assertEquals($auth['status'], 1);
        $test->assertTrue(isset($auth['is_new']));
        $test->assertTrue(isset($auth['latest_soft_description']));

        $access['key'] = $auth['key'];
        $access['user_id'] = $auth['user_id'];

        return $access;
    }

    public static function vkApiMethod($method, $access_token, $params = array()) {
        $params['timestamp']    = time();
        $params['api_id']       = env('VK_APP_ID');
        $params['random']       = rand(0, 10000);
        $params['access_token'] = $access_token;
        $params['v']            = '5.30';

        ksort($params);

        $sig = '';
        foreach ($params as $key => $value) {
            $sig .= $key . '=' . $value;
        }
        $sig .= env('VK_APP_SECRET');

        $params['sig'] = md5($sig);

        $result = self::get('https://api.vk.com/method/' . $method . '.json', $params);

        self::log("Result = " . $result);

        $result = json_decode($result, true);

        return $result;
    }

    public static function getFriendsGroupsVK($access_token) {
        $code = 'var profile = API.users.get({"fields":"id,sex,photo_max,bdate,name,occupation,activities,interests,music,movies,tv,books,games,quotes,personal"});
                 var friends = API.friends.get({});
                 var groups = API.groups.get({});
                 var photos = API.photos.get({"album_id":"profile", "limit":"5", "offset":"0", "rev":"1"});
                 return {"profile":profile,"name":profile@.first_name,"sex":profile@.sex, "avatar_url":profile@.photo_max,
                 "bdate":profile@.bdate, "groups":groups, "friends":friends, "photos":photos};';

        $result = self::vkApiMethod(
            'execute',
            $access_token,
            array('code' => $code)
        );

        $photos = [];
        $rank = 0;

        foreach ($result['response']['photos']['items'] as $photo) {
            $photos []= [
                'url' => $photo['photo_130'],
                'rank' => $rank ++,
            ];
        }

        return array(
            'friends' => $result['response']['friends']['items'],
            'groups' => $result['response']['groups']['items'],
            'photos' => $photos,
            'profile' => $result['response']['profile'][0],
        );
    }

    public static function gerMaleFemalePair() {
        $test_female_id = 100000;
        $test_male_id = 200000;
        $token_male = '2' . sprintf("%063d", $test_male_id);
        $token_female = '1' . sprintf("%063d", $test_female_id);

        $male_key = \DB::select("
            SELECT public.get_access_key(?, ?, 1, '20000')
        ", [$test_male_id, $token_male])[0]->get_access_key;

        $female_key = \DB::select("
            SELECT public.get_access_key(?, ?, 1, '20000')
        ", [$test_female_id, $token_female])[0]->get_access_key;

        return array(
            'test_female_id' => $test_female_id,
            'test_male_id' => $test_male_id,
            'male_key' => $male_key,
            'female_key' => $female_key,
        );
    }
}

