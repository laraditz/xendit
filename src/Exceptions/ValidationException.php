<?php

namespace Laraditz\Xendit\Exceptions;

class ValidationException extends XenditException
{
    protected array $errors;

    public function __construct(string $message, array $errors = [], int $code = 0)
    {
        parent::__construct($message, $code);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
