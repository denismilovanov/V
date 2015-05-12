<?php namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;

class SiteController extends BaseController {
    public function __construct() {
    }

    function index() {
        return view('site.index');
    }
}
