<?php namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Models\Users;
use App\Models\UsersGroupsVk;
use App\Models\UsersFiendsVk;
use App\Models\UsersPhotos;
use App\Models\Checkins;
use App\Models\Likes;
use App\Models\Messages;
use App\Models\Abuses;
use App\Models\Helper;
use App\Models\VK;
use App\Models\ErrorCollector;


class ApiController extends BaseController {
    const ERROR = 0;
    const SUCCESS = 1;
    const ERROR_KEY = 2;
    const ERROR_DISLIKE = 7;

    public static $user = null;

    public function __construct() {
    }

    public function beforeAction($method) {
        $key = \Request::get('key');
        self::$user = Users::findByKey($key);

        if (self::$user !== null) {
            Users::updateLastActivity(self::$user->id, self::$user->need_to_trigger_activity_event);
        }

        $auth = self::$user !== null;

        if ($auth) {
            ErrorCollector::addRequest($method, self::$user->id);
        }

        return $auth;
    }

    public function authorizeVK() {
        $access_token = \Request::get('access_token');
        $vk_id = intval(\Request::get('vk_id'));
        $device_type = intval(\Request::get('device_type'));
        $device_token = \Request::get('device_token');
        if (! $device_token) {
            $device_token = null;
        }
        $api_version = \Request::get('api_version');
        $soft_version = \Request::get('soft_version');
        $name = \Request::get('name');
        $sex = intval(\Request::get('sex'));
        $bdate = \Request::get('bdate');
        $about = strip_tags(\Request::get('about'));
        $avatar_url = strip_tags(\Request::get('avatar_url'));
        $timezone = intval(\Request::get('timezone', 0));

        $data = [];

        $soft_version_int = Helper::softVersionFromStringToInt($soft_version);

        if (! $soft_version_int) {
            $data['status'] = self::ERROR;
            return response()->json($data);
        }

        if (! is_int($vk_id)) {
            $data['status'] = self::ERROR;
            $data['error'] = 'vk_id';
            return response()->json($data);
        }

        if (! in_array($device_type, [1, 2])) {
            $data['status'] = self::ERROR;
            $data['error'] = 'device_type';
            return response()->json($data);
        }

        if (($device_type == 1 and $device_token !== null and strlen($device_token) != 64) or
            ($device_type == 2 and $device_token !== null and strlen($device_token) != 162))
        {
            $data['status'] = self::ERROR;
            $data['error'] = 'device_token_length';
            return response()->json($data);
        }

        if (! VK::checkVKAccessToken($access_token, $vk_id)) {
            $data['status'] = self::ERROR;
            $data['error'] = 'access_token';
            return response()->json($data);
        }

        $user = Users::upsertByVkId($vk_id, $sex, $name, $bdate, $about, $avatar_url, $timezone, $timezone);

        $key = Users::getAccessKey($user->user_id, $device_token, $device_type, $soft_version_int);

        $soft = Users::getLatestSoftVersion($device_type);

        $data['status'] = self::SUCCESS;
        $data['key'] = $key;
        $data['user_id'] = $user->user_id;
        $data['is_new'] = $user->is_new;
        $data['is_blocked'] = $user->is_blocked;

        $data['latest_soft_version'] = Helper::softVersionFromIntToString($soft->version);
        $data['latest_soft_description'] = $soft->description;

        return response()->json($data);
    }

    public function syncGroupsVK() {
        if (! $this->beforeAction(__METHOD__)) {
            $data['status'] = self::ERROR_KEY;
            return response()->json($data);
        }

        $result = false;

        if (isset($_POST['groups']) and $groups = json_decode($_POST['groups'], 'assoc') and is_array($groups)) {
            $groups = array_slice($groups, 0, 5000);
            $result = Users::syncGroupsVK(
                self::$user->id,
                $groups
            );
        }

        return response()->json([
            'status' => $result ? self::SUCCESS : self::ERROR,
        ]);
    }

    public function syncFriendsVK() {
        if (! $this->beforeAction(__METHOD__)) {
            $data['status'] = self::ERROR_KEY;
            return response()->json($data);
        }

        $result = false;

        if (isset($_POST['friends']) and $friends = json_decode($_POST['friends'], 'assoc') and is_array($friends)) {
            $friends = array_slice($friends, 0, 5000);
            $result = Users::syncFriendsVK(
                self::$user->id,
                $friends
            );
        }

        return response()->json([
            'status' => $result ? self::SUCCESS : self::ERROR,
        ]);
    }

    public function syncProfileVK() {
        if (! $this->beforeAction(__METHOD__)) {
            $data['status'] = self::ERROR_KEY;
            return response()->json($data);
        }

        $result = false;

        if (isset($_POST['profile']) and $profile = json_decode($_POST['profile'], 'assoc') and is_array($profile)) {
            $result = Users::syncProfileVK(
                self::$user->id,
                $profile
            );
        }

        return response()->json([
            'status' => $result ? self::SUCCESS : self::ERROR,
        ]);
    }

