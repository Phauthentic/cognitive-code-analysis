<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Cyclomatic;

/**
 * @SuppressWarnings(TooManyFields)
 */
class CyclomaticMetrics
{
    /**
     * The cyclomatic complexity value.
     * @var int
     */
    public int $complexity;

    /**
     * The risk level associated with the complexity.
     * @var string
     */
    public string $riskLevel;

    /**
     * The total number of decision points.
     * @var int
     */
    public int $totalCount;

    /**
     * The base complexity (usually 1).
     * @var int
     */
    public int $baseCount;

    /**
     * Number of if statements.
     * @var int
     */
    public int $ifCount;

    /**
     * Number of elseif statements.
     * @var int
     */
    public int $elseifCount;

    /**
     * Number of else statements.
     * @var int
     */
    public int $elseCount;

    /**
     * Number of switch statements.
     * @var int
     */
    public int $switchCount;

    /**
     * Number of case statements.
     * @var int
     */
    public int $caseCount;

    /**
     * Number of default statements.
     * @var int
     */
    public int $defaultCount;

    /**
     * Number of while loops.
     * @var int
     */
    public int $whileCount;

    /**
     * Number of do-while loops.
     * @var int
     */
    public int $doWhileCount;

    /**
     * Number of for loops.
     * @var int
     */
    public int $forCount;

    /**
     * Number of foreach loops.
     * @var int
     */
    public int $foreachCount;

    /**
     * Number of catch blocks.
     * @var int
     */
    public int $catchCount;

    /**
     * Number of logical AND (&&) operations.
     * @var int
     */
    public int $logicalAndCount;

    /**
     * Number of logical OR (||) operations.
     * @var int
     */
    public int $logicalOrCount;

    /**
     * Number of logical XOR operations.
     * @var int
     */
    public int $logicalXorCount;

    /**
     * Number of ternary operations.
     * @var int
     */
    public int $ternaryCount;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $breakdown = $data['breakdown'] ?? null;
        if (!is_array($breakdown)) {
            $breakdown = [];
        }

        $this->complexity = $this->resolveIntValue($data['complexity'] ?? null, 1);
        $this->riskLevel = $this->resolveRiskLevel($data);
        $this->totalCount = $this->resolveCountValue($data, $breakdown, 'totalCount', 'total', 0);
        $this->baseCount = $this->resolveCountValue($data, $breakdown, 'baseCount', 'base', 1);
        $this->ifCount = $this->resolveCountValue($data, $breakdown, 'ifCount', 'if', 0);
        $this->elseifCount = $this->resolveCountValue($data, $breakdown, 'elseifCount', 'elseif', 0);
        $this->elseCount = $this->resolveCountValue($data, $breakdown, 'elseCount', 'else', 0);
        $this->switchCount = $this->resolveCountValue($data, $breakdown, 'switchCount', 'switch', 0);
        $this->caseCount = $this->resolveCountValue($data, $breakdown, 'caseCount', 'case', 0);
        $this->defaultCount = $this->resolveCountValue($data, $breakdown, 'defaultCount', 'default', 0);
        $this->whileCount = $this->resolveCountValue($data, $breakdown, 'whileCount', 'while', 0);
        $this->doWhileCount = $this->resolveCountValue($data, $breakdown, 'doWhileCount', 'do_while', 0);
        $this->forCount = $this->resolveCountValue($data, $breakdown, 'forCount', 'for', 0);
        $this->foreachCount = $this->resolveCountValue($data, $breakdown, 'foreachCount', 'foreach', 0);
        $this->catchCount = $this->resolveCountValue($data, $breakdown, 'catchCount', 'catch', 0);
        $this->logicalAndCount = $this->resolveCountValue($data, $breakdown, 'logicalAndCount', 'logical_and', 0);
        $this->logicalOrCount = $this->resolveCountValue($data, $breakdown, 'logicalOrCount', 'logical_or', 0);
        $this->logicalXorCount = $this->resolveCountValue($data, $breakdown, 'logicalXorCount', 'logical_xor', 0);
        $this->ternaryCount = $this->resolveCountValue($data, $breakdown, 'ternaryCount', 'ternary', 0);
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
