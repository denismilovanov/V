<?php namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Models\Api;
use App\Models\Users;
use App\Models\Abuses;


class AdminController extends BaseController {
    public function __construct() {
    }

    public function beforeAction() {

    }

    public function index() {
        if (! \Auth::check()) {
            return redirect('/login');
        }
        return view('admin.index', [

        ]);
    }

    public function login() {
        if (\Request::isMethod('POST')) {
            if (\Auth::attempt([
                'email' => \Request::get('email'),
                'password' => \Request::get('password'),
            ], true)) {
                return redirect('/');
            };
        }
        return view('admin.login', [
            'email' => \Request::get('email'),
            'password' => \Request::get('password'),
        ]);
    }

    public function logout() {
        \Auth::logout();
        return redirect('/');
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
        }

        return view('admin.tests.sendRequest', [
            'result' => $result,
        ]);
    }

    public function users() {
        $action = \Request::get('action', 'all');

        if ($action == 'search') {
            $user_id = intval(\Request::get('user_id'));
            $vk_id = intval(\Request::get('vk_id'));

            if ($user_id and Users::findById($user_id)) {
                return redirect('/users/' . $user_id);
            }
            if ($vk_id and $user = Users::findByVkId($vk_id)) {
                return redirect('/users/' . $user->id);
            }
        }

        $limit = env('STD_PAGING_COUNT', 50);
        $page = \Request::get('page', 0);

        $offset = $limit * $page;

        return view('admin.users.users', [
            'users' => Users::getUsersForAdmin($action, $limit, $offset),
            'page' => $page,
        ]);
    }

    public function user($user_id) {
        if (\Request::isMethod('POST')) {
            $action = \Request::get('action');

            if ($action == 'block') {
                Users::block($user_id);
                return redirect('/users/' . $user_id);
            } else if ($action == 'unblock') {
                Users::unblock($user_id);
                return redirect('/users/' . $user_id);
            } else if ($action == 'remove_abuse') {
                $abuse_id = intval(\Request::get('abuse_id'));
                Abuses::remove($abuse_id);
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
}
