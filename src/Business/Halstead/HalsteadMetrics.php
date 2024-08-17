<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics\Business\Halstead;

use InvalidArgumentException;
use JsonSerializable;

/**
 * This class represents the Halstead metrics for a given piece of code.
 */
class HalsteadMetrics implements JsonSerializable
{
    private int $n1 = 0;  // Number of distinct operators
    private int $n2 = 0;  // Number of distinct operands
    private int $N1 = 0;  // Total occurrences of operators
    private int $N2 = 0;  // Total occurrences of operands

    private int $programLength = 0;
    private int $programVocabulary = 0;
    private float $volume = 0.0;
    private float $difficulty = 0.0;
    private float $effort = 0.0;
    private float $possibleBugs = 0.0; // Added for possible bugs calculation

    private ?string $class = null;
    private ?string $file = null;

    /**
     * HalsteadMetrics constructor.
     * @param array<string, mixed> $metrics
     */
    public function __construct(array $metrics)
    {
        $this->assertArrayKeyIsPresent($metrics, 'n1');
        $this->assertArrayKeyIsPresent($metrics, 'n2');
        $this->assertArrayKeyIsPresent($metrics, 'N1');
        $this->assertArrayKeyIsPresent($metrics, 'N2');

        $this->n1 = $metrics['n1'];
        $this->n2 = $metrics['n2'];
        $this->N1 = $metrics['N1'];
        $this->N2 = $metrics['N2'];

        if (isset($metrics['class'])) {
            $this->class = $metrics['class'];
        }

        if (isset($metrics['file'])) {
            $this->file = $metrics['file'];
        }

        $this->calculateDerivedMetrics();
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
     * Calculate derived Halstead metrics (Program Length, Program Vocabulary, Volume, Difficulty, Effort).
     */
    private function calculateDerivedMetrics(): void
    {
        // Program length (N) is the sum of N1 and N2
        $this->programLength = $this->N1 + $this->N2;

        // Program vocabulary (n) is the sum of n1 and n2
        $this->programVocabulary = $this->n1 + $this->n2;

        // Halstead Volume = Program length * log2(Program vocabulary)
        $this->volume = $this->programLength * log($this->programVocabulary, 2);

        // Halstead Difficulty = (n1 / 2) * (N2 / n2)
        $this->difficulty = ($this->n1 / 2) * ($this->N2 / $this->n2);

        // Halstead Effort = Difficulty * Volume
        $this->effort = $this->difficulty * $this->volume;

        // Possible Bugs = Volume / 3000
        $this->possibleBugs = $this->volume / 3000;
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
    public function getN1(): int
    {
        return $this->n1;
    }

    public function getN2(): int
    {
        return $this->n2;
    }

    public function getTotalOperators(): int
    {
        return $this->N1;
    }

    public function getTotalOperands(): int
    {
        return $this->N2;
    }

    public function getProgramLength(): int
    {
        return $this->programLength;
    }

    public function getProgramVocabulary(): int
    {
        return $this->programVocabulary;
    }

    public function getVolume(): float
    {
        return $this->volume;
    }

    public function getDifficulty(): float
    {
        return $this->difficulty;
    }

    public function getEffort(): float
    {
        return $this->effort;
    }

    public function getPossibleBugs(): float
    {
        return $this->possibleBugs;
    }

    public function getClass(): ?string
    {
        return $this->class;
    }

    public function getFile(): ?string
    {
        return $this->file;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'n1' => $this->n1,
            'n2' => $this->n2,
            'N1' => $this->N1,
            'N2' => $this->N2,
            'program_length' => $this->programLength,
            'program_vocabulary' => $this->programVocabulary,
            'volume' => $this->volume,
            'difficulty' => $this->difficulty,
            'effort' => $this->effort,
            'possible_bugs' => $this->possibleBugs, // Added for possible bugs
            'class' => $this->class,
            'file' => $this->file
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Compares two HalsteadMetrics objects to see if they are equal.
     *
     * @param self $metrics
     * @return bool
     */
    public function equals(self $metrics): bool
    {
        return $metrics->getN1() === $this->n1
            && $metrics->getN2() === $this->n2
            && $metrics->getTotalOperators() === $this->N1
            && $metrics->getTotalOperands() === $this->N2
            && $metrics->getClass() === $this->class
            && $metrics->getFile() === $this->file;
    }
}
