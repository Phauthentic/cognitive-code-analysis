<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\SemanticCoupling;

/**
 * Data object representing a semantic coupling measurement between two entities.
 */
class SemanticCouplingMetrics
{
    public function __construct(
        private readonly string $entity1,
        private readonly string $entity2,
        private readonly float $score,
        private readonly string $granularity,
        private readonly array $sharedTerms = [],
        private readonly array $entity1Terms = [],
        private readonly array $entity2Terms = []
    ) {
    }

    public function getEntity1(): string
    {
        return $this->entity1;
    }

    public function getEntity2(): string
    {
        return $this->entity2;
    }

    public function getScore(): float
    {
        return $this->score;
    }

    public function getGranularity(): string
    {
        return $this->granularity;
    }

    public function getSharedTerms(): array
    {
        return $this->sharedTerms;
    }

    public function getEntity1Terms(): array
    {
        return $this->entity1Terms;
    }

    public function getEntity2Terms(): array
    {
        return $this->entity2Terms;
    }

    public function toArray(): array
    {
        return [
            'entity1' => $this->entity1,
            'entity2' => $this->entity2,
            'score' => $this->score,
            'granularity' => $this->granularity,
            'sharedTerms' => $this->sharedTerms,
            'entity1Terms' => $this->entity1Terms,
            'entity2Terms' => $this->entity2Terms,
        ];
    }
}
