<?php

namespace App\Services\Workflow\Validation;

use App\Services\Workflow\NodeRegistry;
use App\Services\Workflow\Graph\Edge;
use InvalidArgumentException;

/**
 * Pre-execution validator for workflow graphs.
 *
 * Checks: required top-level keys, no duplicate node IDs, all edge endpoints
 * reference existing nodes, conditional edges carry only 'true'/'false',
 * startNodeId exists and points to a trigger_* node.
 *
 * Throws InvalidArgumentException on the first violation — WorkflowRunner
 * catches this and surfaces it as an execution error without touching the DB.
 */
class GraphValidator
{
    public function validate(array $graph): void
    {
        $this->validateStructure($graph);

        $nodes = $graph['nodes'] ?? [];
        $edges = $graph['edges'] ?? [];
        $startNodeId = $graph['meta']['startNodeId'] ?? null;

        $this->validateNodes($nodes);
        $this->validateEdges($nodes, $edges);
        $this->validateStartNode($nodes, $startNodeId);
    }

    private function validateStructure(array $graph): void
    {
        foreach (['nodes', 'edges', 'meta'] as $key) {
            if (!array_key_exists($key, $graph)) {
                throw new InvalidArgumentException("Graph missing key: {$key}");
            }
        }

        if (!is_array($graph['nodes']) || !is_array($graph['edges'])) {
            throw new InvalidArgumentException("Nodes and edges must be arrays");
        }

        if (!is_array($graph['meta'])) {
            throw new InvalidArgumentException("Meta must be array");
        }
    }

    private function validateNodes(array $nodes): void
    {
        $ids = [];

        foreach ($nodes as $node) {

            if (!isset($node['id'], $node['type'])) {
                throw new InvalidArgumentException("Node must have id and type");
            }

            if (in_array($node['id'], $ids, true)) {
                throw new InvalidArgumentException("Duplicate node id: {$node['id']}");
            }

            $ids[] = $node['id'];


            if (!isset($node['payload']) || !is_array($node['payload'])) {
                throw new InvalidArgumentException("Node payload must be array");
            }
        }
    }

    private function validateEdges(array $nodes, array $edges): void
    {
        $nodeIds = array_column($nodes, 'id');

        foreach ($edges as $edgeData) {

            $edge = Edge::fromArray($edgeData);

            if (!in_array($edge->source, $nodeIds, true)) {
                throw new InvalidArgumentException("Edge source not found: {$edge->source}");
            }

            if (!in_array($edge->target, $nodeIds, true)) {
                throw new InvalidArgumentException("Edge target not found: {$edge->target}");
            }

            if ($edge->condition !== null &&
                !in_array($edge->condition, ['true', 'false'], true)
            ) {
                throw new InvalidArgumentException("Invalid edge condition: {$edge->condition}");
            }
        }
    }

    private function validateStartNode(array $nodes, ?string $startNodeId): void
    {
        if (!$startNodeId) {
            throw new InvalidArgumentException("Missing startNodeId");
        }

        $startNode = collect($nodes)->firstWhere('id', $startNodeId);

        if (!$startNode) {
            throw new InvalidArgumentException("Start node not found");
        }

        if (!str_starts_with($startNode['type'], 'trigger_')) {
            throw new InvalidArgumentException("Start node must be a trigger");
        }
    }
}