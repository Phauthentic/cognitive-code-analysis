<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Halstead;

/**
 * Represents Halstead metrics for a program.
 *
 * This class encapsulates the Halstead metrics calculated for a program,
 * including the number of unique operators and operands, total operators
 * and operands, program length, vocabulary, volume, difficulty, effort,
 * and the fully qualified name of the program.
 *
 * @SuppressWarnings(ShortVariable)
 */
class HalsteadMetrics
{
    public int $n1;
    public int $n2;
    public int $N1;
    public int $N2;
    public int $programLength;
    public int $programVocabulary;
    public float $volume;
    public float $difficulty;
    public float $effort;
    public string $fqName;

    /**
     * @param array<string, int|float|string> $data
     */
    public function __construct(array $data)
    {
        $this->n1 = (int)$data['n1'];
        $this->n2 = (int)$data['n2'];
        $this->N1 = (int)$data['N1'];
        $this->N2 = (int)$data['N2'];
        $this->programLength = (int)$data['programLength'];
        $this->programVocabulary = (int)$data['programVocabulary'];
        $this->volume = (float)$data['volume'];
        $this->difficulty = (float)$data['difficulty'];
        $this->effort = (float)$data['effort'];
        $this->fqName = (string)$data['fqName'];
    }

    public function getVolume(): float
    {
        return $this->volume;
    }
}
