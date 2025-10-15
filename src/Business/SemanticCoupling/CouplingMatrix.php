<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\SemanticCoupling;

/**
 * Efficient storage and querying of coupling relationships.
 */
class CouplingMatrix
{
    /**
     * @var array<string, array<string, float>>
     */
    private array $matrix = [];

    /**
     * @var array<string, array<string>>
     */
    private array $sharedTerms = [];

    /**
     * @var array<string, array<string, array<string>>>
     */
    private array $entityTerms = [];

    public function add(string $entity1, string $entity2, float $score, array $sharedTerms = [], array $entity1Terms = [], array $entity2Terms = []): void
    {
        // Ensure consistent ordering (entity1 < entity2 lexicographically)
        if ($entity1 > $entity2) {
            [$entity1, $entity2] = [$entity2, $entity1];
            [$entity1Terms, $entity2Terms] = [$entity2Terms, $entity1Terms];
        }

        $this->matrix[$entity1][$entity2] = $score;
        $this->sharedTerms[$entity1][$entity2] = $sharedTerms;
        $this->entityTerms[$entity1][$entity2] = [
            'entity1' => $entity1Terms,
            'entity2' => $entity2Terms
        ];
    }

    public function getScore(string $entity1, string $entity2): ?float
    {
        if ($entity1 > $entity2) {
            [$entity1, $entity2] = [$entity2, $entity1];
        }

        return $this->matrix[$entity1][$entity2] ?? null;
    }

    public function getSharedTerms(string $entity1, string $entity2): array
    {
        if ($entity1 > $entity2) {
            [$entity1, $entity2] = [$entity2, $entity1];
        }

        return $this->sharedTerms[$entity1][$entity2] ?? [];
    }

    public function getTopCoupled(int $limit = 10): array
    {
        $pairs = [];
        
        foreach ($this->matrix as $entity1 => $row) {
            foreach ($row as $entity2 => $score) {
                $pairs[] = [
                    'entity1' => $entity1,
                    'entity2' => $entity2,
                    'score' => $score,
                    'sharedTerms' => $this->sharedTerms[$entity1][$entity2] ?? []
                ];
            }
        }

        // Sort by score descending
        usort($pairs, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($pairs, 0, $limit);
    }

    public function getCouplingFor(string $entity): array
    {
        $couplings = [];
        
        // Check if entity is in first dimension
        if (isset($this->matrix[$entity])) {
            foreach ($this->matrix[$entity] as $otherEntity => $score) {
                $couplings[] = [
                    'entity' => $otherEntity,
                    'score' => $score,
                    'sharedTerms' => $this->sharedTerms[$entity][$otherEntity] ?? []
                ];
            }
        }

        // Check if entity is in second dimension
        foreach ($this->matrix as $entity1 => $row) {
            if (isset($row[$entity])) {
                $couplings[] = [
                    'entity' => $entity1,
                    'score' => $row[$entity],
                    'sharedTerms' => $this->sharedTerms[$entity1][$entity] ?? []
                ];
            }
        }

        // Sort by score descending
        usort($couplings, fn($a, $b) => $b['score'] <=> $a['score']);

        return $couplings;
    }

    public function getAllEntities(): array
    {
        $entities = [];
        
        foreach ($this->matrix as $entity1 => $row) {
            $entities[] = $entity1;
            foreach ($row as $entity2 => $score) {
                $entities[] = $entity2;
            }
        }

        return array_unique($entities);
    }

    public function getMatrix(): array
    {
        return $this->matrix;
    }

    public function toSemanticCouplingCollection(string $granularity): SemanticCouplingCollection
    {
        $collection = new SemanticCouplingCollection();
        
        foreach ($this->matrix as $entity1 => $row) {
            foreach ($row as $entity2 => $score) {
                $entityTermsData = $this->entityTerms[$entity1][$entity2] ?? ['entity1' => [], 'entity2' => []];
                
                $metric = new SemanticCouplingMetrics(
                    $entity1,
                    $entity2,
                    $score,
                    $granularity,
                    $this->sharedTerms[$entity1][$entity2] ?? [],
                    $entityTermsData['entity1'],
                    $entityTermsData['entity2']
                );
                $collection->add($metric);
            }
        }

        return $collection;
    }

    public function isEmpty(): bool
    {
        return empty($this->matrix);
    }

    public function getCount(): int
    {
        $count = 0;
        foreach ($this->matrix as $row) {
            $count += count($row);
        }
        return $count;
    }
}
