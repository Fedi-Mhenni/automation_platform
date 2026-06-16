<?php

namespace App\Services\Workflow\Triggers;

use App\Services\Workflow\Contracts\ActionInterface;
use App\Services\Workflow\ExecutionContext;
use App\Services\Workflow\NodeResult;

class WebhookTrigger implements ActionInterface
{
    public static function getType(): string
    {
        return 'trigger_webhook';
    }

    public static function getLabel(): string
    {
        return 'Webhook Trigger';
    }

    public static function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'expected_fields' => [
                    'type'        => 'string',
                    'title'       => 'Champs attendus (autocomplétion)',
                    'description' => 'Clés JSON attendues, séparées par des virgules (ex: email,montant,produit). Utilisé uniquement pour l\'autocomplétion dans l\'éditeur.',
                ],
            ],
        ];
    }

    public function validatePayload(array $payload): void
    {
        // No server-side validation needed for expected_fields (it's a UI hint only)
    }

    public function handle(ExecutionContext $context): NodeResult
    {
        $webhookData = $context->input;

        foreach ($webhookData as $key => $value) {
            if (!is_array($value)) {
                $context->set($key, $value);
            }
        }
        $context->set($context->currentNodeId, $webhookData);

        return NodeResult::success([
            'trigger' => 'webhook',
            'data'    => $webhookData,
        ]);
    }
}
