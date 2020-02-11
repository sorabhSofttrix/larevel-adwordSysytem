<?php

namespace App\Exceptions;

use Exception;
use Tymon\JWTAuth\Exceptions\JWTException as JWTException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException as TokenInvalidException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException as TokenExpiredException;
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
        if($exception instanceof TokenInvalidException) {
            return response()->json(
                getResponseObject(false, [] , 400, 'Token is invalid')
            , 400);
        } else if($exception instanceof TokenExpiredException) {
            return response()->json(
                getResponseObject(false, [] , 400, 'Token is Expired')
            , 400);
        } else if($exception instanceof JWTException) {
            return response()->json(
                getResponseObject(false, [] , 400, 'issue with the Token')
            , 400);
        } 
        // else {
        //     return response()->json(
        //         [
        //             'status' => false,
        //             'error' => $exception,
        //             'data' => [],
        //             'responseCode' => 500
        //         ]
        //     , 500);
        // }
        return parent::render($request, $exception);
    }
}
