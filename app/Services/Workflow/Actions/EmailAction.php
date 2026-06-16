<?php

namespace App\Services\Workflow\Actions;

use App\Services\Workflow\Contracts\ActionInterface;
use App\Services\Workflow\ExecutionContext;
use App\Services\Workflow\NodeResult;
use App\Services\Workflow\TemplateEngine;
use Illuminate\Support\Facades\Mail;

class EmailAction implements ActionInterface
{
    public function __construct(
        protected TemplateEngine $template
    ) {}

    public static function getType(): string
    {
        return 'action_email';
    }

    public static function getLabel(): string
    {
        return 'Send Email';
    }

    public static function getSchema(): array
    {
        return [
        'type' => 'object',
        'properties' => [
            'to' => [
                'type' => 'string',
                'title' => 'Destinataire',
                'format' => 'email'
            ],
            'subject' => [
                'type' => 'string',
                'title' => 'Sujet du mail'
            ],
            'message' => [
                'type' => 'string',
                'title' => 'Contenu du message'
            ],
        ],
        'required' => ['to', 'message'],
    ];
    }

    public function validatePayload(array $payload): void
    {
        if (empty($payload['to'])) {
            throw new \InvalidArgumentException("Missing 'to'");
        }

        if (empty($payload['message'])) {
            throw new \InvalidArgumentException("Missing 'message'");
        }
    }

    public function handle(ExecutionContext $context): NodeResult
    {
        $payload = $context->currentNodePayload ?? [];

        $to      = $this->template->render($payload['to']      ?? '', $context);
        $subject = $this->template->render($payload['subject'] ?? '', $context);
        $message = $this->template->render($payload['message'] ?? '', $context);

        if (empty($to)) {
            return NodeResult::failure("L'adresse destinataire est vide après résolution des variables.");
        }

        try {
            Mail::raw($message, function ($mail) use ($to, $subject) {
                $mail->to($to)->subject($subject ?: 'Notification');
            });

            $context->set($context->currentNodeId, ['sent_to' => $to, 'status' => 'success']);

            return NodeResult::success(['sent_to' => $to]);

        } catch (\Throwable $e) {
            return NodeResult::failure($e->getMessage());
        }
    }
}