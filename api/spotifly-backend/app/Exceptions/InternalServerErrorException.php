<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Http\Response;

class InternalServerErrorException extends AbstractException
{
    protected const int DEFAULT_HTTP_CODE = Response::HTTP_INTERNAL_SERVER_ERROR;
    protected const string DEFAULT_ERROR_MESSAGE = 'server-error';
    protected const string DEFAULT_SEVERITY = 'critical';
}
