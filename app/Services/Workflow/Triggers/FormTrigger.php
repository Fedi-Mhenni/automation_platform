<?php

namespace App\Services\Workflow\Triggers;

use App\Services\Workflow\Contracts\ActionInterface;
use App\Services\Workflow\ExecutionContext;
use App\Services\Workflow\NodeResult;

class FormTrigger implements ActionInterface
{
    public static function getType(): string
    {
        return 'trigger_form';
    }

    public static function getLabel(): string
    {
        return 'Form Trigger';
    }

    public static function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'expected_fields' => [
                    'type'        => 'string',
                    'title'       => 'Champs attendus (autocomplétion)',
                    'description' => 'Clés attendues, séparées par des virgules (ex: email,nom,message). Utilisé uniquement pour l\'autocomplétion dans l\'éditeur.',
                ],
            ],
        ];
    }

    public function validatePayload(array $payload): void {}

    public function handle(ExecutionContext $context): NodeResult
    {
        $data = [];
        foreach ($context->input as $key => $value) {
            if (!is_array($value)) {
                $context->set($key, $value);
                $data[$key] = $value;
            }
        }
        $context->set($context->currentNodeId, $data);

        return NodeResult::success(['trigger' => 'form', 'fields' => $data]);
    }
}