<?php

namespace Laraditz\Xendit\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laraditz\Xendit\Exceptions\WebhookException;
use Laraditz\Xendit\Support\SignatureValidator;
use Symfony\Component\HttpFoundation\Response;

class VerifyXenditWebhook
{
    protected SignatureValidator $validator;

    public function __construct(SignatureValidator $validator)
    {
        $this->validator = $validator;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the callback token from header or payload
        $token = $request->header('x-callback-token')
            ?? $request->input('callback_token');

        if (!$this->validator->verifyCallbackToken($token)) {
            throw new WebhookException('Invalid webhook signature.', 401);
        }

        return $next($request);
    }
}
