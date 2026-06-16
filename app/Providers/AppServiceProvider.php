<?php

namespace App\Providers;

use App\Services\Workflow\WorkflowRunner;
use App\Services\Workflow\Validation\GraphValidator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WorkflowRunner::class, function ($app) {
            return new WorkflowRunner($app->make(GraphValidator::class));
        });
    }
}