<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param \Exception $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Exception               $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        parent::render($request, $exception);
        $code    = method_exists($exception, "getStatusCode") ? $exception->getStatusCode() : 500;
        $message = $exception->getMessage();
        $message = $code == 404 ? "route not allowed" : $message;
        $message = $code == 405 ? "request method not allowed" : $message;

        if ($code == 500) {
            $message = $message . " in " . $exception->getFile() . ":" . $exception->getLine();
        }

        if ($code == 500 && !env("APP_DEBUG")) $message = "服务器错误";

        $tmp = json_decode($message, true);

        $message = is_array($tmp) ? $tmp : ['detail' => $message];

        return response()
            ->json($message, $code);
    }
}
