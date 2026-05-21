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
        $this->complexity = $data['complexity'] ?? 0;
        $this->riskLevel = (string)($data['risk_level'] ?? $data['riskLevel'] ?? 'unknown');
        $breakdown = $data['breakdown'] ?? $data;
        $this->structuralCount = $breakdown['structural'] ?? 0;
        $this->hybridCount = $breakdown['hybrid'] ?? 0;
        $this->fundamentalCount = $breakdown['fundamental'] ?? 0;
        $this->nestingCount = $breakdown['nesting'] ?? 0;
        $this->recursionCount = $breakdown['recursion'] ?? 0;
    }
}
