<?php namespace App\Exceptions;

use Exception;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler {

    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        'Symfony\Component\HttpKernel\Exception\HttpException'
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $e
     * @return void
     */
    public function report(Exception $e)
    {
        $type = '';
        $message = '';

        if ($e instanceof \Illuminate\Database\QueryException) {
            $type = 'SQL_ERROR';
            $message = $e->getMessage() . "\n\n" . $e->getTraceAsString();
        } else if ($e instanceof \ErrorException) {
            $type = 'PHP_ERROR';
            $message = $e->getMessage() . "\n\n" . $e->getTraceAsString();
        }

        if (isset($_SERVER)) {
            $message .= "\n\n" . print_r($_SERVER, true);
        }

        if ($type) {
            \App\Models\ErrorCollector::addError(
                $type,
                $e->getFile() . ':' . $e->getLine(),
                $message
            );
        }
        return parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $e)
    {
        return parent::render($request, $e);
    }

}
