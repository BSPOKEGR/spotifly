<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Http\Response;

final class NotFoundException extends AbstractException
{
    protected const int DEFAULT_HTTP_CODE = Response::HTTP_NOT_FOUND;
    protected const string DEFAULT_ERROR_MESSAGE = 'not-found';
    protected const string DEFAULT_SEVERITY = 'warning';
}
