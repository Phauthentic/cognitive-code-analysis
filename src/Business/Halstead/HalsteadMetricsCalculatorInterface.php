<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Halstead;

/**
 * @SuppressWarnings("PHPMD.ShortVariable")
 */
interface HalsteadMetricsCalculatorInterface
{
    /**
     * Calculate complete Halstead metrics for given operators and operands.
     *
     * @param array<string> $operators Array of operator types
     * @param array<string> $operands Array of operand values
     * @param string $identifier Identifier for the metrics (class name or method FQN)
     * @return array<string, mixed> Array containing all Halstead metrics
     */
    public function calculateMetrics(array $operators, array $operands, string $identifier): array;

    /**
     * Calculate the volume of the program.
     *
     * @param int $programLength The length of the program
     * @param int $programVocabulary The vocabulary of the program
     * @return float The volume of the program
     */
    public function calculateVolume(int $programLength, int $programVocabulary): float;

    /**
     * Calculate the difficulty of the program.
     *
     * @param int $n1 The count of distinct operators
     * @param int $N2 The total occurrences of operands
     * @param int $n2 The count of distinct operands
     * @return float The difficulty of the program
     */
    public function calculateDifficulty(int $n1, int $N2, int $n2): float;

    /**
     * Calculate the program length.
     *
     * @param int $N1 The total occurrences of operators
     * @param int $N2 The total occurrences of operands
     * @return int The program length
     */
    public function calculateProgramLength(int $N1, int $N2): int;

    /**
     * Calculate the program vocabulary.
     *
     * @param int $n1 The count of distinct operators
     * @param int $n2 The count of distinct operands
     * @return int The program vocabulary
     */
    public function calculateProgramVocabulary(int $n1, int $n2): int;
}
