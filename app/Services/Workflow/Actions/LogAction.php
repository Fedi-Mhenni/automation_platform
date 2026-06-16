<?php

namespace App\Services\Workflow\Actions;

use App\Services\Workflow\Contracts\ActionInterface;
use App\Services\Workflow\ExecutionContext;
use App\Services\Workflow\NodeResult;
use App\Services\Workflow\TemplateEngine;
use Illuminate\Support\Facades\Log;

class LogAction implements ActionInterface
{
    public function __construct(
        protected TemplateEngine $template
    ) {}
    public static function getType(): string
    {
        return 'action_log';
    }

    public static function getLabel(): string
    {
        return 'Log Message';
    }
    
    public static function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'message' => [
                    'type' => 'string',
                    'title' => 'Message de log'
                ],
            ],
            'required' => ['message'],
        ];
    }

    public function validatePayload(array $payload): void
    {
        if (empty($payload['message'])) {
            throw new \InvalidArgumentException("Le champ 'message' est requis.");
        }
    }

    public function handle(ExecutionContext $context): NodeResult
    {
        $payload = $context->currentNodePayload ?? [];
        $message = $this->template->render($payload['message'] ?? 'Aucun message', $context);

        return NodeResult::success([
            'logged'  => true,
            'message' => $message,
        ]);
    }
}