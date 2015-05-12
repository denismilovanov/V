<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

use App\Http\Controllers\ApiController;
use App\Http\Controllers\SiteController;

$app->group(['prefix' => 'api', 'namespace' => 'App\Http\Controllers'], function($app) {
    $app->get('bla', ['uses' => 'ApiController@bla']);
    $app->get('authorizeVK', ['uses' => 'ApiController@authorizeVK']);
    $app->post('syncGroupsVK', ['uses' => 'ApiController@syncGroupsVK']);
    $app->post('syncFriendsVK', ['uses' => 'ApiController@syncFriendsVK']);
    $app->post('uploadPhoto', ['uses' => 'ApiController@uploadPhoto']);
    $app->get('checkIn', ['uses' => 'ApiController@checkIn']);
    $app->get('getUserProfile', ['uses' => 'ApiController@getUserProfile']);
    $app->get('getMySettings', ['uses' => 'ApiController@getMySettings']);
    $app->get('setMySettings', ['uses' => 'ApiController@setMySettings']);
    $app->get('searchAround', ['uses' => 'ApiController@searchAround']);
    $app->get('like', ['uses' => 'ApiController@like']);
    $app->get('blockUser', ['uses' => 'ApiController@blockUser']);
    $app->get('abuse', ['uses' => 'ApiController@abuse']);
    $app->get('getMyMessages', ['uses' => 'ApiController@getMyMessages']);
    $app->get('setDeviceToken', ['uses' => 'ApiController@setDeviceToken']);
    $app->get('logout', ['uses' => 'ApiController@logout']);
    $app->get('removeProfile', ['uses' => 'ApiController@removeProfile']);
});

$app->get('', ['uses' => '\App\Http\Controllers\SiteController@index']);
