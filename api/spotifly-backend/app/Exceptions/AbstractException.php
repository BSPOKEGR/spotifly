<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Lang;
use Throwable;

abstract class AbstractException extends \Exception
{
    public const string TRANSLATION_PREFIX = 'exception';
    protected const int DEFAULT_HTTP_CODE = Response::HTTP_INTERNAL_SERVER_ERROR;
    protected const string DEFAULT_ERROR_MESSAGE = 'server-error';
    protected const string DEFAULT_SEVERITY = 'error';

    protected int $httpCode;


    protected $message;
    protected string $severity;

    /**
     * @var array<string, array<string>>|null
     */
    protected ?array $errors;

    /**
     * @param array<string, array<string>>|null $errors
     */
    public function __construct(
         $message = null,
        ?int $code = null,
        ?array $errors = null,
        ?string $severity = null,
        ?Throwable $previous = null
    ) {
        $this->httpCode = $code ?? static::DEFAULT_HTTP_CODE;
        $this->message = $message ?? static::DEFAULT_ERROR_MESSAGE;
        $this->errors = $this->buildErrors($errors);
        $this->severity = $severity ?? static::DEFAULT_SEVERITY;

        parent::__construct($this->message, $this->httpCode, $previous);
    }

    /**
     * Get Exception Http Code
     */
    public function getHttpCode(): int
    {
        return $this->code;
    }

    /**
     * Get exception message
     */
    public function getExceptionMessage(bool $translate = false): string
    {
        if ($translate) {
            return Lang::get(sprintf('%s.message.%s', static::TRANSLATION_PREFIX, $this->message));
        } else {
            return $this->message;
        }
    }

    /**
     * Get exception errors
     *
     * @return array<string, array<string>>|null
     */
    public function getExceptionErrors(bool $translate = false): ?array
    {
        $errors = $this->errors ?? [];

        foreach ($errors as $attribute => &$messages) {
            foreach ($messages as &$message) {
                // translate messages if required.
                if (true === $translate) {
                    $message = Lang::get(sprintf('%s.message.%s', static::TRANSLATION_PREFIX, $message));
                }

                // replace placeholders
                $message = \str_replace(':attribute', $attribute, $message);
            }
        }

        $this->errors = $errors;

        return $this->errors;
    }

    /**
     * Get Exception Severity.
     */
    public function getSeverity(): string
    {
        return $this->severity;
    }

    /**
     * Get Exception Default Error Message.
     */
    public function getDefaultErrorMessage(bool $translate = false): string
    {
        if ($translate) {
            return Lang::get(sprintf('%s.message.%s', static::TRANSLATION_PREFIX, static::DEFAULT_ERROR_MESSAGE));
        } else {
            return static::DEFAULT_ERROR_MESSAGE;
        }
    }

    /**
     * Define if Error message should be translated before sending response to client.
     */
    public function shouldTranslateErrorMessage(): bool
    {
        return true;
    }

    /**
     * Build exception errors in order to match Laravel validation error structure.
     *
     * @param array<string, mixed>|null $errors
     * @return array<string, array<string>>|null
     */
    private function buildErrors(?array $errors): ?array
    {
        if (null === $errors) {
            return null;
        }

        return [$errors['property'] => $errors['messages']];
    }
}
