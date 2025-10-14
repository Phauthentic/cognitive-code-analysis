<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Handler\CognitiveAnalysis;

use Phauthentic\CognitiveCodeAnalysis\Business\CodeCoverage\CodeCoverageFactory;
use Phauthentic\CognitiveCodeAnalysis\Command\CognitiveMetricsSpecifications\CognitiveMetricsCommandContext;
use Phauthentic\CognitiveCodeAnalysis\Command\Result\OperationResult;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;

/**
 * Handler for loading coverage files in cognitive metrics command.
 * Encapsulates coverage loading logic, format detection, and error handling.
 */
class CoverageLoadHandler
{
    public function __construct(
        private readonly CodeCoverageFactory $coverageFactory
    ) {
    }

    /**
     * Load coverage reader from the context.
     * Returns success result with reader if file is provided and loading succeeds.
     * Returns success result with null if no file is provided.
     * Returns failure result if loading fails.
     */
    public function load(CognitiveMetricsCommandContext $context): OperationResult
    {
        $coverageFile = $context->getCoverageFile();
        $format = $context->getCoverageFormat();

        if ($coverageFile === null) {
            return OperationResult::success(null);
        }

        // Auto-detect format if not specified
        if ($format === null) {
            $format = $this->detectCoverageFormat($coverageFile);
            if ($format === null) {
                return OperationResult::failure('Unable to detect coverage file format. Please specify format explicitly.');
            }
        }

        try {
            $reader = $this->coverageFactory->createFromName($format, $coverageFile);
            return OperationResult::success($reader);
        } catch (CognitiveAnalysisException $e) {
            return OperationResult::failure('Failed to load coverage file: ' . $e->getMessage());
        }
    }

    /**
     * Detect coverage file format by examining the XML structure.
     */
    private function detectCoverageFormat(string $coverageFile): ?string
    {
        $content = file_get_contents($coverageFile);
        if ($content === false) {
            return null;
        }

        // Cobertura format has <coverage> root element with line-rate attribute
        if (preg_match('/<coverage[^>]*line-rate=/', $content)) {
            return 'cobertura';
        }

        // Clover format has <coverage> with generated attribute and <project> child
        if (preg_match('/<coverage[^>]*generated=.*<project/', $content)) {
            return 'clover';
        }

        return null;
    }
}
