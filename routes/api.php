<?php

use App\Http\Controllers\WebhookController;
use App\Http\Controllers\Api\WorkflowNodeSchemaController;
use App\Http\Controllers\WorkflowController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExecutionLogController;

Route::post('/webhook/{workflow_token}', WebhookController::class);

Route::middleware('auth:sanctum')->group(function () {
    
    Route::get('/workflow-nodes/schema', [WorkflowNodeSchemaController::class, 'index']);
    Route::get('/workflow-nodes/schema/{type}', [WorkflowNodeSchemaController::class, 'show']);
    
    Route::prefix('workflows')->group(function () {
        Route::get('/', [WorkflowController::class, 'index']); 
        
        Route::get('{workflow}', [WorkflowController::class, 'show']); 
        
        Route::post('/', [WorkflowController::class, 'store']);
        Route::post('{workflow}/save', [WorkflowController::class, 'saveNodes']);

        Route::put('{workflow}/nodes/{nodeId}/payload', [WorkflowController::class, 'updateNodePayload']);
        Route::get('{workflow}/nodes/{nodeId}/payload', [WorkflowController::class, 'getNodePayload']);

        Route::patch('{workflow}', [WorkflowController::class, 'update']);
        Route::post('{workflow}/activate', [WorkflowController::class, 'activate']);
        Route::post('{workflow}/deactivate', [WorkflowController::class, 'deactivate']);

        Route::delete('{workflow}', [WorkflowController::class, 'destroy']);

        Route::get('{workflow}/logs', [ExecutionLogController::class, 'index'])->name('api.workflows.logs');
        Route::delete('{workflow}/logs', [ExecutionLogController::class, 'clear'])->name('api.workflows.logs.clear');

        Route::post('{workflow}/test', [WorkflowController::class, 'test']);
        Route::get('{workflow}/next-run', [WorkflowController::class, 'nextRun']);
        Route::get('{workflow}/raw', [WorkflowController::class, 'raw']);
    });
});