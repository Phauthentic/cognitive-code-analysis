<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\SemanticCoupling\Report;

use Phauthentic\CognitiveCodeAnalysis\Business\SemanticCoupling\SemanticCouplingCollection;

/**
 * JSON report generator for semantic coupling analysis.
 */
class JsonReport extends AbstractReport
{
    /**
     * @throws \JsonException|\Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException
     */
    public function export(SemanticCouplingCollection $couplings, string $filename): void
    {
        $this->assertFileIsWritable($filename);

        $data = [
            'createdAt' => $this->getCurrentTimestamp(),
            'totalCouplings' => $couplings->count(),
            'granularity' => $couplings->count() > 0 ? $couplings->current()->getGranularity() : 'unknown',
            'summary' => [
                'averageScore' => $couplings->getAverageScore(),
                'maxScore' => $couplings->getMaxScore(),
                'minScore' => $couplings->getMinScore(),
                'medianScore' => $couplings->getMedianScore(),
            ],
            'couplings' => $couplings->toArray(),
        ];

        $jsonData = json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);

        $this->writeFile($filename, $jsonData);
    }
}
