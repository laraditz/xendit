<?php

use Illuminate\Support\Facades\Route;
use Laraditz\Xendit\Http\Controllers\WebhookController;
use Laraditz\Xendit\Http\Middleware\VerifyXenditWebhook;

Route::post('/xendit/webhook', [WebhookController::class, 'handle'])
    ->middleware(VerifyXenditWebhook::class)
    ->name('xendit.webhook');
