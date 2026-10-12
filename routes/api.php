<?php

use App\Http\Controllers\PayMongoWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/paymongo/webhook', PayMongoWebhookController::class)
    ->name('paymongo.webhook.api');
