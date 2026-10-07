<?php

namespace App\Shared\Exception;

class ValidationException extends \InvalidArgumentException
{
    public function __construct(string $message = '', ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    public function getErrorCode(): string
    {
        return ErrorCode::VALIDATION_ERROR->value;
    }
}
