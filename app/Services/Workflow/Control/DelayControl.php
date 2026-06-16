<?php

namespace App\Services\Workflow\Control;

use App\Services\Workflow\Contracts\ActionInterface;
use App\Services\Workflow\ExecutionContext;
use App\Services\Workflow\NodeResult;

class DelayControl implements ActionInterface
{
    public static function getType(): string
    {
        return 'action_delay';
    }

    public static function getLabel(): string
    {
        return 'Delay';
    }

    private const MAX_SYNC_SECONDS = 30;

    public static function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'duration' => [
                    'type'    => 'number',
                    'title'   => 'Durée',
                    'minimum' => 1,
                ],
                'unit' => [
                    'type'  => 'string',
                    'title' => 'Unité',
                    'oneOf' => [
                        ['const' => 'seconds', 'title' => 'Secondes'],
                        ['const' => 'minutes', 'title' => 'Minutes'],
                        ['const' => 'hours',   'title' => 'Heures'],
                    ],
                ],
            ],
            'required' => ['duration', 'unit'],
        ];
    }

    public function validatePayload(array $payload): void
    {
        $duration = $payload['duration'] ?? null;
        $unit     = $payload['unit'] ?? null;

        if (!is_numeric($duration) || (float) $duration < 1) {
            throw new \InvalidArgumentException("La durée doit être un nombre positif.");
        }

        if (!in_array($unit, ['seconds', 'minutes', 'hours'], true)) {
            throw new \InvalidArgumentException("L'unité doit être seconds, minutes ou hours.");
        }
    }

    public function handle(ExecutionContext $context): NodeResult
    {
        $payload  = $context->currentNodePayload ?? [];
        $duration = (float) ($payload['duration'] ?? 0);
        $unit     = $payload['unit'] ?? 'seconds';

        $seconds = match ($unit) {
            'minutes' => (int) ($duration * 60),
            'hours'   => (int) ($duration * 3600),
            default   => (int) $duration,
        };

        if ($seconds >= 1 && $seconds <= self::MAX_SYNC_SECONDS) {
            sleep($seconds);
        }

        $label = "{$duration} {$unit}";
        $context->set($context->currentNodeId, ['waited' => $label, 'seconds' => $seconds]);

        return NodeResult::success([
            'delay'   => $label,
            'seconds' => $seconds,
            'applied' => $seconds <= self::MAX_SYNC_SECONDS,
        ]);
    }
}