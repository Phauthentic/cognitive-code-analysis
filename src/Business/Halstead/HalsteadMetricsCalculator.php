<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Halstead;

/**
 * @SuppressWarnings("PHPMD.ShortVariable")
 */
class HalsteadMetricsCalculator implements HalsteadMetricsCalculatorInterface
{
    /**
     * Calculate complete Halstead metrics for given operators and operands.
     *
     * @param array<string> $operators Array of operator types
     * @param array<string> $operands Array of operand values
     * @param string $identifier Identifier for the metrics (class name or method FQN)
     * @return array<string, mixed> Array containing all Halstead metrics
     */
    public function calculateMetrics(array $operators, array $operands, string $identifier): array
    {
        // Step 1: Count distinct and total occurrences of operators and operands
        $distinctOperators = count(array_unique($operators));
        $distinctOperands = count(array_unique($operands));
        $totalOperators = count($operators);
        $totalOperands = count($operands);

        // Step 2: Calculate basic metrics
        $programLength = $this->calculateProgramLength($totalOperators, $totalOperands);
        $programVocabulary = $this->calculateProgramVocabulary($distinctOperators, $distinctOperands);

        // Step 3: Calculate advanced metrics
        $volume = $this->calculateVolume($programLength, $programVocabulary);
        $difficulty = $this->calculateDifficulty($distinctOperators, $totalOperands, $distinctOperands);
        $effort = $difficulty * $volume;

        // Step 4: Prepare the results array
        return [
            'n1' => $distinctOperators,
            'n2' => $distinctOperands,
            'N1' => $totalOperators,
            'N2' => $totalOperands,
            'programLength' => $programLength,
            'programVocabulary' => $programVocabulary,
            'volume' => $volume,
            'difficulty' => $difficulty,
            'effort' => $effort,
            'fqName' => $identifier,
        ];
    }

    /**
     * Calculate the program length.
     *
     * @param int $N1 The total occurrences of operators
     * @param int $N2 The total occurrences of operands
     * @return int The program length
     */
    public function calculateProgramLength(int $N1, int $N2): int
    {
        return $N1 + $N2;
    }

    /**
     * Calculate the program vocabulary.
     *
     * @param int $n1 The count of distinct operators
     * @param int $n2 The count of distinct operands
     * @return int The program vocabulary
     */
    public function calculateProgramVocabulary(int $n1, int $n2): int
    {
        return $n1 + $n2;
    }

    /**
     * Calculate the volume of the program.
     *
     * @param int $programLength The length of the program
     * @param int $programVocabulary The vocabulary of the program
     * @return float The volume of the program
     */
    public function calculateVolume(int $programLength, int $programVocabulary): float
    {
        if ($programVocabulary <= 0) {
            return 0.0;
        }
        return $programLength * log($programVocabulary, 2);
    }

    /**
     * Calculate the difficulty of the program.
     *
     * @param int $n1 The count of distinct operators
     * @param int $N2 The total occurrences of operands
     * @param int $n2 The count of distinct operands
     * @return float The difficulty of the program
     */
    public function calculateDifficulty(int $n1, int $N2, int $n2): float
    {
        if ($n2 === 0) {
            return 0.0;
        }
        return ($n1 / 2) * ($N2 / $n2);
    }
}
