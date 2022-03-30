<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadMethodCallException;
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
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */


    // public function report(Exception $exception)
    // {
    //   dd($exception);

    //   return parent::report($exception);
    // }

    public function register()
    {

      $this->renderable(function (ValidationException $exception, $request) {
        return response()->json([
          "code" => 422,
          "message" => $exception->getMessage(),
          "errors" => $exception->errors()
        ], 422);

      });

      $this->renderable(function (AuthenticationException $exception, $request) {
        return response()->json([
          "code" => 401,
          "message" => $exception->getMessage(),
          "result" => []
        ], 401);
      });

      $this->renderable(function (NotFoundHttpException $exception) {
        return response()->json([
          "code" => 404,
          "message" =>"API Not Found, Check the route in backend.",
          "result" => []
        ], 404);
      });

      $this->renderable(function (BadMethodCallException $exception){
        return response()->json([
          "code"=>404,
          "message" =>"Method Not Found.",
          "result" => []
        ], 404);
      });
    }
}
