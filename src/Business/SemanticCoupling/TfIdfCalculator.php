<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\SemanticCoupling;

/**
 * Calculates TF-IDF weights for terms across files/entities.
 */
class TfIdfCalculator
{
    /**
     * @var array<string, array<string, int>> Term frequency per entity
     */
    private array $termFrequencies = [];

    /**
     * @var array<string, int> Document frequency (how many entities contain each term)
     */
    private array $documentFrequencies = [];

    /**
     * @var int Total number of entities
     */
    private int $totalEntities = 0;

    /**
     * Add term frequencies for an entity.
     *
     * @param string $entity Entity identifier (file, class, etc.)
     * @param array<string, int> $termFrequencies Term frequency map
     */
    public function addEntity(string $entity, array $termFrequencies): void
    {
        $this->termFrequencies[$entity] = $termFrequencies;
        $this->totalEntities++;

        // Update document frequencies
        foreach (array_keys($termFrequencies) as $term) {
            $this->documentFrequencies[$term] = ($this->documentFrequencies[$term] ?? 0) + 1;
        }
    }

    /**
     * Calculate TF-IDF weight for a term in an entity.
     */
    public function calculateTfIdf(string $entity, string $term): float
    {
        $tf = $this->calculateTf($entity, $term);
        $idf = $this->calculateIdf($term);
        
        return $tf * $idf;
    }

    /**
     * Calculate term frequency (TF) for a term in an entity.
     */
    public function calculateTf(string $entity, string $term): float
    {
        if (!isset($this->termFrequencies[$entity][$term])) {
            return 0.0;
        }

        $termCount = $this->termFrequencies[$entity][$term];
        $totalTerms = array_sum($this->termFrequencies[$entity]);

        return $totalTerms > 0 ? $termCount / $totalTerms : 0.0;
    }

    /**
     * Calculate inverse document frequency (IDF) for a term.
     */
    public function calculateIdf(string $term): float
    {
        $docFreq = $this->documentFrequencies[$term] ?? 0;
        
        if ($docFreq === 0) {
            return 0.0;
        }

        return log($this->totalEntities / (1 + $docFreq));
    }

    /**
     * Build weighted vector for an entity.
     *
     * @param string $entity
     * @return array<string, float> Term -> weight mapping
     */
    public function buildWeightedVector(string $entity): array
    {
        if (!isset($this->termFrequencies[$entity])) {
            return [];
        }

        $vector = [];
        foreach (array_keys($this->termFrequencies[$entity]) as $term) {
            $vector[$term] = $this->calculateTfIdf($entity, $term);
        }

        return $vector;
    }

    /**
     * Get all unique terms across all entities.
     *
     * @return array<string>
     */
    public function getAllTerms(): array
    {
        $terms = [];
        foreach ($this->termFrequencies as $entityTerms) {
            $terms = array_merge($terms, array_keys($entityTerms));
        }
        return array_unique($terms);
    }

    /**
     * Get entities that contain a specific term.
     *
     * @param string $term
     * @return array<string>
     */
    public function getEntitiesContainingTerm(string $term): array
    {
        $entities = [];
        foreach ($this->termFrequencies as $entity => $terms) {
            if (isset($terms[$term])) {
                $entities[] = $entity;
            }
        }
        return $entities;
    }

    /**
     * Get total number of entities.
     */
    public function getTotalEntities(): int
    {
        return $this->totalEntities;
    }

    /**
     * Get term frequencies for an entity.
     *
     * @param string $entity
     * @return array<string, int>
     */
    public function getTermFrequencies(string $entity): array
    {
        return $this->termFrequencies[$entity] ?? [];
    }

    /**
     * Get document frequency for a term.
     */
    public function getDocumentFrequency(string $term): int
    {
        return $this->documentFrequencies[$term] ?? 0;
    }

    /**
     * Clear all data.
     */
    public function clear(): void
    {
        $this->termFrequencies = [];
        $this->documentFrequencies = [];
        $this->totalEntities = 0;
    }
}
