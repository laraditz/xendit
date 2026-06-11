<?php

namespace Laraditz\Xendit\Events;

use Illuminate\Foundation\Events\Dispatchable;

class XenditApiRequesting
{
    use Dispatchable;

    public function __construct(
        public string $method,
        public string $endpoint,
        public array $query = [],
        public array $payload = [],
    ) {
    }
}
