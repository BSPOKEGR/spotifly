<?php

use App\Exceptions\AbstractException;
use App\Exceptions\Enums\ExceptionsEnum;
use App\Exceptions\InternalServerErrorException;
use App\Exceptions\InvalidCredentialsException;
use App\Exceptions\InvalidVerificationSignatureException;
use App\Http\Middleware\AuthenticateWithGatekeeper;
use App\Http\Middleware\EnsureRequestsAreStateful;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\IpBlocker;
use App\Http\Middleware\Localization;
use App\Http\Middleware\SetPaginationHeaders;
use App\Http\Middleware\ValidateSignedRequest;
use App\Http\Middleware\VerifyOrigin;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;
use Laravel\Horizon\Exceptions\ForbiddenException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        apiPrefix: '/',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->statefulApi();
        $middleware->validateCsrfTokens(except: [
            'auth/verify/*',
            'api/*',
        ]);

        $middleware->alias([
            'signed' => ValidateSignedRequest::class,
            'pagination.headers' => SetPaginationHeaders::class,
            'auth.gatekeeper' => AuthenticateWithGatekeeper::class,
        ]);

        $middleware->api(append: [
            Localization::class,
        ], prepend: [
            EnsureRequestsAreStateful::class,
            ForceJsonResponse::class,
            'throttle:api',
        ]);

        $middleware->web(append: [
            IpBlocker::class,
            VerifyOrigin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (InvalidVerificationSignatureException $e) {
            $error = ExceptionsEnum::INVALID_VERIFICATION_SIGNATURE_EXCEPTION->formatExceptionResponse($e);

            return new JsonResponse($error, $error['code']);
        });

        $exceptions->render(function (NotFoundHttpException $e) {
            $error = [
                'code' => Response::HTTP_NOT_FOUND,
                'description' => 'Not Found',
            ];

            Log::warning(
                get_class($e),
                array_merge(['userId' => Auth::id()], ['message' => $e->getMessage()])
            );

            return new JsonResponse($error, $error['code']);
        });

        $exceptions->render(function (UnauthorizedException|UnauthorizedHttpException|AuthenticationException|ForbiddenException $e) {
            // telescope and horizon uses season guard and it throws UnauthorizedHttpException
            // like cookie authentication and we should not return json response for them
            // and any other UnauthorizedHttpException that is thrown by AuthenticateWithBasicAuth middleware
            if ($e instanceof UnauthorizedHttpException) {
                $headers = $e->getHeaders();

                if (isset($headers['WWW-Authenticate']) && 'Basic' === $headers['WWW-Authenticate']) {
                    return new JsonResponse([], Response::HTTP_UNAUTHORIZED, $e->getHeaders());
                }
            }

            Log::error(
                get_class($e),
                array_merge(['userId' => Auth::id()], ['message' => $e->getMessage()]),
            );

            Log::channel('slack')->error(
                get_class($e),
                array_merge(['userId' => Auth::id()], ['message' => $e->getMessage()]),
            );

            $error = [
                'code' => Response::HTTP_UNAUTHORIZED,
                'description' => 'Unauthorized',
            ];

            return new JsonResponse($error, $error['code']);
        });

        $exceptions->render(function (InvalidCredentialsException $e) {
            $error = [
                'code' => Response::HTTP_UNAUTHORIZED,
                'description' => 'The user credentials were incorrect.',
            ];

            Log::warning(
                get_class($e),
                array_merge(['userId' => Auth::id()], ['message' => $e->getMessage()]),
            );

            return new JsonResponse($error, $error['code']);
        });

        $exceptions->render(function (AccessDeniedHttpException $e) {
            $error = [
                'code' => Response::HTTP_FORBIDDEN,
                'description' => 'Forbidden.',
            ];

            Log::critical(
                get_class($e),
                array_merge(['userId' => Auth::id()], ['message' => $e->getMessage()]),
            );

            Log::channel('slack')->critical(
                get_class($e),
                array_merge(['userId' => Auth::id()], ['message' => $e->getMessage()]),
            );

            return new JsonResponse($error, $error['code']);
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            $error = [
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'description' => 'Validation error',
                'errors' => $e->errors(),
            ];

            Log::info(
                get_class($e),
                array_merge(['userId' => Auth::id()], ['errors' => $error['errors']]),
            );

            return new JsonResponse($error, $error['code']);
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e) {
            $error = [
                'code' => Response::HTTP_METHOD_NOT_ALLOWED,
                'description' => 'Method not allowed',
            ];

            Log::warning(
                get_class($e),
                array_merge(['userId' => Auth::id()], ['message' => $e->getMessage()]),
            );

            return new JsonResponse($error, $error['code']);
        });

        $exceptions->render(function (ThrottleRequestsException $e) {
            $error = [
                'code' => Response::HTTP_TOO_MANY_REQUESTS,
                'description' => 'Too many requests',
            ];

            Log::warning(
                get_class($e),
                array_merge(['userId' => Auth::id()], ['message' => $e->getMessage()]),
            );

            return new JsonResponse($error, $error['code']);
        });

        $exceptions->render(function (InternalServerErrorException $e) {
            $error = [
                'code' => $e->getHttpCode(),
                'description' => $e->getDefaultErrorMessage(true),
            ];

            Log::{$e->getSeverity()}(
                get_class($e),
                array_merge(['userId' => Auth::id()], ['message' => $e->getDefaultErrorMessage(true)]),
            );

            Log::channel('slack')->{$e->getSeverity()}(
                get_class($e),
                array_merge(['userId' => Auth::id()], ['message' => $e->getDefaultErrorMessage(true)]),
            );

            return new JsonResponse($error, $error['code']);
        });

        $exceptions->render(function (AbstractException $e) {
            $translate = $e->shouldTranslateErrorMessage();
            $error = [
                'code' => $e->getHttpCode(),
                'description' => $e->getExceptionMessage($translate),
            ];

            $exceptionErrors = $e->getExceptionErrors(true);
            if (null !== $exceptionErrors) {
                $error['errors'] = $exceptionErrors;
            }

            Log::{$e->getSeverity()}(
                get_class($e),
                array_merge(['userId' => Auth::id()], ['message' => $e->getDefaultErrorMessage(true)]),
            );

            return new JsonResponse($error, $error['code']);
        });

        $exceptions->render(function (ForbiddenException  $e) {
            $error = [
                'code' => Response::HTTP_FORBIDDEN,
                'description' => 'Forbidden.',
            ];

            Log::critical(
                get_class($e),
                array_merge(['userId' => Auth::id()], ['message' => $e->getMessage()]),
            );

            Log::channel('slack')->critical(
                get_class($e),
                array_merge(['userId' => Auth::id()], ['message' => $e->getMessage()]),
            );

            return new JsonResponse($error, $error['code']);
        });

        $exceptions->render(function (BadRequestHttpException $e) {
            $error = [
                'code' => Response::HTTP_BAD_REQUEST,
                'description' => 'Bad Request',
            ];

            Log::warning(
                get_class($e),
                array_merge(['userId' => Auth::id()], ['message' => $e->getMessage()]),
            );

            return new JsonResponse($error, $error['code'], []);
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, Request $request) {
            $exception = $e->getPrevious();

            $error = [
                'code' => $e->getCode(),
                'description' => $e->getMessage(),
                'severity' => 'error',
            ];

            if ($exception instanceof AuthorizationException) {
                $error = [
                    'code' => Response::HTTP_FORBIDDEN,
                    'description' => 'Forbidden',
                    'severity' => 'critical',
                ];
            } elseif ($exception instanceof TokenMismatchException) {
                $error = [
                    'code' => Response::HTTP_UNAUTHORIZED,
                    'description' => 'CSRF token mismatch.',
                    'severity' => 'warning',
                ];
            }

            Log::{$error['severity']}(
                get_class($e),
                array_merge(['userId' => Auth::id()], ['message' => $e->getMessage(), 'errors' => [$request->path()]]),
            );

            Log::channel('slack')->{$error['severity']}(
                get_class($e),
                array_merge(['userId' => Auth::id()], ['message' => $e->getMessage(), 'errors' => [$request->path()]]),
            );
            unset($error['severity']);

            return new JsonResponse($error, $error['code']);
        });

        $exceptions->render(function (\Exception $e) {
            // do not render UnauthorizedHttpException thrown by AuthenticateWithBasicAuth middleware
            if ($e instanceof UnauthorizedHttpException) {
                Log::channel('slack')->error(
                    get_class($e),
                    ['message' => $e->getMessage()],
                );

                return new JsonResponse([], Response::HTTP_UNAUTHORIZED, $e->getHeaders());
            }

            $error = [
                'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'description' => 'Unexpected error',
            ];

            Log::error(
                get_class($e),
                ['message' => $e->getMessage()],
            );

            Log::channel('slack')->error(
                get_class($e),
                ['message' => $e->getMessage()],
            );

            return new JsonResponse($error, $error['code']);
        });
    })->withEvents(false)
    ->create();
