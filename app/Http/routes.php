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

if (! isset($_SERVER['SERVER_NAME'])) {
    $_SERVER['SERVER_NAME'] = '';
}

$app->group(['prefix' => 'api', 'namespace' => 'App\Http\Controllers'], function($app) {
    $app->get('authorizeVK', ['uses' => 'ApiController@authorizeVK']);

    $app->post('syncGroupsVK', ['uses' => 'ApiController@syncGroupsVK']);
    $app->post('syncFriendsVK', ['uses' => 'ApiController@syncFriendsVK']);
    $app->post('syncProfileVK', ['uses' => 'ApiController@syncProfileVK']);

    $app->post('uploadPhoto', ['uses' => 'ApiController@uploadPhoto']);
    $app->get('removePhoto', ['uses' => 'ApiController@removePhoto']);

    $app->get('checkIn', ['uses' => 'ApiController@checkIn']);
    $app->get('getUserProfile', ['uses' => 'ApiController@getUserProfile']);

    $app->get('getMySettings', ['uses' => 'ApiController@getMySettings']);
    $app->get('setMySettings', ['uses' => 'ApiController@setMySettings']);

    $app->post('setPhotosVK', ['uses' => 'ApiController@setPhotosVK']);
    $app->get('getPhotosVK', ['uses' => 'ApiController@getPhotosVK']);

    $app->get('setBDate', ['uses' => 'ApiController@setBDate']);

    $app->get('setAbout', ['uses' => 'ApiController@setAbout']);
    $app->get('getAbout', ['uses' => 'ApiController@getAbout']);

    $app->get('searchAround', ['uses' => 'ApiController@searchAround']);

    $app->get('like', ['uses' => 'ApiController@like']);
    $app->get('blockUser', ['uses' => 'ApiController@blockUser']);
    $app->get('abuse', ['uses' => 'ApiController@abuse']);

    $app->get('getMyMessages', ['uses' => 'ApiController@getMyMessages']);
    $app->get('sendMessageToUser', ['uses' => 'ApiController@sendMessageToUser']);
    $app->get('deleteMessage', ['uses' => 'ApiController@deleteMessage']);
    $app->get('getMessagesWithUser', ['uses' => 'ApiController@getMessagesWithUser']);
    $app->get('deleteMessagesWithUser', ['uses' => 'ApiController@deleteMessagesWithUser']);

    $app->get('setDeviceToken', ['uses' => 'ApiController@setDeviceToken']);
    $app->get('logout', ['uses' => 'ApiController@logout']);
    $app->get('removeProfile', ['uses' => 'ApiController@removeProfile']);
});

$app->get('', ['uses' => '\App\Http\Controllers\SiteController@index']);

$app->group(['prefix' => 'admin', 'namespace' => 'App\Http\Controllers'], function($app) {
    $app->get('', ['uses' => 'AdminController@index']);
    $app->get('login', ['uses' => 'AdminController@login']);
    $app->post('login', ['uses' => 'AdminController@login']);
    $app->get('logout', ['uses' => 'AdminController@logout']);

    $app->get('tests/sendRequest', ['uses' => 'AdminController@sendRequest']);
    $app->post('tests/sendRequest', ['uses' => 'AdminController@sendRequest']);

    $app->get('users/', ['uses' => 'AdminController@users']);
    $app->get('users/{user_id}', ['uses' => 'AdminController@user']);
    $app->post('users/{user_id}', ['uses' => 'AdminController@user']);

    $app->get('stats/', ['uses' => 'AdminController@stats']);

    $app->get('tools/push', ['uses' => 'AdminController@push']);
    $app->get('tools/softVersions', ['uses' => 'AdminController@softVersions']);
});






