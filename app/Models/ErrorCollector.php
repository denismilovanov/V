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

}

