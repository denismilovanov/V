<?php namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Models\Api;
use App\Models\Users;
use App\Models\Abuses;
use App\Models\Stats;
use App\Models\Queues;
use App\Models\SoftVersions;
use App\Models\Likes;
use App\Models\Messages;



class AdminController extends BaseController {
    public function __construct() {
        \Illuminate\Support\Facades\View::share('base', env('ADMIN_RELATIVE_URL'));
    }

    public function beforeAction() {

    }

    public function index() {
        return view('admin.index', [

        ]);
    }

    public function login() {
        if (\Request::isMethod('POST')) {
            if (\Auth::attempt([
                'email' => \Request::get('email'),
                'password' => \Request::get('password'),
            ], true)) {
                return redirect(env('ADMIN_RELATIVE_URL') . '/');
            };
        }
        return view('admin.login', [
            'email' => \Request::get('email'),
            'password' => \Request::get('password'),
        ]);
    }

    public function logout() {
        \Auth::logout();
        return redirect(env('ADMIN_RELATIVE_URL') . '/');
    }

    public function sendRequest() {
        $result = '';

        $result = [
            'response' => '',
            'url' => '',
        ];

        if (\Request::isMethod('POST')) {
            $user_id = \Request::get('user_id', 0);
            $method = \Request::get('method');

            $result = Api::callApiMethod($user_id, $method, \Request::all());
            $result = str_replace('\/', '/', $result);
        }

        return view('admin.tests.sendRequest', [
            'result' => $result,
        ]);
    }

    public function misc() {
        $action = \Request::get('action');

        if ($action == 'unlike_mutual') {
            $user1_id = intval(\Request::get('user1_id', 0));
            $user2_id = intval(\Request::get('user2_id', 0));

            $status = 0;

            if ($user1_id and $user2_id and Likes::isMutual($user1_id, $user2_id)) {
                Likes::deleteAllBetween($user1_id, $user2_id);
                Messages::deleteAllBetween($user1_id, $user2_id);
                $status = 1;
            }

            return response()->json(['status' => $status]);
        }

        return view('admin.tests.misc', [

        ]);
    }

    public function users() {
        $action = \Request::get('action', 'all');
        $ajax = \Request::get('ajax');

        if ($action == 'search') {
            $user_id = intval(\Request::get('user_id'));
            $vk_id = intval(\Request::get('vk_id'));

            if ($user_id and Users::findById($user_id)) {
                return redirect(env('ADMIN_RELATIVE_URL') . '/users/' . $user_id);
            }
            if ($vk_id and $user = Users::findByVkId($vk_id)) {
                return redirect(env('ADMIN_RELATIVE_URL') . '/users/' . $user->id);
            }

            return redirect(env('ADMIN_RELATIVE_URL') . '/users/?not_found');
        }
        if ($action == 'all' and $ajax) {
            $page = \Request::get('page', 0);
            $users = Users::getUsersForAdmin($action, $page, 0);
            return response()->json($users);
        }
        if ($action == 'search_with_abuses' and $ajax) {
            $users = Users::getUsersForAdmin($action, 1000, 0);
            return response()->json($users);
        }

        return view('admin.users.users', [
            'action' => $action,
        ]);
    }

    public function user($user_id) {
        if (\Request::isMethod('POST')) {
            $action = \Request::get('action');

            if ($action == 'block') {
                Users::block($user_id, strip_tags(\Request::get('reason')));
                return redirect(env('ADMIN_RELATIVE_URL') . '/users/' . $user_id);
            } else if ($action == 'unblock') {
                Users::unblock($user_id);
                return redirect(env('ADMIN_RELATIVE_URL') . '/users/' . $user_id);
            } else if ($action == 'remove') {
                Users::removeProfile($user_id, true);
                return redirect(env('ADMIN_RELATIVE_URL') . '/users/' . $user_id);
            } else if ($action == 'unremove') {
                Users::unremove($user_id);
                return redirect(env('ADMIN_RELATIVE_URL') . '/users/' . $user_id);
            } else if ($action == 'remove_abuse') {
                $abuse_id = intval(\Request::get('abuse_id'));
                Abuses::remove($abuse_id);
                return response()->json(['status' => 1]);
            } else if ($action == 'unblock_like') {
                $user_id = intval(\Request::get('user_id'));
                $blocked_user_id = intval(\Request::get('blocked_user_id'));
                Likes::unblockUser($user_id, $blocked_user_id);
                return response()->json(['status' => 1]);
            } else if ($action == 'remove_all_abuses') {
                Abuses::removeAllByToUserId($user_id);
                return response()->json(['status' => 1]);
            }

        }
        return view('admin.users.user', [
            'user' => Users::findById($user_id, 'admin'),
        ]);
    }

    public function stats() {
        if (\Request::isMethod('get')) {
            $action = \Request::get('action');

            if ($action == 'get_ages_data') {
                return response()->json(Stats::getAgesData());
            } else if ($action == 'get_registrations_data') {
                return response()->json(Stats::getRegistrationsData());
            } else if ($action == 'get_activity_data') {
                return response()->json(Stats::getActivityData());
            } else if ($action == 'who_likes_who_data') {
                return response()->json(Stats::whoLikesWhoData());
            } else if ($action == 'gender_data') {
                return response()->json(Stats::genderData());
            } else if ($action == 'likes_activity_data') {
                return response()->json(Stats::getLikesActivityData());
            } else if ($action == 'matches_activity_data') {
                return response()->json(Stats::getMatchesActivityData());
            } else if ($action == 'matches_months_activity_data') {
                return response()->json(Stats::getMatchesMonthsActivityData());
            } else if ($action == 'matches_groups_data') {
                return response()->json(Stats::getMatchesGroupsData());
            }
        }

        return view('admin.stats.all', [

        ]);
    }

    public function geo() {
        if (\Request::isMethod('get')) {
            $action = \Request::get('action');

            if ($action == 'get_geo_data') {
                return response()->json(Stats::getGeoData());
            }
        }

        return view('admin.stats.geo', [

        ]);
    }

    public function push() {
        $user_id = intval(\Request::get('user_id'));
        $action = \Request::get('action');

        if ($action == 'personal_push') {
            $status = Queues::personalPush($user_id, \Request::get('message'));
            return response()->json(['status' => $status]);
        } else if ($action == 'mass_push') {
            $status = Queues::enqueueMassPush(\Request::get('message'));
            return response()->json(['status' => $status]);
        }

        return view('admin.tools.push', [
            'user_id' => $user_id,
        ]);
    }

    public function softVersions() {
        $action = \Request::get('action');

        $id = \Request::get('id');
        $device_type = \Request::get('device_type');
        $description =  strip_tags(\Request::get('description'));

        $version = SoftVersions::findById($id, $device_type);

        if ($action == 'upsert') {
            $status = SoftVersions::upsert($id, $device_type, $description);
            if ($status) {
                return redirect(env('ADMIN_RELATIVE_URL') . '/tools/softVersions');
            }
        } else if ($action == 'make_actual') {
            if ($version) {
                SoftVersions::makeActual($id, $device_type, true);
            }
        } else if ($action == 'make_noactual') {
            if ($version) {
                SoftVersions::makeActual($id, $device_type, false);
            }
        }

        if (! $version) {
            $version = new \stdClass;
            $version->id = $id;
            $version->device_type = $device_type;
            $version->description = $description;
        }

        return view('admin.tools.softVersions', [
            'versions' => SoftVersions::getAll(),
            'version' => $version,
        ]);
    }

}
