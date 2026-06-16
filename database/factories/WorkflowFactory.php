<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Workflow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Workflow>
 */
class WorkflowFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Workflow de Test',
            'is_active' => false,
            'user_id' => User::factory(),
            'nodes_structure' => [
                'nodes' => [],
                'edges' => [], 
                'meta' => [
                    'startNodeId' => null,
                ],
            ],
        ];
    }

    public function withMinimalGraph(): self
    {
        return $this->state(fn () => [
            'nodes_structure' => [
                'nodes' => [
                    [
                        'id' => 'start',
                        'type' => 'trigger_webhook',
                        'payload' => [],
                    ],
                ],
                'edges' => [],
                'meta' => [
                    'startNodeId' => 'start',
                ],
            ],
        ]);
    }
}