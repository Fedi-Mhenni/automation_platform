<?php

namespace App\Services\Workflow\Control;

use App\Services\Workflow\Contracts\ActionInterface;
use App\Services\Workflow\ExecutionContext;
use App\Services\Workflow\NodeResult;
use App\Services\Workflow\Control\ConditionResolver;

class ConditionControl implements ActionInterface
{
    public const OPERATORS = [
        'equals'           => '==',
        'not_equals'       => '!=',
        'greater_than'     => '>',
        'greater_or_equal' => '>=',
        'less_than'        => '<',
        'less_or_equal'    => '<=',
        'contains'         => 'contains',
    ];

    public function __construct(
        protected ConditionResolver $resolver
    ) {}

    public static function getType(): string
    {
        return 'control_condition';
    }

    public static function getLabel(): string
    {
        return 'Condition';
    }

    public static function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'variable' => [
                    'type'        => 'string',
                    'title'       => 'Variable à tester',
                    'description' => 'Nom de la variable du payload entrant (ex: montant, email, statut).',
                ],
                'operator' => [
                    'type'  => 'string',
                    'title' => 'Opérateur',
                    'oneOf' => [
                        ['const' => 'equals',           'title' => 'égal à (==)'],
                        ['const' => 'not_equals',       'title' => 'différent de (!=)'],
                        ['const' => 'greater_than',     'title' => 'supérieur à (>)'],
                        ['const' => 'greater_or_equal', 'title' => 'supérieur ou égal à (>=)'],
                        ['const' => 'less_than',        'title' => 'inférieur à (<)'],
                        ['const' => 'less_or_equal',    'title' => 'inférieur ou égal à (<=)'],
                        ['const' => 'contains',         'title' => 'contient'],
                    ],
                ],
                'value' => [
                    'type'        => 'string',
                    'title'       => 'Valeur de comparaison',
                    'description' => 'Valeur fixe à comparer (ex: 100, VIP, completed).',
                ],
            ],
            'required' => ['variable', 'operator', 'value'],
        ];
    }

    public function validatePayload(array $payload): void
    {
        foreach (['variable', 'operator', 'value'] as $field) {
            if (!isset($payload[$field]) || $payload[$field] === '') {
                throw new \InvalidArgumentException("Field '{$field}' is required.");
            }
        }

        if (!isset(self::OPERATORS[$payload['operator']])) {
            throw new \InvalidArgumentException("Invalid operator '{$payload['operator']}'.");
        }
    }

    public function handle(ExecutionContext $context): NodeResult
    {
        $payload = $context->currentNodePayload ?? [];
        $variable = $payload['variable'] ?? null;
        $operator = $payload['operator'] ?? null;
        $value = $payload['value'] ?? null;

        if (!$variable || !$operator) {
            return NodeResult::failure("Invalid condition configuration.");
        }

        $actualValue = $context->extractValueFromPath($variable);

        if ($actualValue === null) {
            return NodeResult::failure("Variable '{$variable}' introuvable dans le contexte. Vérifiez que le déclencheur précédent expose bien cette variable.");
        }

        $isMet = $this->resolver->evaluateCondition($actualValue, $operator, $value);

        $context->set($context->currentNodeId, ['condition_met' => $isMet]);

        return NodeResult::success([
            'condition_met' => $isMet
        ]);
    }
}