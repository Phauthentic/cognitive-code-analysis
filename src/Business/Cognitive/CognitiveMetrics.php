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
    /**
     * @var array<string, string>
     */
    private array $metrics = [
        'lineCount' => 'lineCount',
        'argCount' => 'argCount',
        'returnCount' => 'returnCount',
        'variableCount' => 'variableCount',
        'propertyCallCount' => 'propertyCallCount',
        'ifCount' => 'ifCount',
        'ifNestingLevel' => 'ifNestingLevel',
        'elseCount' => 'elseCount'
    ];

    private string $class;
    private string $method;

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

    private ?Delta $lineCountWeightDelta = null;
    private ?Delta $argCountWeightDelta = null;
    private ?Delta $returnCountWeightDelta = null;
    private ?Delta $variableCountWeightDelta = null;
    private ?Delta $propertyCallCountWeightDelta = null;
    private ?Delta $ifCountWeightDelta = null;
    private ?Delta $ifNestingLevelWeightDelta = null;
    private ?Delta $elseCountWeightDelta = null;

    /**
     * @param array<string, mixed> $metrics
     */
    public function __construct(array $metrics)
    {
        $this->assertArrayKeyIsPresent($metrics, 'class');
        $this->assertArrayKeyIsPresent($metrics, 'method');

        $this->method = $metrics['method'];
        $this->class = $metrics['class'];

        $this->setRequiredMetricProperties($metrics);
        $this->setOptionalMetricProperties($metrics);
    }

    /**
     * @param array<string, mixed> $metrics
     * @return void
     */
    private function setRequiredMetricProperties(array $metrics): void
    {
        $missingKeys = array_diff_key($this->metrics, $metrics);
        if (!empty($missingKeys)) {
            throw new InvalidArgumentException('Missing required keys');
        }

        // Not pretty to set each but more efficient than using a loop and $this->metrics
        $this->lineCount = $metrics['lineCount'];
        $this->argCount = $metrics['argCount'];
        $this->returnCount = $metrics['returnCount'];
        $this->variableCount = $metrics['variableCount'];
        $this->propertyCallCount = $metrics['propertyCallCount'];
        $this->ifCount = $metrics['ifCount'];
        $this->ifNestingLevel = $metrics['ifNestingLevel'];
        $this->elseCount = $metrics['elseCount'];
    }

    /**
     * @param array<string, mixed> $metrics
     * @return void
     */
    private function setOptionalMetricProperties(array $metrics): void
    {
        // Not pretty to set each but more efficient than using a loop and $this->metrics
        $this->lineCountWeight = $metrics['lineCountWeight'] ?? 0.0;
        $this->argCountWeight = $metrics['argCountWeight'] ?? 0.0;
        $this->returnCountWeight = $metrics['returnCountWeight'] ?? 0.0;
        $this->variableCountWeight = $metrics['variableCountWeight'] ?? 0.0;
        $this->propertyCallCountWeight = $metrics['propertyCallCountWeight'] ?? 0.0;
        $this->ifCountWeight = $metrics['ifCountWeight'] ?? 0.0;
        $this->ifNestingLevelWeight = $metrics['ifNestingLevelWeight'] ?? 0.0;
        $this->elseCountWeight = $metrics['elseCountWeight'] ?? 0.0;
    }

    private function assertSame(self $other): void
    {
        if ($this->equals($other)) {
            return;
        }

        throw new InvalidArgumentException(sprintf(
            'Cannot calculate deltas for different methods: %s::%s and %s::%s',
            $this->getClass(),
            $this->getMethod(),
            $other->getClass(),
            $other->getMethod()
        ));
    }

    /**
     * Calculate delta between current instance and another instance of CognitiveMetrics.
     */
    public function calculateDeltas(self $other): void
    {
        $this->assertSame($other);

        $this->lineCountWeightDelta = new Delta($other->getLineCountWeight(), $this->lineCountWeight);
        $this->argCountWeightDelta = new Delta($other->getArgCountWeight(), $this->argCountWeight);
        $this->returnCountWeightDelta = new Delta($other->getReturnCountWeight(), $this->returnCountWeight);
        $this->variableCountWeightDelta = new Delta($other->getVariableCountWeight(), $this->variableCountWeight);
        $this->propertyCallCountWeightDelta = new Delta($other->getPropertyCallCountWeight(), $this->propertyCallCountWeight);
        $this->ifCountWeightDelta = new Delta($other->getIfCountWeight(), $this->ifCountWeight);
        $this->ifNestingLevelWeightDelta = new Delta($other->getIfNestingLevelWeight(), $this->ifNestingLevelWeight);
        $this->elseCountWeightDelta = new Delta($other->getElseCountWeight(), $this->elseCountWeight);
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
        if (!isset($array[$key])) {
            throw new InvalidArgumentException("Missing required key: $key");
        }
    }

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

    public function getLineCountWeightDelta(): ?Delta
    {
        return $this->lineCountWeightDelta;
    }

    public function getArgCountWeightDelta(): ?Delta
    {
        return $this->argCountWeightDelta;
    }

    public function getReturnCountWeightDelta(): ?Delta
    {
        return $this->returnCountWeightDelta;
    }

    public function getVariableCountWeightDelta(): ?Delta
    {
        return $this->variableCountWeightDelta;
    }

    public function getPropertyCallCountWeightDelta(): ?Delta
    {
        return $this->propertyCallCountWeightDelta;
    }

    public function getIfCountWeightDelta(): ?Delta
    {
        return $this->ifCountWeightDelta;
    }

    public function getIfNestingLevelWeightDelta(): ?Delta
    {
        return $this->ifNestingLevelWeightDelta;
    }

    public function getElseCountWeightDelta(): ?Delta
    {
        return $this->elseCountWeightDelta;
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
            'lineCount' => $this->lineCount,
            'argCount' => $this->argCount,
            'returnCount' => $this->returnCount,
            'variableCount' => $this->variableCount,
            'propertyCallCount' => $this->propertyCallCount,
            'ifCount' => $this->ifCount,
            'ifNestingLevel' => $this->ifNestingLevel,
            'elseCount' => $this->elseCount,
            'lineCountWeight' => $this->lineCountWeight,
            'argCountWeight' => $this->argCountWeight,
            'returnCountWeight' => $this->returnCountWeight,
            'variableCountWeight' => $this->variableCountWeight,
            'propertyCallCountWeight' => $this->propertyCallCountWeight,
            'ifCountWeight' => $this->ifCountWeight,
            'ifNestingLevelWeight' => $this->ifNestingLevelWeight,
            'elseCountWeight' => $this->elseCountWeight,
            'lineCountWeightDelta' => $this->lineCountWeightDelta,
            'argCountWeightDelta' => $this->argCountWeightDelta,
            'returnCountWeightDelta' => $this->returnCountWeightDelta,
            'variableCountWeightDelta' => $this->variableCountWeightDelta,
            'propertyCallCountWeightDelta' => $this->propertyCallCountWeightDelta,
            'ifCountWeightDelta' => $this->ifCountWeightDelta,
            'ifNestingLevelWeightDelta' => $this->ifNestingLevelWeightDelta,
            'elseCountWeightDelta' => $this->elseCountWeightDelta,
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
