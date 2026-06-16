<?php

namespace App\Services\Workflow\Graph;

/**
 * Value object representing a directed connection between two nodes.
 *
 * $condition is null for unconditional edges (always followed).
 * For edges leaving a control_condition node it is 'true' or 'false',
 * and WorkflowRunner reads condition_met from ExecutionContext state to
 * decide which branch to take. matches() encapsulates that routing logic.
 */
class Edge
{
    public function __construct(
        public readonly string $source,
        public readonly string $target,
        public readonly ?string $condition = null, 
    ) {}

    public static function fromArray(array $data): self
    {
        if (!isset($data['source'], $data['target'])) {
            throw new \InvalidArgumentException("Invalid edge structure");
        }

        return new self(
            source: $data['source'],
            target: $data['target'],
            condition: $data['condition'] ?? null,
        );
    }

    public function matches(bool $conditionMet): bool
    {
        if ($this->condition === null) {
            return true;
        }

        return match ($this->condition) {
            'true' => $conditionMet,
            'false' => !$conditionMet,
            default => false,
        };
    }

    public function toArray(): array
    {
        return array_filter([
            'source' => $this->source,
            'target' => $this->target,
            'condition' => $this->condition,
        ], fn ($v) => $v !== null);
    }
}