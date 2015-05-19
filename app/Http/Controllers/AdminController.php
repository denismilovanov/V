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
        return view('admin.tests.sendRequest', [
        ]);
    }
}
