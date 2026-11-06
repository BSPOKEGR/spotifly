<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Http\Response;

final class InvalidVerificationSignatureException extends AbstractException
{
    protected const int DEFAULT_HTTP_CODE = Response::HTTP_UNAUTHORIZED;
    protected const string DEFAULT_ERROR_MESSAGE = 'invalid-signature';
    protected const string DEFAULT_SEVERITY = 'info';
}
