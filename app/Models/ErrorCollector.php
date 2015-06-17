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

    public static function addRequest() {
        $request = $_REQUEST;
        \DB::connection('logs')->select("
            SELECT logs.add_request(?);
        ", [json_encode($request, JSON_UNESCAPED_UNICODE)]);
    }

}

