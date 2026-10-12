<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MongoSprout\PayMongo\Webhooks\WebhookController as PayMongoBridgeWebhookController;

class PayMongoWebhookController
{
    public function __invoke(Request $request, PayMongoBridgeWebhookController $webhook): JsonResponse
    {
        return $webhook($request);
    }
}