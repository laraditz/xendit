<?php

namespace Laraditz\Xendit\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Laraditz\Xendit\Support\WebhookHandler;

class WebhookController extends Controller
{
    protected WebhookHandler $handler;

    public function __construct(WebhookHandler $handler)
    {
        $this->handler = $handler;
    }

    /**
     * Handle incoming Xendit webhook
     */
    public function handle(Request $request): JsonResponse
    {
        try {
            $payload = $request->all();

            $this->handler->handle($payload);

            return response()->json(['status' => 'success'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
