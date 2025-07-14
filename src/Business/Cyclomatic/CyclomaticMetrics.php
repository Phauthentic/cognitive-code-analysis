<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Cyclomatic;

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

    public function __construct(array $data)
    {

        $this->complexity = $data['complexity'] ?? 1;
        $this->riskLevel = (string)($data['risk_level'] ?? $data['riskLevel'] ?? 'unknown');
        $this->totalCount = $data['totalCount'] ?? $data['breakdown']['total'] ?? 0;
        $this->baseCount = $data['baseCount'] ?? $data['breakdown']['base'] ?? 1;
        $this->ifCount = $data['ifCount'] ?? $data['breakdown']['if'] ?? 0;
        $this->elseifCount = $data['elseifCount'] ?? $data['breakdown']['elseif'] ?? 0;
        $this->elseCount = $data['elseCount'] ?? $data['breakdown']['else'] ?? 0;
        $this->switchCount = $data['switchCount'] ?? $data['breakdown']['switch'] ?? 0;
        $this->caseCount = $data['caseCount'] ?? $data['breakdown']['case'] ?? 0;
        $this->defaultCount = $data['defaultCount'] ?? $data['breakdown']['default'] ?? 0;
        $this->whileCount = $data['whileCount'] ?? $data['breakdown']['while'] ?? 0;
        $this->doWhileCount = $data['doWhileCount'] ?? $data['breakdown']['do_while'] ?? 0;
        $this->forCount = $data['forCount'] ?? $data['breakdown']['for'] ?? 0;
        $this->foreachCount = $data['foreachCount'] ?? $data['breakdown']['foreach'] ?? 0;
        $this->catchCount = $data['catchCount'] ?? $data['breakdown']['catch'] ?? 0;
        $this->logicalAndCount = $data['logicalAndCount'] ?? $data['breakdown']['logical_and'] ?? 0;
        $this->logicalOrCount = $data['logicalOrCount'] ?? $data['breakdown']['logical_or'] ?? 0;
        $this->logicalXorCount = $data['logicalXorCount'] ?? $data['breakdown']['logical_xor'] ?? 0;
        $this->ternaryCount = $data['ternaryCount'] ?? $data['breakdown']['ternary'] ?? 0;
    }
}
