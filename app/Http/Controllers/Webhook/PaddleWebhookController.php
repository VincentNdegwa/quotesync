<?php

namespace App\Http\Controllers\Webhook;

use Laravel\Paddle\Http\Controllers\WebhookController as CashierWebhookController;

class PaddleWebhookController extends CashierWebhookController
{
    protected function handleSubscriptionCreated($payload)
    {
        parent::handleSubscriptionCreated($payload);

        $customData = $payload['data']['custom_data'] ?? [];
        $workspaceId = $customData['workspace_id'] ?? null;
        $planId = $customData['plan_id'] ?? null;

        if ($workspaceId && $planId) {
            \App\Models\Workspace::where('id', $workspaceId)
                ->update(['plan_id' => $planId]);
        }
    }

    protected function handleTransactionCompleted($payload)
    {
        parent::handleTransactionCompleted($payload);

        $customData = $payload['data']['custom_data'] ?? [];
        $workspaceId = $customData['workspace_id'] ?? null;
        $planId = $customData['plan_id'] ?? null;

        if ($workspaceId && $planId) {
            \App\Models\Workspace::where('id', $workspaceId)
                ->update(['plan_id' => $planId]);
        }
    }
}
