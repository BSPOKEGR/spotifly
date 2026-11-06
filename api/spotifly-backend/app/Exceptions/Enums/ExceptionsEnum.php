<?php

declare(strict_types=1);

namespace App\Exceptions\Enums;

use App\Exceptions\AbstractException;
use App\Exceptions\InternalServerErrorException;
use App\Exceptions\InvalidCredentialsException;
use App\Exceptions\InvalidVerificationSignatureException;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Laravel\Horizon\Exceptions\ForbiddenException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

enum ExceptionsEnum: string
{
    case NOT_FOUND_HTTP_EXCEPTION = NotFoundHttpException::class;
    case UNAUTHORIZED_EXCEPTION = UnauthorizedException::class;
    case UNAUTHORIZED_HTTP_EXCEPTION = UnauthorizedHttpException::class;
    case AUTHENTICATION_EXCEPTION = AuthenticationException::class;
    case INVALID_CREDENTIALS_EXCEPTION = InvalidCredentialsException::class;
    case ACCESS_DENIED_HTTP_EXCEPTION = AccessDeniedHttpException::class;
    case VALIDATION_EXCEPTION = ValidationException::class;
    case INVALID_ARGUMENT_EXCEPTION = InvalidArgumentException::class;
    case METHOD_NOT_ALLOWED_HTTP_EXCEPTION = MethodNotAllowedHttpException::class;
    case THROTTLE_REQUESTS_EXCEPTION = ThrottleRequestsException::class;
    case INTERNAL_SERVER_ERROR_EXCEPTION = InternalServerErrorException::class;
    case ABSTRACT_EXCEPTION = AbstractException::class;
    case FORBIDDEN_EXCEPTION = ForbiddenException::class;
    case BAD_REQUEST_HTTP_EXCEPTION = BadRequestHttpException::class;
    case AUTHORIZATION_EXCEPTION = AuthorizationException::class;
    case TOKEN_MISMATCH_EXCEPTION = TokenMismatchException::class;
    case HTTP_EXCEPTION = HttpException::class;
    case EXCEPTION = Exception::class;
    case INVALID_VERIFICATION_SIGNATURE_EXCEPTION = InvalidVerificationSignatureException::class;

