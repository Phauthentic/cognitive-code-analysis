<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Churn;

use JsonSerializable;

/**
 * ChurnMetrics represents churn data for a single class.
 */
class ChurnMetrics implements JsonSerializable
{
    private string $className;
    private string $file;
    private float $score;
    private int $timesChanged;
    private float $churn;
    private ?float $coverage = null;
    private ?float $riskChurn = null;
    private ?string $riskLevel = null;

    public function __construct(
        string $className,
        string $file,
        float $score,
        int $timesChanged,
        float $churn,
        ?float $coverage = null,
        ?float $riskChurn = null,
        ?string $riskLevel = null
    ) {
        $this->className = $className;
        $this->file = $file;
        $this->score = $score;
        $this->timesChanged = $timesChanged;
        $this->churn = $churn;
        $this->coverage = $coverage;
        $this->riskChurn = $riskChurn;
        $this->riskLevel = $riskLevel;
    }

    /**
     * Create ChurnMetrics from array data (for backward compatibility).
     *
     * @param string $className
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(string $className, array $data): self
    {
        return new self(
            className: $className,
            file: $data['file'] ?? '',
            score: (float)($data['score'] ?? 0),
            timesChanged: (int)($data['timesChanged'] ?? 0),
            churn: (float)($data['churn'] ?? 0),
            coverage: isset($data['coverage']) ? (float)$data['coverage'] : null,
            riskChurn: isset($data['riskChurn']) ? (float)$data['riskChurn'] : null,
            riskLevel: $data['riskLevel'] ?? null
        );
    }

    /**
     * Convert to array format (for backward compatibility).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'file' => $this->file,
            'score' => $this->score,
            'timesChanged' => $this->timesChanged,
            'churn' => $this->churn,
            'coverage' => $this->coverage,
            'riskChurn' => $this->riskChurn,
            'riskLevel' => $this->riskLevel,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'className' => $this->className,
            'file' => $this->file,
            'score' => $this->score,
            'timesChanged' => $this->timesChanged,
            'churn' => $this->churn,
            'coverage' => $this->coverage,
            'riskChurn' => $this->riskChurn,
            'riskLevel' => $this->riskLevel,
        ];
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getFile(): string
    {
        return $this->file;
    }

    public function getScore(): float
    {
        return $this->score;
    }

    public function setScore(float $score): void
    {
        $this->score = $score;
    }

    public function getTimesChanged(): int
    {
        return $this->timesChanged;
    }

    public function setTimesChanged(int $timesChanged): void
    {
        $this->timesChanged = $timesChanged;
    }

    public function getChurn(): float
    {
        return $this->churn;
    }

    public function setChurn(float $churn): void
    {
        $this->churn = $churn;
    }

    public function getCoverage(): ?float
    {
        return $this->coverage;
    }

    public function setCoverage(?float $coverage): void
    {
        $this->coverage = $coverage;
    }

    public function getRiskChurn(): ?float
    {
        return $this->riskChurn;
    }

    public function setRiskChurn(?float $riskChurn): void
    {
        $this->riskChurn = $riskChurn;
    }

    public function getRiskLevel(): ?string
    {
        return $this->riskLevel;
    }

    public function setRiskLevel(?string $riskLevel): void
    {
        $this->riskLevel = $riskLevel;
    }

    /**
     * Check if this metric has coverage data.
     */
    public function hasCoverageData(): bool
    {
        return $this->coverage !== null;
    }

    /**
     * Check if this metric has risk data.
     */
    public function hasRiskData(): bool
    {
        return $this->riskChurn !== null && $this->riskLevel !== null;
    }
}
