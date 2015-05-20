<?php namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Models\Api;

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
}
