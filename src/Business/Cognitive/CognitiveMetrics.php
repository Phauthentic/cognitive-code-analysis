<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics\Business\Cognitive;

use InvalidArgumentException;
use JsonSerializable;

/**
 *
 */
class CognitiveMetrics implements JsonSerializable
{
    private string $class = '';
    private string $method = '';
    private int $lineCount = 0;
    private int $argCount = 0;
    private int $returnCount = 0;
    private int $variableCount = 0;
    private int $propertyCallCount = 0;
    private int $ifCount = 0;
    private int $ifNestingLevel = 0;
    private int $elseCount = 0;

    private float $lineCountWeight = 0.0;
    private float $argCountWeight = 0.0;
    private float $returnCountWeight = 0.0;
    private float $variableCountWeight = 0.0;
    private float $propertyCallCountWeight = 0.0;
    private float $ifCountWeight = 0.0;
    private float $ifNestingLevelWeight = 0.0;
    private float $elseCountWeight = 0.0;
    private float $score = 0.0;

    /**
     * @param array<string, mixed> $metrics
     */
    public function __construct(array $metrics)
    {
        $this->assertArrayKeyIsPresent($metrics, 'class');
        $this->assertArrayKeyIsPresent($metrics, 'method');
        $this->assertArrayKeyIsPresent($metrics, 'line_count');
        $this->assertArrayKeyIsPresent($metrics, 'arg_count');
        $this->assertArrayKeyIsPresent($metrics, 'return_count');
        $this->assertArrayKeyIsPresent($metrics, 'variable_count');
        $this->assertArrayKeyIsPresent($metrics, 'property_call_count');
        $this->assertArrayKeyIsPresent($metrics, 'if_count');
        $this->assertArrayKeyIsPresent($metrics, 'if_nesting_level');
        $this->assertArrayKeyIsPresent($metrics, 'else_count');

        $this->class = $metrics['class'];
        $this->method = $metrics['method'];
        $this->lineCount = $metrics['line_count'];
        $this->argCount = $metrics['arg_count'];
        $this->returnCount = $metrics['return_count'];
        $this->variableCount = $metrics['variable_count'];
        $this->propertyCallCount = $metrics['property_call_count'];
        $this->ifCount = $metrics['if_count'];
        $this->ifNestingLevel = $metrics['if_nesting_level'];
        $this->elseCount = $metrics['else_count'];
    }

    /**
     * @param array<string, mixed> $metrics
     * @return self
     */
    public static function fromArray(array $metrics): self
    {
        return new self($metrics);
    }

    /**
     * @param array<string, mixed> $array
     * @param string $key
     * @return void
     */
    private function assertArrayKeyIsPresent(array $array, string $key): void
    {
        if (!array_key_exists($key, $array)) {
            throw new InvalidArgumentException("Missing required key: $key");
        }
    }

    // Getters for read-only attributes
    public function getClass(): string
    {
        return $this->class;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getLineCount(): int
    {
        return $this->lineCount;
    }

    public function getArgCount(): int
    {
        return $this->argCount;
    }

    public function getReturnCount(): int
    {
        return $this->returnCount;
    }

    public function getVariableCount(): int
    {
        return $this->variableCount;
    }

    public function getPropertyCallCount(): int
    {
        return $this->propertyCallCount;
    }

    public function getIfCount(): int
    {
        return $this->ifCount;
    }

    public function getIfNestingLevel(): int
    {
        return $this->ifNestingLevel;
    }

    public function getElseCount(): int
    {
        return $this->elseCount;
    }

    // Getters and setters for weight attributes
    public function getLineCountWeight(): float
    {
        return $this->lineCountWeight;
    }

    public function setLineCountWeight(float $weight): void
    {
        $this->lineCountWeight = $weight;
    }

    public function getArgCountWeight(): float
    {
        return $this->argCountWeight;
    }

    public function setArgCountWeight(float $weight): void
    {
        $this->argCountWeight = $weight;
    }

    public function getReturnCountWeight(): float
    {
        return $this->returnCountWeight;
    }

    public function setReturnCountWeight(float $weight): void
    {
        $this->returnCountWeight = $weight;
    }

    public function getVariableCountWeight(): float
    {
        return $this->variableCountWeight;
    }

    public function setVariableCountWeight(float $weight): void
    {
        $this->variableCountWeight = $weight;
    }

    public function getPropertyCallCountWeight(): float
    {
        return $this->propertyCallCountWeight;
    }

    public function setPropertyCallCountWeight(float $weight): void
    {
        $this->propertyCallCountWeight = $weight;
    }

    public function getIfCountWeight(): float
    {
        return $this->ifCountWeight;
    }

    public function setIfCountWeight(float $weight): void
    {
        $this->ifCountWeight = $weight;
    }

    public function getIfNestingLevelWeight(): float
    {
        return $this->ifNestingLevelWeight;
    }

    public function setIfNestingLevelWeight(float $weight): void
    {
        $this->ifNestingLevelWeight = $weight;
    }

    public function getElseCountWeight(): float
    {
        return $this->elseCountWeight;
    }

    public function setElseCountWeight(float $weight): void
    {
        $this->elseCountWeight = $weight;
    }

    public function setScore(float $score): void
    {
        $this->score = $score;
    }

    public function getScore(): float
    {
        return $this->score;
    }

    public function equals(self $metrics): bool
    {
        return $metrics->getClass() === $this->class
            && $metrics->getMethod() === $this->method;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'class' => $this->class,
            'method' => $this->method,
            'line_count' => $this->lineCount,
            'arg_count' => $this->argCount,
            'return_count' => $this->returnCount,
            'variable_count' => $this->variableCount,
            'property_call_count' => $this->propertyCallCount,
            'if_count' => $this->ifCount,
            'if_nesting_level' => $this->ifNestingLevel,
            'else_count' => $this->elseCount,
            'line_count_weight' => $this->lineCountWeight,
            'arg_count_weight' => $this->argCountWeight,
            'return_count_weight' => $this->returnCountWeight,
            'variable_count_weight' => $this->variableCountWeight,
            'property_call_count_weight' => $this->propertyCallCountWeight,
            'if_count_weight' => $this->ifCountWeight,
            'if_nesting_level_weight' => $this->ifNestingLevelWeight,
            'else_count_weight' => $this->elseCountWeight,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
