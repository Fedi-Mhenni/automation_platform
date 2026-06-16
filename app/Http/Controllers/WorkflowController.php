<?php

namespace App\Http\Controllers;

use App\Models\Workflow;
use App\Models\ExecutionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\SaveWorkflowRequest;
use App\Http\Controllers\Controller;
use App\Services\Workflow\NodeRegistry;
use App\Services\Workflow\Validation\GraphValidator;
use Cron\CronExpression;

class WorkflowController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index()
    {
        $workflows = Auth::user()->workflows()
            ->withCount('executionLogs')
            ->latest()
            ->get()
            ->map(fn($w) => [
                'id'              => $w->id,
                'name'            => $w->name,
                'is_active'       => $w->is_active,
                'token'           => $w->token,
                'nodes_count'     => count($w->nodes_structure['nodes'] ?? []),
                'execution_count' => $w->execution_logs_count,
                'created_at'      => $w->created_at,
            ]);

        return response()->json($workflows);
    }

    public function show(Workflow $workflow)
    {
        if ($workflow->user_id !== Auth::id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $totalExecutions = ExecutionLog::where('workflow_id', $workflow->id)
            ->whereNotNull('execution_id')
            ->distinct('execution_id')
            ->count('execution_id');

        $successCount = ExecutionLog::where('workflow_id', $workflow->id)
            ->where('status', true)
            ->whereNotNull('execution_id')
            ->distinct('execution_id')
            ->count('execution_id');

        $recentLogs = $workflow->executionLogs()->latest()->take(10)->get();

        return response()->json([
            'id'          => $workflow->id,
            'name'        => $workflow->name,
            'is_active'   => $workflow->is_active,
            'token'       => $workflow->token,
            'webhook_url' => url('/api/webhook/' . $workflow->token),
            'nodes'       => $workflow->nodes_structure['nodes'] ?? [],
            'edges'       => $workflow->nodes_structure['edges'] ?? [],
            'meta'        => $workflow->nodes_structure['meta'] ?? ['startNodeId' => null],
            'stats'       => [
                'total_executions' => $totalExecutions,
                'success_count'    => $successCount,
                'error_count'      => max(0, $totalExecutions - $successCount),
                'nodes_count'      => count($workflow->nodes_structure['nodes'] ?? []),
            ],
            'recent_logs' => $recentLogs,
        ]);
    }

    public function raw(Workflow $workflow)
    {
        if ($workflow->user_id !== Auth::id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json([
            'id'              => $workflow->id,
            'name'            => $workflow->name,
            'nodes_structure' => $workflow->nodes_structure,
        ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function store(Request $request)
    {
        $validated = $request->validate(['name' => 'required|string|max:255']);

        $workflow = Auth::user()->workflows()->create([
            'name'      => $validated['name'],
            'is_active' => false,
        ]);

        return response()->json(['success' => true, 'workflow' => $workflow], 201);
    }

    public function update(Request $request, Workflow $workflow)
    {
        if ($workflow->user_id !== Auth::id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate(['name' => 'required|string|max:255']);
        $workflow->update(['name' => $validated['name']]);

        return response()->json(['success' => true, 'workflow' => $workflow]);
    }

    public function destroy(Workflow $workflow)
    {
        if ($workflow->user_id !== Auth::id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $workflow->delete();
        return response()->json(['success' => true]);
    }

    public function saveNodes(SaveWorkflowRequest $request, Workflow $workflow)
    {
        $workflow->nodes_structure = $this->normalizeGraph($request->validated());
        $workflow->save();

        return response()->json(['success' => true, 'message' => 'Workflow sauvegardé avec succès']);
    }

    public function activate(Workflow $workflow)
    {
        if ($workflow->user_id !== Auth::id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $workflow->update(['is_active' => true]);
        return response()->json(['success' => true, 'is_active' => true]);
    }

    public function deactivate(Workflow $workflow)
    {
        if ($workflow->user_id !== Auth::id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $workflow->update(['is_active' => false]);
        return response()->json(['success' => true, 'is_active' => false]);
    }
    public function nextRun(Request $request, Workflow $workflow)
    {
        if ($workflow->user_id !== Auth::id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $graph = $workflow->nodes_structure ?? [];
        $nodes = $graph['nodes'] ?? [];

        $schedulerNode = collect($nodes)->first(fn($n) => ($n['type'] ?? '') === 'trigger_scheduler');

        if (!$schedulerNode) {
            return response()->json(['has_scheduler' => false]);
        }

        $cronExpr = $schedulerNode['payload']['cron_expression'] ?? null;

        if (!$cronExpr) {
            return response()->json(['has_scheduler' => true, 'error' => 'Expression cron manquante — configurez le planificateur.']);
        }

        try {
            $cron    = new CronExpression($cronExpr);
            $nextRun = $cron->getNextRunDate();

            $errors = [];
            try {
                app(GraphValidator::class)->validate($graph);
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }

            $diff    = now()->diffInSeconds($nextRun);
            $human   = match (true) {
                $diff < 120  => 'dans ' . $diff . ' secondes',
                $diff < 3600 => 'dans ' . round($diff / 60) . ' minute' . (round($diff / 60) > 1 ? 's' : ''),
                $diff < 86400 => 'dans ' . round($diff / 3600) . ' heure' . (round($diff / 3600) > 1 ? 's' : ''),
                default      => 'dans ' . round($diff / 86400) . ' jour' . (round($diff / 86400) > 1 ? 's' : ''),
            };

            return response()->json([
                'has_scheduler'   => true,
                'cron_expression' => $cronExpr,
                'next_run'        => $nextRun->format('Y-m-d H:i:s'),
                'next_run_human'  => $human,
                'valid'           => empty($errors),
                'errors'          => $errors,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'has_scheduler' => true,
                'error'         => 'Expression cron invalide : ' . $e->getMessage(),
            ]);
        }
    }

    public function test(Request $request, Workflow $workflow)
    {
        if ($workflow->user_id !== Auth::id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            $runner  = app(\App\Services\Workflow\WorkflowRunner::class);
            $payload = $request->input('payload', []);
            // No trigger_type override — always starts from meta.startNodeId (first node in chain).
            // If scheduler is first, it fires automatically before the webhook/form trigger.
            $result  = $runner->run($workflow, $payload);

            return response()->json([
                'success' => !(!empty($result['error']) || in_array('error', $result['path'] ?? [])),
                'result'  => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function getNodeSchema(string $type): array
    {
        $nodeSchemaData = collect(NodeRegistry::schemas())->firstWhere('type', $type);
        return $nodeSchemaData['schema'] ?? [];
    }

    public function getNodePayload($workflowId, $nodeId)
    {
        $workflow = Workflow::findOrFail($workflowId);
        $node     = collect($workflow->getNodes())->firstWhere('id', $nodeId);

        return response()->json([
            'form_schema'     => $this->getNodeSchema($node['type']),
            'current_payload' => $node['payload'] ?? [],
        ]);
    }

    public function updateNodePayload(Request $request, $workflowId, $nodeId)
    {
        $workflow  = Workflow::findOrFail($workflowId);
        $nodes     = $workflow->getNodes();
        $nodeIndex = collect($nodes)->search(fn($n) => $n['id'] === $nodeId);

        if ($nodeIndex === false) {
            return response()->json(['message' => 'Nœud introuvable'], 404);
        }

        $schema       = $this->getNodeSchema($nodes[$nodeIndex]['type']);
        $allowedKeys  = array_keys($schema['properties'] ?? []);
        $nodes[$nodeIndex]['payload'] = array_intersect_key($request->json()->all(), array_flip($allowedKeys));

        $workflow->setGraph(['nodes' => $nodes, 'edges' => $workflow->getEdges()]);
        $workflow->save();

        return response()->json(['message' => 'Configuration mise à jour', 'data' => $nodes[$nodeIndex]['payload']]);
    }

    private function normalizeGraph(array $data): array
    {
        return [
            'nodes' => array_values($data['nodes'] ?? []),
            'edges' => array_values($data['edges'] ?? []),
            'meta'  => ['startNodeId' => $data['meta']['startNodeId'] ?? $data['startNodeId'] ?? null],
        ];
    }
}
