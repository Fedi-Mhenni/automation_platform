<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Workflow\NodeRegistry;

class WorkflowNodeSchemaController extends Controller
{
    public function index()
    {
        $schemas = NodeRegistry::schemas();

        return response()->json(['data' => $schemas,'meta' => ['count' => count($schemas),],]);
    }

    public function show($type)
    {
        $schemas = NodeRegistry::schemas();

        foreach ($schemas as $node) {
            if (isset($node['type']) && $node['type'] === $type) {
                return response()->json(['data' => $node]);}
        }
        return response()->json(['message' => 'Schema non trouvé' ], 404);      
    }
}   