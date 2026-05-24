<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Cognitive;

use Phauthentic\CognitiveCodeAnalysis\Business\Halstead\HalsteadMetrics;
use Phauthentic\CognitiveCodeAnalysis\Business\Cyclomatic\CyclomaticMetrics;
use InvalidArgumentException;
use JsonSerializable;

/**
 * @SuppressWarnings(PHPMD)
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
        'elseCount' => 'elseCount',
    ];

    private string $class;
    private string $method;
    private string $file;
    private int $line;
    private ?int $timesChanged = null;

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

    private ?Delta $halsteadVolumeDelta = null;
    private ?Delta $halsteadDifficultyDelta = null;
    private ?Delta $halsteadEffortDelta = null;
    private ?Delta $cyclomaticComplexityDelta = null;

    private ?HalsteadMetrics $halstead = null;
    private ?CyclomaticMetrics $cyclomatic = null;
    private ?float $coverage = null;

    /**
     * @param array<string, mixed> $metrics
     */
    public function __construct(array $metrics)
    {
        $this->assertArrayKeyIsPresent($metrics, 'class');
        $this->assertArrayKeyIsPresent($metrics, 'method');

        $this->method = $this->resolveStringValue($metrics['method']);
        $this->class = $this->resolveStringValue($metrics['class']);
        $this->file = isset($metrics['file']) ? $this->resolveStringValue($metrics['file']) : '';
        $this->line = isset($metrics['line']) ? $this->resolveIntValue($metrics['line'], 0) : 0;

        $this->setRequiredMetricProperties($metrics);
        $this->setOptionalMetricProperties($metrics);

        if (isset($metrics['halstead']) && is_array($metrics['halstead'])) {
            /** @var array<string, mixed> $halsteadData */
            $halsteadData = $metrics['halstead'];
            $this->halstead = new HalsteadMetrics($this->resolveHalsteadData($halsteadData));
        }

        // Handle baseline format with individual halstead fields
        if (isset($metrics['halsteadVolume']) && !isset($metrics['halstead'])) {
            $this->halstead = new HalsteadMetrics([
                'n1' => 0, 'n2' => 0, 'N1' => 0, 'N2' => 0,
                'programLength' => 0, 'programVocabulary' => 0,
                'volume' => $this->resolveFloatValue($metrics['halsteadVolume'], 0.0),
                'difficulty' => $this->resolveFloatValue($metrics['halsteadDifficulty'] ?? null, 0.0),
                'effort' => $this->resolveFloatValue($metrics['halsteadEffort'] ?? null, 0.0),
                'fqName' => $this->class . '::' . $this->method,
            ]);
        }

        if (!isset($metrics['cyclomatic_complexity'])) {
            // Handle baseline format with individual cyclomatic fields
            if (isset($metrics['cyclomaticComplexity']) && !isset($metrics['cyclomatic_complexity'])) {
                $riskLevel = $metrics['cyclomaticRiskLevel'] ?? 'unknown';
                $this->cyclomatic = new CyclomaticMetrics([
                    'complexity' => $this->resolveIntValue($metrics['cyclomaticComplexity'], 1),
                    'riskLevel' => is_string($riskLevel) ? $riskLevel : 'unknown',
                ]);
            }
            return;
        }

        if (is_array($metrics['cyclomatic_complexity'])) {
            /** @var array<string, mixed> $cyclomaticData */
            $cyclomaticData = $metrics['cyclomatic_complexity'];
            $this->cyclomatic = new CyclomaticMetrics($cyclomaticData);
        }
    }

    /**
     * @param array<string, mixed> $metrics
     * @return void
     */
    private function setRequiredMetricProperties(array $metrics): void
    {
        $missingKeys = array_diff_key($this->metrics, $metrics);
        if (!empty($missingKeys)) {
            $class = is_string($metrics['class'] ?? null) ? $metrics['class'] : 'Unknown';
            $method = is_string($metrics['method'] ?? null) ? $metrics['method'] : 'Unknown';
            $file = is_string($metrics['file'] ?? null) ? $metrics['file'] : 'Unknown';

            $errorMessage = sprintf(
                'Missing required keys for %s::%s in file %s: %s. Available keys: %s',
                $class,
                $method,
                $file,
                implode(', ', $missingKeys),
                implode(', ', array_keys($metrics))
            );

            throw new InvalidArgumentException($errorMessage);
        }

        // Not pretty to set each but more efficient than using a loop and $this->metrics
        $this->lineCount = $this->resolveIntValue($metrics['lineCount'], 0);
        $this->argCount = $this->resolveIntValue($metrics['argCount'], 0);
        $this->returnCount = $this->resolveIntValue($metrics['returnCount'], 0);
        $this->variableCount = $this->resolveIntValue($metrics['variableCount'], 0);
        $this->propertyCallCount = $this->resolveIntValue($metrics['propertyCallCount'], 0);
        $this->ifCount = $this->resolveIntValue($metrics['ifCount'], 0);
        $this->ifNestingLevel = $this->resolveIntValue($metrics['ifNestingLevel'], 0);
        $this->elseCount = $this->resolveIntValue($metrics['elseCount'], 0);
    }

    /**
     * @param array<string, mixed> $metrics
     * @return void
     */
    private function setOptionalMetricProperties(array $metrics): void
    {
        // Not pretty to set each but more efficient than using a loop and $this->metrics
        $this->lineCountWeight = $this->resolveFloatValue($metrics['lineCountWeight'] ?? null, 0.0);
        $this->argCountWeight = $this->resolveFloatValue($metrics['argCountWeight'] ?? null, 0.0);
        $this->returnCountWeight = $this->resolveFloatValue($metrics['returnCountWeight'] ?? null, 0.0);
        $this->variableCountWeight = $this->resolveFloatValue($metrics['variableCountWeight'] ?? null, 0.0);
        $this->propertyCallCountWeight = $this->resolveFloatValue($metrics['propertyCallCountWeight'] ?? null, 0.0);
        $this->ifCountWeight = $this->resolveFloatValue($metrics['ifCountWeight'] ?? null, 0.0);
        $this->ifNestingLevelWeight = $this->resolveFloatValue($metrics['ifNestingLevelWeight'] ?? null, 0.0);
        $this->elseCountWeight = $this->resolveFloatValue($metrics['elseCountWeight'] ?? null, 0.0);
    }

    public function setTimesChanged(int $timesChanged): void
    {
        $this->timesChanged = $timesChanged;
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

        // Calculate Halstead deltas if both metrics have Halstead data
        if ($this->halstead !== null && $other->getHalstead() !== null) {
            $this->halsteadVolumeDelta = new Delta($other->getHalstead()->volume, $this->halstead->volume);
            $this->halsteadDifficultyDelta = new Delta($other->getHalstead()->difficulty, $this->halstead->difficulty);
            $this->halsteadEffortDelta = new Delta($other->getHalstead()->effort, $this->halstead->effort);
        }

        // Calculate Cyclomatic delta if both metrics have Cyclomatic data
        if ($this->cyclomatic === null || $other->getCyclomatic() === null) {
            return;
        }

        $this->cyclomaticComplexityDelta = new Delta($other->getCyclomatic()->complexity, $this->cyclomatic->complexity);
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

    public function setCoverage(?float $coverage): void
    {
        $this->coverage = $coverage;
    }

    public function getCoverage(): ?float
    {
        return $this->coverage;
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

    public function getHalsteadVolumeDelta(): ?Delta
    {
        return $this->halsteadVolumeDelta;
    }

    public function getHalsteadDifficultyDelta(): ?Delta
    {
        return $this->halsteadDifficultyDelta;
    }

    public function getHalsteadEffortDelta(): ?Delta
    {
        return $this->halsteadEffortDelta;
    }

    public function getCyclomaticComplexityDelta(): ?Delta
    {
        return $this->cyclomaticComplexityDelta;
    }

    public function getTimesChanged(): int
    {
        return $this->timesChanged ?? 0;
    }

    public function getFileName(): string
    {
        return $this->file;
    }

    public function getLine(): int
    {
        return $this->line;
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
            'file' => $this->file,
            'line' => $this->line,
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
            'coverage' => $this->coverage,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function getHalstead(): ?HalsteadMetrics
    {
        return $this->halstead;
    }

    public function getCyclomatic(): ?CyclomaticMetrics
    {
        return $this->cyclomatic;
    }

    private function resolveStringValue(mixed $value): string
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('Expected string value.');
        }

        return $value;
    }

    private function resolveIntValue(mixed $value, int $default): int
    {
        return is_int($value) ? $value : $default;
    }

    private function resolveFloatValue(mixed $value, float $default): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        return $default;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, int|float|string>
     */
    private function resolveHalsteadData(array $data): array
    {
        $fqName = $data['fqName'] ?? '';

        return [
            'n1' => $this->resolveIntValue($data['n1'] ?? null, 0),
            'n2' => $this->resolveIntValue($data['n2'] ?? null, 0),
            'N1' => $this->resolveIntValue($data['N1'] ?? null, 0),
            'N2' => $this->resolveIntValue($data['N2'] ?? null, 0),
            'programLength' => $this->resolveIntValue($data['programLength'] ?? null, 0),
            'programVocabulary' => $this->resolveIntValue($data['programVocabulary'] ?? null, 0),
            'volume' => $this->resolveFloatValue($data['volume'] ?? null, 0.0),
            'difficulty' => $this->resolveFloatValue($data['difficulty'] ?? null, 0.0),
            'effort' => $this->resolveFloatValue($data['effort'] ?? null, 0.0),
            'fqName' => is_string($fqName) ? $fqName : '',
        ];
    }
}
