<?php

namespace App\Console\Commands;

use App\Models\Workflow;
use App\Services\Workflow\WorkflowRunner;
use Cron\CronExpression;
use Illuminate\Console\Command;

class RunScheduledWorkflows extends Command
{
    protected $signature   = 'workflow:run-scheduled';
    protected $description = 'Déclenche les workflows actifs dont l\'heure planifiée est due';

    public function handle(WorkflowRunner $runner): void
    {
        $workflows = Workflow::where('is_active', true)->get();

        foreach ($workflows as $workflow) {
            $graph = $workflow->nodes_structure ?? [];
            $nodes = $graph['nodes'] ?? [];

            $schedulerNode = collect($nodes)->first(
                fn($n) => ($n['type'] ?? '') === 'trigger_scheduler'
            );

            if (!$schedulerNode) {
                continue;
            }

            $cronExpr = $schedulerNode['payload']['cron_expression'] ?? null;
            if (!$cronExpr) {
                continue;
            }

            try {
                $cron = new CronExpression($cronExpr);

                if ($cron->isDue()) {
                    $this->info("Déclenchement workflow #{$workflow->id} : {$workflow->name}");
                    $runner->run($workflow, [], 'trigger_scheduler');
                }
            } catch (\Throwable $e) {
                $this->error("Workflow #{$workflow->id} ignoré : {$e->getMessage()}");
            }
        }
    }
}