    public function setPhotosVK() {
        if (! $this->beforeAction(__METHOD__)) {
            $data['status'] = self::ERROR_KEY;
            return response()->json($data);
        }

        $result = false;

        if (isset($_POST['photos']) and $photos = json_decode($_POST['photos'], 'assoc') and is_array($photos)) {
            $photos = array_slice($photos, 0, 50);
            $result = UsersPhotos::setPhotosVK(
                self::$user->id,
                $photos
            );
        }

        return response()->json([
            'status' => $result ? self::SUCCESS : self::ERROR,
        ]);
    }

    public function getPhotosVK() {
        if (! $this->beforeAction(__METHOD__)) {
            $data['status'] = self::ERROR_KEY;
            return response()->json($data);
        }

        return response()->json([
            'status' => self::SUCCESS,
            'photos' => UsersPhotos::getUserPhotos(self::$user->id, 1),
        ]);
    }

    public function uploadPhoto() {
        if (! $this->beforeAction(__METHOD__)) {
            $data['status'] = self::ERROR_KEY;
            return response()->json($data);
        }

        $result = false;

        if (isset($_POST['photo'], $_POST['extension'])) {
            $photo_id = UsersPhotos::uploadPhoto(
                self::$user->id,
                $_POST['photo'],
                $_POST['extension']
            );
        }

        return response()->json([
            'status' => $photo_id ? self::SUCCESS : self::ERROR,
            'photo_id' => $photo_id,
        ]);
    }

    public function removePhoto() {
        if (! $this->beforeAction(__METHOD__)) {
            $data['status'] = self::ERROR_KEY;
            return response()->json($data);
        }

        $photo_id = intval(\Request::get('photo_id'));

        $result = UsersPhotos::removePhoto(
            self::$user->id,
            $photo_id
        );

        return response()->json([
            'status' => $result ? self::SUCCESS : self::ERROR,
        ]);
    }

    public function checkin() {
        if (! $this->beforeAction(__METHOD__)) {
            $data['status'] = self::ERROR_KEY;
            return response()->json($data);
        }

        $latitude = floatval(\Request::get('latitude'));
        $longitude = floatval(\Request::get('longitude'));

        if (! $latitude or ! $longitude or abs($latitude) > 90 or abs($longitude) > 180) {
            $result = false;
        } else {
            $result = Checkins::checkin(
                self::$user->id,
                floatval($longitude),
                floatval($latitude)
            );
        }

        return response()->json([
            'status' => $result ? self::SUCCESS : self::ERROR,
        ]);
    }

    public function getMySettings() {
        if (! $this->beforeAction(__METHOD__)) {
            $data['status'] = self::ERROR_KEY;
            return response()->json($data);
        }

        $settings = Users::getSettings(self::$user->id);

        return response()->json($settings + [
            'status' => $settings ? self::SUCCESS : self::ERROR,
        ]);
    }

    public function setMySettings() {
        if (! $this->beforeAction(__METHOD__)) {
            $data['status'] = self::ERROR_KEY;
            return response()->json($data);
        }

        $sex = intval(\Request::get('sex'));
        $radius = intval(\Request::get('radius'));
        $age_from = intval(\Request::get('age_from'));
        $age_to = intval(\Request::get('age_to'));
        $is_show_male = \Request::get('is_show_male');
        $is_show_female = \Request::get('is_show_female');
        $is_notification = \Request::get('is_notification');
        $is_notification_likes = \Request::get('is_notification_likes');
        $is_notification_messages = \Request::get('is_notification_messages');

        if (($sex != 1 and $sex != 2) or $age_from < 18 or $age_to > 80) {
            $result = false;
        } else {
            $result = Users::setSettings(self::$user->id, $sex, $radius, $age_from, $age_to, $is_show_male,
                                         $is_show_female, $is_notification,
                                         $is_notification_likes, $is_notification_messages);
        }

        return response()->json([
            'status' => $result ? self::SUCCESS : self::ERROR,
        ]);
    }

    public function setAbout() {
        if (! $this->beforeAction(__METHOD__)) {
            $data['status'] = self::ERROR_KEY;
            return response()->json($data);
        }

        $about = strip_tags(\Request::get('about'));
        $about = mb_substr($about, 0, 1000);

        $result = Users::setAbout(self::$user->id, $about);

        return response()->json([
            'status' => $result ? self::SUCCESS : self::ERROR,
        ]);
    }

    public function getAbout() {
        if (! $this->beforeAction(__METHOD__)) {
            $data['status'] = self::ERROR_KEY;
            return response()->json($data);
        }

        $about = '';

        $user = Users::findById(self::$user->id);
        if ($user) {
            $about = $user->about;
        }

        return response()->json([
            'status' => $user ? self::SUCCESS : self::ERROR,
            'about' => $about,
        ]);
    }



    public function searchAround() {
        if (! $this->beforeAction(__METHOD__)) {
            $data['status'] = self::ERROR_KEY;
            return response()->json($data);
        }

        $limit = intval(\Request::get('limit', 50));

        $data['status'] = self::SUCCESS;
        $data['users'] = Users::searchAround(self::$user->id, $limit);

        return response()->json($data);
    }

