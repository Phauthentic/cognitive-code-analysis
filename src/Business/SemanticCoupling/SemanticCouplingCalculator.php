<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\SemanticCoupling;

/**
 * Main calculation orchestrator for semantic coupling analysis.
 */
class SemanticCouplingCalculator
{
    public function __construct(
        private readonly TermExtractor $termExtractor,
        private readonly TfIdfCalculator $tfIdfCalculator
    ) {
    }

    /**
     * Calculate semantic coupling between entities.
     *
     * @param array<string, array<string>> $entityIdentifiers Entity -> identifiers mapping
     * @param string $granularity Analysis granularity (file, class, module)
     * @return SemanticCouplingCollection
     */
    public function calculate(array $entityIdentifiers, string $granularity = 'file'): SemanticCouplingCollection
    {
        $this->tfIdfCalculator->clear();

        // Extract terms for each entity
        $entityTerms = [];
        foreach ($entityIdentifiers as $entity => $identifiers) {
            $terms = $this->termExtractor->extractTermsFromIdentifiers($identifiers);
            $entityTerms[$entity] = $terms;
            $this->tfIdfCalculator->addEntity($entity, $terms);
        }

        // Calculate coupling between all entity pairs
        $couplingMatrix = new CouplingMatrix();
        $entities = array_keys($entityIdentifiers);
        
        for ($i = 0; $i < count($entities); $i++) {
            for ($j = $i + 1; $j < count($entities); $j++) {
                $entity1 = $entities[$i];
                $entity2 = $entities[$j];
                
                $similarity = $this->calculateCosineSimilarity($entity1, $entity2);
                $sharedTerms = $this->getSharedTerms($entityTerms[$entity1], $entityTerms[$entity2]);
                
                // Get entity terms as arrays of term names
                $entity1TermNames = array_keys($entityTerms[$entity1]);
                $entity2TermNames = array_keys($entityTerms[$entity2]);
                
                $couplingMatrix->add($entity1, $entity2, $similarity, $sharedTerms, $entity1TermNames, $entity2TermNames);
            }
        }

        return $couplingMatrix->toSemanticCouplingCollection($granularity);
    }

    /**
     * Calculate cosine similarity between two entities.
     */
    private function calculateCosineSimilarity(string $entity1, string $entity2): float
    {
        $vector1 = $this->tfIdfCalculator->buildWeightedVector($entity1);
        $vector2 = $this->tfIdfCalculator->buildWeightedVector($entity2);

        if (empty($vector1) || empty($vector2)) {
            return 0.0;
        }

        // Get all unique terms from both vectors
        $allTerms = array_unique(array_merge(array_keys($vector1), array_keys($vector2)));
        
        if (empty($allTerms)) {
            return 0.0;
        }

        // Calculate dot product
        $dotProduct = 0.0;
        foreach ($allTerms as $term) {
            $weight1 = $vector1[$term] ?? 0.0;
            $weight2 = $vector2[$term] ?? 0.0;
            $dotProduct += $weight1 * $weight2;
        }

        // Calculate magnitudes
        $magnitude1 = $this->calculateMagnitude($vector1);
        $magnitude2 = $this->calculateMagnitude($vector2);

        if ($magnitude1 === 0.0 || $magnitude2 === 0.0) {
            return 0.0;
        }

        return $dotProduct / ($magnitude1 * $magnitude2);
    }

    /**
     * Calculate magnitude of a vector.
     */
    private function calculateMagnitude(array $vector): float
    {
        $sum = 0.0;
        foreach ($vector as $weight) {
            $sum += $weight * $weight;
        }
        return sqrt($sum);
    }

    /**
     * Get shared terms between two entities.
     */
    private function getSharedTerms(array $terms1, array $terms2): array
    {
        $shared = [];
        foreach ($terms1 as $term => $freq1) {
            if (isset($terms2[$term])) {
                $shared[] = $term;
            }
        }
        return $shared;
    }

    /**
     * Aggregate coupling by module/directory.
     *
     * @param SemanticCouplingCollection $couplings
     * @param callable $entityToModule Function to map entity to module
     * @return SemanticCouplingCollection
     */
    public function aggregateByModule(SemanticCouplingCollection $couplings, callable $entityToModule): SemanticCouplingCollection
    {
        $moduleCouplings = new SemanticCouplingCollection();
        $modulePairs = [];

        foreach ($couplings as $coupling) {
            $module1 = $entityToModule($coupling->getEntity1());
            $module2 = $entityToModule($coupling->getEntity2());

            if ($module1 === $module2) {
                continue; // Skip intra-module coupling
            }

            $key = $module1 < $module2 ? "$module1|$module2" : "$module2|$module1";
            
            if (!isset($modulePairs[$key])) {
                $modulePairs[$key] = [
                    'module1' => $module1,
                    'module2' => $module2,
                    'scores' => [],
                    'sharedTerms' => []
                ];
            }

            $modulePairs[$key]['scores'][] = $coupling->getScore();
            $modulePairs[$key]['sharedTerms'] = array_merge(
                $modulePairs[$key]['sharedTerms'],
                $coupling->getSharedTerms()
            );
        }

        // Calculate average coupling for each module pair
        foreach ($modulePairs as $pair) {
            $avgScore = array_sum($pair['scores']) / count($pair['scores']);
            $uniqueSharedTerms = array_unique($pair['sharedTerms']);

            $metric = new SemanticCouplingMetrics(
                $pair['module1'],
                $pair['module2'],
                $avgScore,
                'module',
                $uniqueSharedTerms
            );

            $moduleCouplings->add($metric);
        }

        return $moduleCouplings;
    }

    /**
     * Get term extractor instance.
     */
    public function getTermExtractor(): TermExtractor
    {
        return $this->termExtractor;
    }

    /**
     * Get TF-IDF calculator instance.
     */
    public function getTfIdfCalculator(): TfIdfCalculator
    {
        return $this->tfIdfCalculator;
    }
}
