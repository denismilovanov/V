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
        if ($e instanceof \Illuminate\Database\QueryException) {
            $type = 'SQL_ERROR';
        } else if ($e instanceof \ErrorException) {
            $type = 'PHP_ERROR';
        }

        if ($type) {
            \App\Models\ErrorCollector::addError(
                $type,
                $e->getFile() . ':' . $e->getLine(),
                $e->getMessage()
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
