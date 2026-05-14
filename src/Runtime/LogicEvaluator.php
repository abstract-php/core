<?php

declare(strict_types=1);

namespace AbstractLang\Runtime;

use AbstractLang\Exception\RuntimeResolutionException;

final class LogicEvaluator
{
    /**
     * @param array<string, mixed> $context
     */
    public function evaluate(mixed $expression, array $context = []): mixed
    {
        if (!is_array($expression)) {
            return $expression;
        }

        if (array_is_list($expression)) {
            return array_map(fn (mixed $item): mixed => $this->evaluate($item, $context), $expression);
        }

        if (count($expression) !== 1) {
            $result = [];
            foreach ($expression as $key => $value) {
                $result[$key] = $this->evaluate($value, $context);
            }
            return $result;
        }

        $operator = array_key_first($expression);
        $operand = $expression[$operator];

        return match ($operator) {
            'var' => $this->variable($operand, $context),
            '==' => $this->compare($operand, $context, static fn (mixed $left, mixed $right): bool => $left == $right),
            '!=' => $this->compare($operand, $context, static fn (mixed $left, mixed $right): bool => $left != $right),
            '>' => $this->compare($operand, $context, static fn (mixed $left, mixed $right): bool => $left > $right),
            '>=' => $this->compare($operand, $context, static fn (mixed $left, mixed $right): bool => $left >= $right),
            '<' => $this->compare($operand, $context, static fn (mixed $left, mixed $right): bool => $left < $right),
            '<=' => $this->compare($operand, $context, static fn (mixed $left, mixed $right): bool => $left <= $right),
            'and' => $this->all($operand, $context),
            'or' => $this->any($operand, $context),
            '!' => !$this->truthy($this->evaluate($operand, $context)),
            '+' => array_sum($this->numbers($operand, $context)),
            '-' => $this->subtract($operand, $context),
            '*' => array_product($this->numbers($operand, $context)),
            '/' => $this->divide($operand, $context),
            default => throw new RuntimeResolutionException(sprintf('Unknown Abstract Logic operator "%s".', $operator)),
        };
    }

    /**
     * @param array<string, mixed> $context
     */
    private function variable(mixed $operand, array $context): mixed
    {
        $path = is_array($operand) ? ($operand[0] ?? null) : $operand;
        $default = is_array($operand) && array_key_exists(1, $operand) ? $operand[1] : null;

        if (!is_string($path) || $path === '') {
            return $context;
        }

        $current = $context;
        foreach (explode('.', $path) as $part) {
            if (is_array($current) && array_key_exists($part, $current)) {
                $current = $current[$part];
                continue;
            }

            if (is_object($current) && isset($current->{$part})) {
                $current = $current->{$part};
                continue;
            }

            return $default;
        }

        return $current;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function compare(mixed $operand, array $context, callable $comparison): bool
    {
        $values = is_array($operand) ? array_values($operand) : [$operand];
        if (count($values) < 2) {
            return false;
        }

        $left = $this->evaluate($values[0], $context);
        $right = $this->evaluate($values[1], $context);
        return (bool) $comparison($left, $right);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function all(mixed $operand, array $context): bool
    {
        foreach ((array) $operand as $item) {
            if (!$this->truthy($this->evaluate($item, $context))) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function any(mixed $operand, array $context): bool
    {
        foreach ((array) $operand as $item) {
            if ($this->truthy($this->evaluate($item, $context))) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array<string, mixed> $context
     * @return list<float|int>
     */
    private function numbers(mixed $operand, array $context): array
    {
        return array_map(
            static fn (mixed $value): float|int => is_int($value) ? $value : (float) $value,
            array_map(fn (mixed $item): mixed => $this->evaluate($item, $context), (array) $operand),
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    private function subtract(mixed $operand, array $context): float|int
    {
        $numbers = $this->numbers($operand, $context);
        if ($numbers === []) {
            return 0;
        }
        $first = array_shift($numbers);
        return $numbers === [] ? -$first : array_reduce($numbers, static fn (float|int $carry, float|int $item): float|int => $carry - $item, $first);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function divide(mixed $operand, array $context): float|int
    {
        $numbers = $this->numbers($operand, $context);
        if (count($numbers) < 2) {
            return 0;
        }

        $first = array_shift($numbers);
        return array_reduce($numbers, function (float|int $carry, float|int $item): float|int {
            if ($item == 0) {
                throw new RuntimeResolutionException('Division by zero in Abstract Logic expression.');
            }
            return $carry / $item;
        }, $first);
    }

    public function truthy(mixed $value): bool
    {
        if (is_array($value)) {
            return $value !== [];
        }
        return (bool) $value;
    }
}
