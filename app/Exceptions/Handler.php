<?php

namespace App\Exceptions;

use App\Http\Controllers\ApiException;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Illuminate\Session\TokenMismatchException::class,
        \Illuminate\Validation\ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        if ($exception instanceof ApiException) {//allen api 异常捕获
            $crossDomain = config('site.crossDomain');
            $headers = $request->header();
            $responseHeaders = [];
            if (isset($headers['origin']) && preg_match('%'.$crossDomain.'%i', $headers['origin'][0])) {
                $responseHeaders = ['Access-Control-Allow-Credentials'=>'true', 'Access-Control-Allow-Origin'=>'*'];
            }
            return response()->json([
                'state' => [
                    "code" => $exception->getCode(),// 0=成功 非0=失败
                    "msg"  => $exception->getMessage(),//失败理由
                ],
                'data' => new \stdClass(),
                'cookies' => new \stdClass(),
            ], 200, $responseHeaders);
        }
        return parent::render($request, $exception);
    }

    /**
     * Convert an authentication exception into an unauthenticated response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Illuminate\Http\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        return redirect()->guest('login');
    }
}
