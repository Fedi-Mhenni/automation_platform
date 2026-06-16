<?php

namespace App\Services\Workflow;

use App\Services\Workflow\Contracts\ActionInterface;
use App\Services\Workflow\Triggers\WebhookTrigger;
use App\Services\Workflow\Triggers\FormTrigger;
use App\Services\Workflow\Triggers\SchedulerTrigger;
use App\Services\Workflow\Actions\EmailAction;
use App\Services\Workflow\Actions\LogAction;
use App\Services\Workflow\Control\ConditionControl;
use App\Services\Workflow\Control\DelayControl;

/**
 * Central registry of all available node types.
 *
 * To add a new node: implement ActionInterface, then append the class here.
 * NodeRegistry::make() resolves a type string (e.g. 'action_email') to a
 * handler instance via the Laravel service container.
 * NodeRegistry::schemas() is called by WorkflowNodeSchemaController to supply
 * the frontend form renderer with JSON Schema definitions for each node type.
 */
class NodeRegistry
{
    protected static array $nodes = [
        FormTrigger::class,
        WebhookTrigger::class,
        SchedulerTrigger::class,
        EmailAction::class,
        LogAction::class,
        ConditionControl::class,
        DelayControl::class,
    ];

    public static function resolve(string $type): string
    {
        foreach (self::$nodes as $class) {
            if ($class::getType() === $type) {
                return $class;
            }
        }

        throw new \InvalidArgumentException("Unknown node type: {$type}");
    }

    public static function make(string $type): ActionInterface
    {
        return app(self::resolve($type));
    }

    public static function schemas(): array
    {
        $schemas = [];

        foreach (self::$nodes as $class) {
            $schemas[] = [
                'type' => $class::getType(),
                'label' => $class::getLabel(),
                'schema' => $class::getSchema(),
            ];
        }

        return $schemas;
    }
}