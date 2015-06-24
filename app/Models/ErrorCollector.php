<?php namespace App\Models;

class ErrorCollector {

    public static function addError($type, $header, $message) {
        $query = \DB::connection('logs')->select("
            SELECT logs.add_error(?, ?, ?);
        ", [$type, $header, $message]);

        \Queue::push('send_errors', [
            'type' => $type,
            'header' => $header,
            'message' => $message,
        ], 'send_errors');
    }

    public static function addRequest($method, $user_id) {
        $request = $_REQUEST;
        $request['method'] = str_replace('App\Http\Controllers\ApiController::', '', $method);

        foreach (['photo'] as $key) {
            unset($request['key']);
        }

        return \DB::connection('logs')->select("
            SELECT logs.add_request(?, ?);
        ", [json_encode($request, JSON_UNESCAPED_UNICODE), $user_id])[0]->add_request;
    }

    public static function addResponse($request_id, $response, $method, $start_time) {
        \DB::connection('logs')->select("
            UPDATE logs.requests
                SET response = ?
                WHERE id = ?;
        ", [json_encode($response, JSON_UNESCAPED_UNICODE), $request_id]);

        $method = str_replace('App\Http\Controllers\ApiController::', '', $method);

        TIMER('methods.' . $method, microtime(true) - $start_time);
    }

}