    public function like() {
        if (! $this->beforeAction(__METHOD__)) {
            $data['status'] = self::ERROR_KEY;
            return response()->json($data);
        }

        $user_id = intval(\Request::get('user_id'));
        $is_like = intval(\Request::get('is_like'));
        $weight_level = \Request::get('weight_level');

        if ($is_like) {
            $is_like = 1;
        }

        $result = Likes::like(self::$user->id, $user_id, $is_like, $weight_level);

        return response()->json([
            'status' => $result ? self::SUCCESS : self::ERROR,
            'mutual' => $result ? $result['mutual'] : 0,
        ]);
    }

    public function abuse() {
        if (! $this->beforeAction(__METHOD__)) {
            $data['status'] = self::ERROR_KEY;
            return response()->json($data);
        }

        $user_id = intval(\Request::get('user_id'));
        $text = strip_tags(\Request::get('text'));

        $abuse_id = Abuses::abuse(self::$user->id, $user_id, $text);

        return response()->json([
            'status' => $abuse_id ? self::SUCCESS : self::ERROR,
            'abuse_id' => $abuse_id,
        ]);
    }

    public function blockUser() {
        if (! $this->beforeAction(__METHOD__)) {
            $data['status'] = self::ERROR_KEY;
            return response()->json($data);
        }

        $user_id = intval(\Request::get('user_id'));

        $result = Likes::blockUser(self::$user->id, $user_id);

        return response()->json([
            'status' => $result ? self::SUCCESS : self::ERROR,
        ]);
    }

    public function logout() {
        if (! $this->beforeAction(__METHOD__)) {
            $data['status'] = self::ERROR_KEY;
            return response()->json($data);
        }

        $key = \Request::get('key');

        $result = Users::logout(self::$user->id, $key);

        return response()->json([
            'status' => $result ? self::SUCCESS : self::ERROR,
        ]);
    }

    public function removeProfile() {
        if (! $this->beforeAction(__METHOD__)) {
            $data['status'] = self::ERROR_KEY;
            return response()->json($data);
        }

        $result = Users::removeProfile(self::$user->id, intval(\Request::get('test', 0)));

        return response()->json([
            'status' => $result ? self::SUCCESS : self::ERROR,
        ]);
    }

    public function setDeviceToken() {
        if (! $this->beforeAction(__METHOD__)) {
            $data['status'] = self::ERROR_KEY;
            return response()->json($data);
        }

        $key = \Request::get('key');
        $device_token = \Request::get('device_token');
        $device_type = \Request::get('device_type');

        $result = Users::setDeviceToken(self::$user->id, $key, $device_token, $device_type);

        return response()->json([
            'status' => $result ? self::SUCCESS : self::ERROR,
        ]);
    }

    public function getMyMessages() {
        if (! $this->beforeAction(__METHOD__)) {
            $data['status'] = self::ERROR_KEY;
            return response()->json($data);
        }

        $messages = Messages::getMessages(self::$user->id);

        return response()->json([
            'status' => self::SUCCESS,
            'messages' => $messages,
        ]);
    }

    public function getUserProfile() {
        if (! $this->beforeAction(__METHOD__)) {
            $data['status'] = self::ERROR_KEY;
            return response()->json($data);
        }

        $user_id = intval(\Request::get('user_id'));

        $profile = Users::findById($user_id, 'getUserProfile');

        if (! $profile) {
            return response()->json([
                'status' => APIController::ERROR,
            ]);
        }

        return response()->json([
            'status' => self::SUCCESS,
        ] + (array) $profile);
    }

    public function sendMessageToUser() {
        if (! $this->beforeAction(__METHOD__)) {
            $data['status'] = self::ERROR_KEY;
            return response()->json($data);
        }

        $user_id = intval(\Request::get('user_id'));
        $text = strip_tags(\Request::get('text'));

        if (! Likes::isMutual(self::$user->id, $user_id)) {
            return response()->json([
                'status' => self::ERROR_DISLIKE,
            ]);
        }

        if (trim($text) === '') {
            return response()->json([
                'status' => self::ERROR,
            ]);
        }

        $result = Messages::addMessage(self::$user->id, $user_id, $text);

        return response()->json([
            'status' => $result['message_id'] ? self::SUCCESS : self::ERROR,
            'message_id' => $result['message_id'],
            'added_at' => $result['added_at'],
        ]);
    }

    public function getMessagesWithUser() {
        if (! $this->beforeAction(__METHOD__)) {
            $data['status'] = self::ERROR_KEY;
            return response()->json($data);
        }

        $user_id = intval(\Request::get('user_id'));
        $older_than = \Request::get('older_than') !== null ? intval(\Request::get('older_than')) : null;
        $later_than = \Request::get('later_than') !== null ? intval(\Request::get('later_than')) : null;

        $messages = Messages::getAllBetweenUsers(self::$user->id, $user_id, $older_than, $later_than);

        return response()->json([
            'status' => self::SUCCESS,
            'messages' => $messages,
        ]);
    }
}
