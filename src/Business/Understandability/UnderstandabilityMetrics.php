<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Understandability;

class UnderstandabilityMetrics
{
    public int $complexity;

    public string $riskLevel;

    public int $structuralCount;

    public int $hybridCount;

    public int $fundamentalCount;

    public int $nestingCount;

    public int $recursionCount;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $breakdown = $data['breakdown'] ?? null;
        if (!is_array($breakdown)) {
            $breakdown = $data;
        }

        /** @var array<string, mixed> $breakdown */
        $this->complexity = $this->resolveIntValue($data['complexity'] ?? null, 0);
        $this->riskLevel = $this->resolveRiskLevel($data);
        $this->structuralCount = $this->resolveCountValue($data, $breakdown, 'structuralCount', 'structural', 0);
        $this->hybridCount = $this->resolveCountValue($data, $breakdown, 'hybridCount', 'hybrid', 0);
        $this->fundamentalCount = $this->resolveCountValue($data, $breakdown, 'fundamentalCount', 'fundamental', 0);
        $this->nestingCount = $this->resolveCountValue($data, $breakdown, 'nestingCount', 'nesting', 0);
        $this->recursionCount = $this->resolveCountValue($data, $breakdown, 'recursionCount', 'recursion', 0);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function resolveRiskLevel(array $data): string
    {
        $value = $data['risk_level'] ?? $data['riskLevel'] ?? 'unknown';

        return is_string($value) ? $value : 'unknown';
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $breakdown
     */
    private function resolveCountValue(
        array $data,
        array $breakdown,
        string $dataKey,
        string $breakdownKey,
        int $default
    ): int {
        if (array_key_exists($dataKey, $data)) {
            return $this->resolveIntValue($data[$dataKey], $default);
        }

        return $this->resolveIntValue($breakdown[$breakdownKey] ?? null, $default);
    }

    private function resolveIntValue(mixed $value, int $default): int
    {
        return is_int($value) ? $value : $default;
    }
}
