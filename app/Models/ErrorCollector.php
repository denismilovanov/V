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

    public static function addRequest($method) {
        $request = $_REQUEST;
        $request['method'] = str_replace('App\Http\Controllers\ApiController::', '', $method);

        foreach (['photo'] as $key) {
            unset($request['key']);
        }

        \DB::connection('logs')->select("
            SELECT logs.add_request(?);
        ", [json_encode($request, JSON_UNESCAPED_UNICODE)]);
    }

}

