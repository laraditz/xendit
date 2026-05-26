<?php

namespace Laraditz\Xendit\Client\Concerns;

trait HasApiVersion
{
    protected ?string $defaultApiVersion = null;
    protected ?string $apiVersionKey = null;

    protected function resolveHeaders(array $callerHeaders): array
    {
        // $headers starts as a copy of $callerHeaders so all caller values are
        // already present in the returned array for non-api-version keys.
        $headers = $callerHeaders;

        // Step 1: caller explicitly set (or suppressed) api-version
        if (array_key_exists('api-version', $callerHeaders)) {
            if ($callerHeaders['api-version'] === null) {
                unset($headers['api-version']);
            }
            // non-null value is already in $headers via the initialisation above
            return $headers;
        }

        // Step 2: config override — must use array_key_exists on the raw array,
        // not config() directly, because config() returns null for both "key absent"
        // and "key set to null", making them indistinguishable.
        $versions = config('xendit.api_versions', []);
        if ($this->apiVersionKey !== null && $this->apiVersionKey !== '' && array_key_exists($this->apiVersionKey, $versions)) {
            $version = $versions[$this->apiVersionKey];
            if ($version !== null) {
                $headers['api-version'] = $version;
            }
            return $headers;
        }

        // Step 3: service hardcoded default
        if ($this->defaultApiVersion !== null) {
            $headers['api-version'] = $this->defaultApiVersion;
        }

        return $headers;
    }
}
