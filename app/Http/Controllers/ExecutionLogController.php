<?php

namespace App\Http\Controllers;

use App\Models\Workflow;
use App\Models\ExecutionLog;

class ExecutionLogController extends Controller
{
    public function index(Workflow $workflow)
    {
    if ($workflow->user_id !== auth()->id()) {abort(403);}

    $logs = $workflow->executionLogs() ->orderBy('created_at', 'desc')
        ->get()
        ->groupBy('execution_id'); 

    return response()->json($logs); 
    }

    public function clear(Workflow $workflow)
    {
        if ($workflow->user_id !== auth()->id()) { abort(403);}

        ExecutionLog::where('workflow_id', $workflow->id)->delete();

        return response()->json(['message' => 'Les logs ont été vidés avec succès.']);
    }
}