<?php

namespace App\Http\Controllers;

use App\Models\Workflow;
use App\Services\Workflow\WorkflowRunner;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function __invoke(Request $request, string $workflowToken)
    {
        $workflow = Workflow::where('token', $workflowToken)->firstOrFail();

        if (!$workflow->is_active) {
            return response()->json([
                'message' => 'Workflow is disabled'
            ], 403);
        }

        $normalizedPayload = $this->normalizePayload($request);

        \Illuminate\Support\Facades\Log::info("WEBHOOK: Reçu pour workflow", ['id' => $workflow->id, 'payload' => $normalizedPayload]);
        $result = app(WorkflowRunner::class)->run($workflow, $normalizedPayload, 'trigger_webhook');

        if (!empty($result['error'])) {
            return response()->json([
                'message' => 'Workflow déclenché avec erreurs',
                'error' => $result['error'],
                'path' => $result['path'],
            ], 200);
        }

        return response()->json([
            'message' => 'Workflow déclenché avec succès'
        ], 202);
    }

    private function normalizePayload(Request $request): array
    {
        return $request->all();
    }
}