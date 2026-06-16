<?php

namespace App\Services\Workflow\Triggers;

use App\Services\Workflow\Contracts\ActionInterface;
use App\Services\Workflow\ExecutionContext;
use App\Services\Workflow\NodeResult;
use Cron\CronExpression;

class SchedulerTrigger implements ActionInterface
{
    public static function getType(): string
    {
        return 'trigger_scheduler';
    }

    public static function getLabel(): string
    {
        return 'Scheduler Trigger';
    }

    public static function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'cron_expression' => [
                    'type'        => 'string',
                    'title'       => 'Expression cron',
                    'description' => 'Format : "minute heure jour mois jour_semaine". Ex: "0 9 * * 1" = lundi à 9h. Raccourcis : @daily, @hourly, @weekly.',
                ],
                'timezone' => [
                    'type'    => 'string',
                    'title'   => 'Fuseau horaire',
                    'default' => 'UTC',
                ],
            ],
            'required' => ['cron_expression'],
        ];
    }

    public function validatePayload(array $payload): void
    {
        $expr = $payload['cron_expression'] ?? null;

        if (empty($expr)) {
            throw new \InvalidArgumentException("L'expression cron est obligatoire (ex: \"0 9 * * 1\" pour lundi à 9h).");
        }

        try {
            new CronExpression($expr);
        } catch (\Throwable) {
            throw new \InvalidArgumentException("Expression cron invalide : \"{$expr}\".");
        }
    }

    public function handle(ExecutionContext $context): NodeResult
    {
        $payload  = $context->currentNodePayload ?? [];
        $timezone = $payload['timezone'] ?? 'UTC';
        $cronExpr = $payload['cron_expression'] ?? null;
        $now      = now()->setTimezone($timezone);

        $nextRun = null;
        if ($cronExpr) {
            try {
                $nextRun = (new CronExpression($cronExpr))
                    ->getNextRunDate()
                    ->setTimezone($timezone)
                    ->format('Y-m-d H:i:s');
            } catch (\Throwable) {}
        }

        $data = [
            'triggered_at'   => $now->toDateTimeString(),
            'scheduled_time' => $now->toDateTimeString(),
            'next_run'       => $nextRun,
        ];

        foreach ($data as $key => $value) {
            $context->set($key, $value);
        }
        $context->set($context->currentNodeId, $data);

        return NodeResult::success(['trigger' => 'scheduler', ...$data]);
    }
}