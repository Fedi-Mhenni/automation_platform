<?php

namespace App\Services\Workflow\Contracts;

use App\Services\Workflow\ExecutionContext;
use App\Services\Workflow\NodeResult;

/**
 * Contract that every node type must implement.
 *
 * - getType()   — unique snake_case identifier used in nodes_structure JSON
 *                 (e.g. 'action_email', 'trigger_webhook').
 * - getLabel()  — human-readable name shown in the editor sidebar.
 * - getSchema() — JSON Schema (draft-07) for the node's payload; used by the
 *                 frontend to render the configuration form dynamically.
 * - validatePayload() — called before the node runs; throw on invalid input.
 * - handle()    — executes the node's logic and returns NodeResult.
 */
interface ActionInterface
{
    public function handle(ExecutionContext $context): NodeResult;
    public function validatePayload(array $payload): void;
    public static function getSchema(): array;
    public static function getType(): string;
    public static function getLabel(): string;
}