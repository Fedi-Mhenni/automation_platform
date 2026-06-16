<?php

namespace App\Services\Workflow;

use App\Models\ExecutionLog;
use App\Models\Workflow;
use App\Services\Workflow\Validation\GraphValidator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

/**
 * Traverses a workflow graph recursively, executing each node in order.
 *
 * The runner validates the graph, creates an ExecutionContext to hold mutable
 * state, then calls processFromNode() starting from meta.startNodeId. Each
 * node is visited at most once (cycle guard via $visited). Every node
 * execution — success or failure — is written to ExecutionLog with a shared
 * $executionId UUID that groups all records from a single workflow run.
 */
class WorkflowRunner
{
    protected Workflow $workflow;
    protected array $executionPath = [];
    protected array $visited = [];
    protected ?string $lastErrorMessage = null;
    protected string $executionId;

    public function __construct(
        protected GraphValidator $validator
    ) {}

    /**
     * Entry point. Validates the graph, picks the start node, then traverses.
     *
     * @param  string|null  $triggerType  When set, finds the first node of that
     *                                    type instead of following meta.startNodeId.
     *                                    Used by WebhookController to start from
     *                                    the webhook trigger regardless of position.
     * @return array{path: array<string,string>, error: ?string}
     */
    public function run(Workflow $workflow, array $payload = [], ?string $triggerType = null): array
    {
        $this->workflow = $workflow;
        $this->executionId = (string) Str::uuid();
        $this->resetExecutionState();

        $graph = $workflow->nodes_structure ?? ['nodes' => [], 'edges' => [], 'meta' => ['startNodeId' => null]];

        try {
            $this->validator->validate($graph);

            if ($triggerType) {
                $startNode = collect($graph['nodes'])->first(fn($n) => ($n['type'] ?? '') === $triggerType);
                if (!$startNode) {
                    throw new \Exception("Aucun nœud de type '{$triggerType}' dans ce workflow.");
                }
            } else {
                $startNodeId = $graph['meta']['startNodeId'] ?? null;
                $startNode   = $this->findNode($graph['nodes'], $startNodeId);
            }

            if (!$startNode || !str_starts_with($startNode['type'], 'trigger_')) {
                throw new \Exception("Nœud de départ invalide ou introuvable.");
            }

            Log::info("EXECUTION: Démarrage workflow", ['id' => $workflow->id, 'trigger' => $triggerType, 'payload' => $payload]);
            
            $context = new ExecutionContext(input: $payload);
            $this->processFromNode($startNode, $graph, $context);

        } catch (\Throwable $e) {
            $this->lastErrorMessage = $e->getMessage();
        }

        return [
            'path'  => $this->executionPath,
            'error' => $this->lastErrorMessage,
        ];
    }

    /** Recursively executes a single node, then follows outgoing edges. */
    protected function processFromNode(array $node, array $graph, ExecutionContext $context): void
    {
        if (isset($this->visited[$node['id']])) {
            throw new \Exception("Cycle détecté au nœud : {$node['id']}");
        }

        $this->visited[$node['id']] = true;
        $this->executionPath[$node['id']] = 'executed';
        $context->setCurrentNode($node['id'], $node['payload'] ?? []);

        try {
            $result = NodeRegistry::make($node['type'])->handle($context);

            if (!$result->success) {
                $this->executionPath[$node['id']] = 'error';
                $this->lastErrorMessage = $result->errorMessage;
                $this->logExecution($node, null, false, $result->errorMessage);
                return;
            }

            $this->logExecution($node, $result);
            Log::info("EXECUTION: Nœud exécuté avec succès", ['node_id' => $node['id'], 'result' => $result->output]);

            foreach ($this->getNextNodes($node['id'], $graph, $context) as $nextNode) {
                $this->processFromNode($nextNode, $graph, $context);
            }
        } catch (\Throwable $e) {
            $this->executionPath[$node['id']] = 'error';
            $this->lastErrorMessage = $e->getMessage();
            $this->logExecution($node, null, false, $e->getMessage());
        }
    }

    /**
     * Returns the nodes to visit next, respecting conditional edge routing.
     *
     * For control_condition nodes, the context stores condition_met: bool under
     * the node's own ID. Edges leaving a condition node carry condition = 'true'
     * or 'false'; only the matching branch is followed.
     */
    protected function getNextNodes(string $nodeId, array $graph, ExecutionContext $context): array
    {
        $nodeState = $context->get($nodeId);
        $conditionMet = $nodeState['condition_met'] ?? null;
        
        return array_filter(array_map(function ($edge) use ($graph, $nodeId, $conditionMet) {
            if (($edge['source'] ?? '') !== $nodeId) return null;
            
            $target = $this->findNode($graph['nodes'], $edge['target'] ?? '');
            if (!$target) return null;

            if (!isset($edge['condition'])) return $target;
            
            return ($conditionMet === ($edge['condition'] === 'true')) ? $target : null;
        }, $graph['edges'] ?? []));
    }

    protected function logExecution(array $node, ?NodeResult $result, bool $success = true, ?string $error = null): void
    {
        if ($error) {
            $message = $error;
        } else {
            $out     = $result?->output ?? [];
            $message = match ($node['type']) {
                'action_log'         => $out['message']    ?? 'Message vide',
                'action_email'       => 'Email envoyé à '  . ($out['sent_to']     ?? '?'),
                'action_delay'       => 'Délai de '        . ($out['delay']       ?? '?'),
                'trigger_scheduler'  => 'Déclenché à '     . ($out['triggered_at'] ?? now()),
                'control_condition'  => 'Condition '       . ($out['condition_met'] ? 'vraie ✓' : 'fausse ✗'),
                default              => 'Succès',
            };
        }

        ExecutionLog::create([
            'execution_id' => $this->executionId,
            'workflow_id'  => $this->workflow->id,
            'node_id'      => $node['id'],
            'action'       => $node['type'],
            'status'       => $success,
            'message'      => $message,
        ]);
    }

    protected function resetExecutionState(): void
    {
        $this->visited = [];
        $this->executionPath = [];
        $this->lastErrorMessage = null;
    }

    protected function findNode(array $nodes, ?string $nodeId): ?array
    {
        return collect($nodes)->firstWhere('id', $nodeId);
    }
}