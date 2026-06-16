<?php

namespace App\Services\Workflow;

/**
 * Mutable state bag passed through an entire workflow run.
 *
 * $input is the original trigger payload (read-only after construction).
 * $state is populated as each node runs — e.g. WebhookTrigger writes each
 * JSON key as a flat entry, ConditionControl writes condition_met: bool.
 *
 * resolve() and extractValueFromPath() do a three-step lookup:
 *   1. Flat state entries
 *   2. Recursive search in $input
 *   3. Recursive search in $state (for nested node outputs)
 * This lets {{email}} work whether it comes from the trigger payload or
 * was set explicitly by a previous node.
 */
class ExecutionContext
{
    public function __construct(
        public readonly array $input,
        public array $state = [],
        public array $history = [],
        public ?string $currentNodeId = null,
        public array $currentNodePayload = []
    ) {}

    public function set(string $key, mixed $value): void
    {
        $this->state[$key] = $value;
    }

    public function get(string $key): mixed
    {
        if (array_key_exists($key, $this->state)) {
            return $this->state[$key];
        }
        return $this->input[$key] ?? null;
    }

    public function setCurrentNode(string $nodeId, array $payload = []): void
    {
        $this->currentNodeId = $nodeId;
        $this->currentNodePayload = $payload;
    }


    public function log(string $message): void
    {
        $this->history[] = [
            'timestamp' => now(),
            'node_id' => $this->currentNodeId,
            'message' => $message,
        ];
    }

    public function getData(): array
    {
        return [
            'input' => $this->input,
            'state' => $this->state,
            'history' => $this->history,
            'current_node' => $this->currentNodeId,
        ];
    }

    public function resolve(string $path): mixed
    {
        // 1. Variables mises directement dans state (context->set('email', ...))
        if (array_key_exists($path, $this->state) && !is_array($this->state[$path])) {
            return $this->state[$path];
        }

        // 2. Recherche récursive dans l'input (payload entrant)
        $val = $this->recursiveFind($this->input, $path);
        if ($val !== null) return $val;

        // 3. Recherche récursive dans le state (résultats de nœuds)
        return $this->recursiveFind($this->state, $path);
    }

    private function recursiveFind(array $array, string $needle): mixed
    {
        if (array_key_exists($needle, $array)) {
            return $array[$needle];
        }
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = $this->recursiveFind($value, $needle);
                if ($result !== null) return $result;
            }
        }
        return null;
    }

    public function extractValueFromPath(string $path): mixed
    {
        return $this->resolve($path);
    }
    
}