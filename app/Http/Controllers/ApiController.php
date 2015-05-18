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


class ApiController extends BaseController {
    const ERROR = 0;
    const SUCCESS = 1;
    const ERROR_KEY = 2;
    const ERROR_DISLIKE = 7;

    private static $key = null;
    public static $user = null;

    public function __construct() {
    }

    public function beforeAction() {
        $key = \Request::get('key');

        $sql = "SELECT u.id, u.sex, u.name, u.about,
                        uivk.token,
                        u.avatar_url,
                        uivk.vk_id,
                        date_part('year', age(u.bdate)) as age,
                        u.time_zone
                  FROM users_devices AS ud
                  INNER JOIN users AS u
                        on u.id = ud.user_id
                  INNER JOIN users_info_vk AS uivk
                        on uivk.user_id = ud.user_id
                  where ud.key = ?";

        $user = \DB::select($sql, [$key]);
        if ($user) {
            self::$user = $user[0];
            return true;
        }

        return false;
    }

    public function authorizeVK() {
        $access_token = \Request::get('access_token');
        $vk_id = \Request::get('vk_id');
        $device_type = \Request::get('device_type');
        $device_token = \Request::get('device_token');
        $api_version = \Request::get('api_version');
        $soft_version = \Request::get('soft_version');
        $name = \Request::get('name');
        $sex = \Request::get('sex');
        $bdate = \Request::get('bdate');
        $about = \Request::get('about');
        $avatar_url = \Request::get('avatar_url');
        $timezone = \Request::get('timezone', 0);

        $data = [];

        $soft_version_int = Helper::softVersionFromStringToInt($soft_version);

        if (! $soft_version_int) {
            $data['status'] = self::ERROR;
            return response()->json($data);
        }

        $user = Users::upsertByVkId($vk_id, $sex, $name, $bdate, $about, $avatar_url, $timezone, $timezone);

        $key = Users::getAccessKey($user->user_id, $device_token, $device_type, $soft_version_int);

        $soft = Users::getLatestSoftVersion($device_type);

        $data['status'] = self::SUCCESS;
        $data['key'] = $key;
        $data['user_id'] = $user->user_id;
        $data['is_new'] = $user->is_new;

        $data['latest_soft_version'] = Helper::softVersionFromIntToString($soft->version);
        $data['latest_soft_description'] = $soft->description;

        return response()->json($data);
    }

