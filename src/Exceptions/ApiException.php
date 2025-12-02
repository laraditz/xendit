<?php

namespace Laraditz\Xendit\Exceptions;

class ApiException extends XenditException
{
    protected array $response;

    public function __construct(string $message, int $code = 0, array $response = [])
    {
        parent::__construct($message, $code);
        $this->response = $response;
    }

    public function getResponse(): array
    {
        return $this->response;
    }
}
