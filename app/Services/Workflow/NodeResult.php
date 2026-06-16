<?php

namespace App\Services\Workflow;

/**
 * Immutable value object returned by every node's handle() method.
 *
 * Use NodeResult::success(['key' => 'value']) on happy path — the output
 * array is merged into ExecutionContext state under the node's ID.
 * Use NodeResult::failure('message') to halt the chain; WorkflowRunner
 * logs the error and stops traversal (no exception thrown, so later runs
 * of unrelated branches are unaffected).
 */
class NodeResult
{
    public function __construct(
        public bool $success,
        public array $output = [],
        public ?string $errorMessage = null
    ) {}

    public static function success(array $output = []): self
    {
        return new self(true, $output);
    }

    public static function failure(string $message): self
    {
        return new self(false, [], $message);
    }
}