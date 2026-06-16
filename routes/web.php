<?php

use App\Http\Controllers\WorkflowWebController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'));

require __DIR__.'/auth.php';

Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard (liste des workflows)
    Route::get('/dashboard', [WorkflowWebController::class, 'index'])->name('dashboard');

    // CRUD Workflows
    Route::get('/workflows/create', [WorkflowWebController::class, 'create'])->name('workflows.create');
    Route::post('/workflows', [WorkflowWebController::class, 'store'])->name('workflows.store');
    Route::get('/workflows/{workflow}', [WorkflowWebController::class, 'show'])->name('workflows.show');
    Route::get('/workflows/{workflow}/edit', [WorkflowWebController::class, 'edit'])->name('workflows.edit');
    Route::patch('/workflows/{workflow}', [WorkflowWebController::class, 'update'])->name('workflows.update');
    Route::delete('/workflows/{workflow}', [WorkflowWebController::class, 'destroy'])->name('workflows.destroy');

    // Actions graphe (AJAX depuis l'éditeur)
    Route::post('/workflows/{workflow}/save', [WorkflowWebController::class, 'save'])->name('workflows.save');
    Route::post('/workflows/{workflow}/activate', [WorkflowWebController::class, 'activate'])->name('workflows.activate');
    Route::post('/workflows/{workflow}/deactivate', [WorkflowWebController::class, 'deactivate'])->name('workflows.deactivate');

    // Logs
    Route::get('/workflows/{workflow}/logs', [WorkflowWebController::class, 'logs'])->name('workflows.logs');
    Route::delete('/workflows/{workflow}/logs', [WorkflowWebController::class, 'clearLogs'])->name('workflows.logs.clear');

    // Test manuel
    Route::get('/workflows/{workflow}/test', [WorkflowWebController::class, 'test'])->name('workflows.test');
    Route::post('/workflows/{workflow}/test-run', [WorkflowWebController::class, 'runTest'])->name('workflows.test-run');

    // Profil
    Route::controller(ProfileController::class)->group(function () {
        Route::get('/profile', 'edit')->name('profile.edit');
        Route::patch('/profile', 'update')->name('profile.update');
        Route::delete('/profile', 'destroy')->name('profile.destroy');
    });
});