    public function syncGroupsVK() {
        if (! $this->beforeAction()) {
            $data['status'] = self::ERROR_KEY;
            return response()->json($data);
        }

        $result = false;

        if (isset($_POST['groups']) and $groups = json_decode($_POST['groups'], 'assoc')) {
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
        if (! $this->beforeAction()) {
            $data['status'] = self::ERROR_KEY;
            return response()->json($data);
        }

        $result = false;

        if (isset($_POST['friends']) and $friends = json_decode($_POST['friends'], 'assoc')) {
            $result = Users::syncFriendsVK(
                self::$user->id,
                $friends
            );
        }

        return response()->json([
            'status' => $result ? self::SUCCESS : self::ERROR,
        ]);
    }

    public function uploadPhoto() {
        if (! $this->beforeAction()) {
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
        if (! $this->beforeAction()) {
            $data['status'] = self::ERROR_KEY;
            return response()->json($data);
        }

        $photo_id = \Request::get('photo_id');

        $result = UsersPhotos::removePhoto(
            self::$user->id,
            $photo_id
        );

        return response()->json([
            'status' => $result ? self::SUCCESS : self::ERROR,
        ]);
    }

    public function checkin() {
        if (! $this->beforeAction()) {
            $data['status'] = self::ERROR_KEY;
            return response()->json($data);
        }

        $latitude = floatval(\Request::get('latitude'));
        $longitude = floatval(\Request::get('longitude'));

        if (! $latitude or ! $longitude or abs($latitude) > 90 or abs($longitude) > 90) {
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
        if (! $this->beforeAction()) {
            $data['status'] = self::ERROR_KEY;
            return response()->json($data);
        }

        $settings = Users::getSettings(self::$user->id);

        return response()->json($settings + [
            'status' => $settings ? self::SUCCESS : self::ERROR,
        ]);
    }

    public function setMySettings() {
        if (! $this->beforeAction()) {
            $data['status'] = self::ERROR_KEY;
            return response()->json($data);
        }

        $sex = \Request::get('sex');
        $radius = \Request::get('radius');;
        $age_from = \Request::get('age_from');
        $age_to = \Request::get('age_to');
        $is_show_male = \Request::get('is_show_male');
        $is_show_female = \Request::get('is_show_female');
        $is_notification = \Request::get('is_notification');
        $is_notification_likes = \Request::get('is_notification_likes');
        $is_notification_messages = \Request::get('is_notification_messages');

        $result = Users::setSettings(self::$user->id, $sex, $radius, $age_from, $age_to, $is_show_male,
                                     $is_show_female, $is_notification,
                                     $is_notification_likes, $is_notification_messages);

        return response()->json([
            'status' => $result ? self::SUCCESS : self::ERROR,
        ]);
    }

    public function searchAround() {
        if (! $this->beforeAction()) {
            $data['status'] = self::ERROR_KEY;
            return response()->json($data);
        }

        $data['status'] = self::SUCCESS;
        $data['users'] = Users::searchAround(self::$user->id);

        return response()->json($data);
    }

    public function like() {
        if (! $this->beforeAction()) {
            $data['status'] = self::ERROR_KEY;
            return response()->json($data);
        }

        $user_id = \Request::get('user_id');
        $is_like = \Request::get('is_like');

        $result = Likes::like(self::$user->id, $user_id, $is_like);

        return response()->json([
            'status' => $result ? self::SUCCESS : self::ERROR,
            'mutual' => $result ? $result['mutual'] : 0,
        ]);
    }

    public function abuse() {
        if (! $this->beforeAction()) {
            $data['status'] = self::ERROR_KEY;
            return response()->json($data);
        }

        $user_id = \Request::get('user_id');
        $text = \Request::get('text');

        $abuse_id = Abuses::abuse(self::$user->id, $user_id, $text);

        return response()->json([
            'status' => $abuse_id ? self::SUCCESS : self::ERROR,
            'abuse_id' => $abuse_id,
        ]);
    }

    public function blockUser() {
        if (! $this->beforeAction()) {
            $data['status'] = self::ERROR_KEY;
            return response()->json($data);
        }

        $user_id = \Request::get('user_id');
        $reason = intval(\Request::get('reason'));

        $result = Likes::blockUser(self::$user->id, $user_id, $reason);

        return response()->json([
            'status' => $result ? self::SUCCESS : self::ERROR,
        ]);
    }

    public function logout() {
        if (! $this->beforeAction()) {
            $data['status'] = self::ERROR_KEY;
            return response()->json($data);
        }

        $key = \Request::get('key');

        $result = Users::logout(self::$user->id, $key);

        return response()->json([
            'status' => $result ? self::SUCCESS : self::ERROR,
        ]);
    }

    public function setDeviceToken() {
        if (! $this->beforeAction()) {
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
        if (! $this->beforeAction()) {
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
        if (! $this->beforeAction()) {
            $data['status'] = self::ERROR_KEY;
            return response()->json($data);
        }

        $user_id = \Request::get('user_id');

        $profile = Users::getProfile($user_id, self::$user->id);

        if (! $profile) {
            return response()->json([
                'status' => APIController::ERROR,
            ]);
        }

        $photos = UsersPhotos::getUserPhotos($user_id);

        return response()->json([
            'status' => self::SUCCESS,
            'vk_id' => $profile->vk_id,
            'name' => $profile->name,
            'sex' => $profile->sex,
            'age' => $profile->age,
            'about' => $profile->about,
            'last_activity' => $profile->last_activity,
            'distance' => $profile->distance,
            'weight' => $profile->weight,
            'is_deleted' => $profile->is_deleted,
            'photos' => $photos,
        ]);
    }

    public function sendMessageToUser() {
        if (! $this->beforeAction()) {
            $data['status'] = self::ERROR_KEY;
            return response()->json($data);
        }

        $user_id = \Request::get('user_id');
        $text = \Request::get('text');

        if (! Likes::isMutual(self::$user->id, $user_id)) {
            return response()->json([
                'status' => self::ERROR_DISLIKE,
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
        if (! $this->beforeAction()) {
            $data['status'] = self::ERROR_KEY;
            return response()->json($data);
        }

        $user_id = \Request::get('user_id');
        $offset = \Request::get('offset', 0);
        $older_than = \Request::get('older_than');
        $later_than = \Request::get('later_than');

        $messages = Messages::getAllBetweenUsers(self::$user->id, $user_id, $offset, $older_than, $later_than);

        return response()->json([
            'status' => self::SUCCESS,
            'messages' => $messages,
        ]);
    }
}
