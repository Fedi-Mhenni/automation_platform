<?php

namespace App\Services\Workflow\Control;

class ConditionResolver
{
    public function evaluateCondition(mixed $actualValue, string $operator, mixed $expectedValue): bool
    {
        return match ($operator) {
            'equals'           => $this->equals($actualValue, $expectedValue),
            'not_equals'       => !$this->equals($actualValue, $expectedValue),
            'greater_than'     => $this->compare($actualValue, $expectedValue) > 0,
            'greater_or_equal' => $this->compare($actualValue, $expectedValue) >= 0,
            'less_than'        => $this->compare($actualValue, $expectedValue) < 0,
            'less_or_equal'    => $this->compare($actualValue, $expectedValue) <= 0,
            'contains'         => $this->contains($actualValue, $expectedValue),
            default            => false,
        };
    }

    private function equals(mixed $a, mixed $b): bool
    {
        return (string) $a === (string) $b;
    }

    private function compare(mixed $a, mixed $b): int
    {
        if (is_numeric($a) && is_numeric($b)) {
            return (float) $a <=> (float) $b;
        }

        return strcmp((string) $a, (string) $b);
    }

    private function contains(mixed $a, mixed $b): bool
    {
        if ($a === null || $b === null) {
            return false;
        }

        return str_contains((string) $a, (string) $b);
    }
}