    /**
     * @return  array<string, string|int> $items
     */
    public function formatExceptionResponse(object $exception): array
    {
        $errors = [];

        switch ($this) {
            case self::NOT_FOUND_HTTP_EXCEPTION:
                $this->logger($exception, 'warning', $this->getMessage($exception));
                $errors = [
                    'code' => Response::HTTP_NOT_FOUND,
                    'description' => 'Not Found',
                    'internal_code' => 340401,
                    'internal_description' => 'not-found',
                ];
                break;
            case self::UNAUTHORIZED_EXCEPTION:
                $this->logger($exception, 'error', $this->getMessage($exception), true);
                $errors = [
                    'code' => Response::HTTP_UNAUTHORIZED,
                    'description' => 'Unauthorized',
                    'internal_code' => 140101,
                    'internal_description' => 'authentication',
                ];
                break;
            case self::AUTHENTICATION_EXCEPTION:
                $this->logger($exception, 'error', $this->getMessage($exception), true);
                $errors = [
                    'code' => Response::HTTP_UNAUTHORIZED,
                    'description' => 'Unauthorized',
                    'internal_code' => 140102,
                    'internal_description' => 'authentication',
                ];
                break;
            case self::INVALID_VERIFICATION_SIGNATURE_EXCEPTION:
                $this->logger($exception, 'warning', $this->getMessage($exception));
                $errors = [
                    'code' => Response::HTTP_UNAUTHORIZED,
                    'description' => 'Unauthorized',
                    'internal_code' => 140103,
                    'internal_description' => 'invalid-signature',
                ];
                break;
            case self::INVALID_CREDENTIALS_EXCEPTION:
                $this->logger($exception, 'warning', $this->getMessage($exception));
                $errors = [
                    'code' => Response::HTTP_UNAUTHORIZED,
                    'description' => 'The user credentials were incorrect.',
                    'internal_code' => 140104,
                    'internal_description' => 'invalid-credentials',
                ];
                break;
            case self::FORBIDDEN_EXCEPTION:
            case self::ACCESS_DENIED_HTTP_EXCEPTION:
                $this->logger($exception, 'critical', $this->getMessage($exception), true);
                $errors = [
                    'code' => Response::HTTP_FORBIDDEN,
                    'description' => 'Forbidden.',
                    'internal_code' => 240301,
                    'internal_description' => 'access-denied',
                ];
                break;
            case self::VALIDATION_EXCEPTION:
                $this->logger($exception, 'info', $this->getMessage($exception));
                $errors = [
                    'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'description' => 'Validation error',
                    'errors' => $exception->errors(),
                    'internal_code' => 442201,
                    'internal_description' => 'validation-error',
                ];
                break;
            case self::INVALID_ARGUMENT_EXCEPTION:
                $this->logger($exception, 'info', $this->getMessage($exception));
                $errors = [
                    'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'description' => 'Validation error',
                    'errors' => $exception->errors(),
                    'internal_code' => 442202,
                    'internal_description' => 'validation-error',
                ];
                break;
            case self::METHOD_NOT_ALLOWED_HTTP_EXCEPTION:
                $this->logger($exception, 'warning', $this->getMessage($exception));
                $errors = [
                    'code' => Response::HTTP_METHOD_NOT_ALLOWED,
                    'description' => 'Method not allowed',
                    'internal_code' => 540501,
                    'internal_description' => 'method-not-allowed',
                ];
                break;
            case self::THROTTLE_REQUESTS_EXCEPTION:
                $this->logger($exception, 'warning', $this->getMessage($exception));
                $errors = [
                    'code' => Response::HTTP_TOO_MANY_REQUESTS,
                    'description' => 'Too many requests',
                    'internal_code' => 642901,
                    'internal_description' => 'too-many-requests',
                ];
                break;
            case self::INTERNAL_SERVER_ERROR_EXCEPTION:
                $this->logger($exception, $exception->getSeverity(), $exception->getDefaultErrorMessage(true), withSlack: true);
                $errors = [
                    'code' => $exception->getHttpCode(),
                    'description' => $exception->getDefaultErrorMessage(true),
                    'internal_code' => 750001,
                    'internal_description' => 'internal-error',
                ];
                break;
            case self::BAD_REQUEST_HTTP_EXCEPTION:
                $this->logger($exception, 'warning', $this->getMessage($exception));
                $errors = [
                    'code' => Response::HTTP_BAD_REQUEST,
                    'description' => 'Bad Request',
                    'internal_code' => 840001,
                    'internal_description' => 'bad-request',
                ];
                break;
            case self::AUTHORIZATION_EXCEPTION:
                $this->logger($exception, 'critical', $this->getMessage($exception), true);
                $errors = [
                    'code' => Response::HTTP_FORBIDDEN,
                    'description' => 'Forbidden',
                    'internal_code' => 240302,
                    'internal_description' => 'forbidden',
                ];
                break;
            case self::TOKEN_MISMATCH_EXCEPTION:
                $this->logger($exception, 'warning', $this->getMessage($exception), true);
                $errors = [
                    'code' => Response::HTTP_UNAUTHORIZED,
                    'description' => 'CSRF token mismatch.',
                    'internal_code' => 140105,
                    'internal_description' => 'csrf-token-mismatch',
                ];
                break;
            case self::ABSTRACT_EXCEPTION:
                $this->logger($exception, $exception->getSeverity(), $exception->getDefaultErrorMessage(true));
                $errors = [
                    'code' => $exception->getHttpCode(),
                    'internal_code' => 750002,
                    'internal_description' => 'server-error',
                ];
                break;
            case self::HTTP_EXCEPTION:
                $this->logger($exception, 'error', $this->getMessage($exception), true);
                $errors = [
                    'code' => $exception->getCode(),
                    'description' => $exception->getMessage(),
                    'internal_code' => 140106,
                    'internal_description' => 'http-error',
                ];
                break;
            case self::UNAUTHORIZED_HTTP_EXCEPTION:
                $this->logger($exception, 'error', $this->getMessage($exception), true, authorized: false);
                $errors = [
                    'code' => Response::HTTP_UNAUTHORIZED,
                    'description' => $exception->getMessage(),
                    'internal_code' => 140107,
                    'internal_description' => 'unauthorized-http-error',
                ];
                break;
            case self::EXCEPTION:
                $this->logger($exception, 'error', $this->getMessage($exception), true, authorized: false);
                $errors = [
                    'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'description' => 'Unexpected error',
                    'internal_code' => 750003,
                    'internal_description' => 'server-error',
                ];
                break;
        }

        return $errors;
    }

    private function logger(
        object $exception,
        string $severity,
        string $message,
        bool $withSlack = false,
        bool $authorized = true
    ): void {
        $logData = array_merge(
            $authorized ? ['userId' => Auth::id()] : [],
            ['message' => $message]
        );

        if ($withSlack) {
            Log::channel('slack')->{$severity}(get_class($exception), $logData);
        }

        Log::{$severity}(get_class($exception), $logData);
    }

    private function getMessage(object $exception): string
    {
        return $exception instanceof AbstractException
            ? $exception->getExceptionMessage() : $exception->getMessage();
    }
}
