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
    private static $user = null;

    public function __construct() {
    }

    public function beforeAction() {
        $key = \Request::get('key');

        $sql = "SELECT u.id, u.sex, u.name, u.about,
                        uivk.token,
                        u.avatar_url,
                        uivk.vk_id,
                        date_part('year', age(u.bdate)) as age
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

        $data = [];

        $soft_version_int = Helper::softVersionFromStringToInt($soft_version);

        if (! $soft_version_int) {
            $data['status'] = self::ERROR;
            return response()->json($data);
        }

        $sql = "SELECT * FROM public.upsert_user_by_vk_id(?, ?, ?, ?, ?, ?) AS t(user_id integer, is_new integer);";
        $user = \DB::select($sql, [$vk_id, $sex, $name, $bdate, $about, $avatar_url])[0];

        $sql = "SELECT public.get_access_key(?, ?, ?, ?);";
        $key = \DB::select($sql, [$user->user_id, $device_token, $device_type, $soft_version_int])[0]->get_access_key;

        $sql = "SELECT * FROM public.get_latest_soft_version(?) AS t(version integer, description text);";
        $soft = \DB::select($sql, [$device_type])[0];

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
            $result = UsersPhotos::addPhoto(
                self::$user->id,
                $_POST['photo'],
                $_POST['extension']
            );
        }

        return response()->json([
            'status' => $result ? self::SUCCESS : self::ERROR,
        ]);

    }

    public function checkin() {
        if (! $this->beforeAction()) {
            $data['status'] = self::ERROR_KEY;
            return response()->json($data);
        }

        $latitude = \Request::get('latitude');
        $longitude = \Request::get('longitude');

        $result = Checkins::checkin(
            self::$user->id,
            floatval($longitude),
            floatval($latitude)
        );

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

        $data = [];

        $data['status'] = self::SUCCESS;
        $data['users'] = array();

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

        $profile = \DB::select("
            SELECT * FROM public.get_user_profile(?, ?);
        ", [$user_id, self::$user->id]);

        if (! isset($profile[0])) {
            return response()->json([
                'status' => APIController::ERROR,
            ]);
        }

        $profile = $profile[0];

        $photos_url = env('PHOTOS_URL');

        $photos_raw = \DB::select("
            SELECT * FROM public.get_user_photos(?);
        ", [$user_id]);

        $photos = array();

        foreach ($photos_raw as $photo) {
            if ($photo->source_id == 0) {
                $photo->url = $photos_url . $photo->url;
            }
            $photos []= array(
                'id' => $photo->id,
                'url' => $photo->url,
                'rank' => $photo->rank,
            );
        }

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

        $message_id = Messages::addMessage(self::$user->id, $user_id, $text);

        return response()->json([
            'status' => $message_id ? self::SUCCESS : self::ERROR,
            'message_id' => $message_id,
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
