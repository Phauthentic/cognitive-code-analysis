<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Halstead;

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

    public function __construct(array $data)
    {
        $this->n1 = $data['n1'];
        $this->n2 = $data['n2'];
        $this->N1 = $data['N1'];
        $this->N2 = $data['N2'];
        $this->programLength = $data['programLength'];
        $this->programVocabulary = $data['programVocabulary'];
        $this->volume = $data['volume'];
        $this->difficulty = $data['difficulty'];
        $this->effort = $data['effort'];
        $this->fqName = $data['fqName'];
    }

    public function getVolume(): float
    {
        return $this->volume;
    }
}
