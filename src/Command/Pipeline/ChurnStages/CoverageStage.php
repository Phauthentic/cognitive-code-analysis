<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\ChurnStages;

use Phauthentic\CognitiveCodeAnalysis\Business\CodeCoverage\CloverReader;
use Phauthentic\CognitiveCodeAnalysis\Business\CodeCoverage\CoberturaReader;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\ChurnExecutionContext;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\ChurnPipelineStage;
use Phauthentic\CognitiveCodeAnalysis\Command\Result\OperationResult;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;

/**
 * Pipeline stage for loading coverage files for churn analysis.
 */
class CoverageStage implements ChurnPipelineStage
{
    public function execute(ChurnExecutionContext $context): OperationResult
    {
        $commandContext = $context->getCommandContext();
        $coverageFile = $commandContext->getCoverageFile();
        $format = $commandContext->getCoverageFormat();

        if ($coverageFile === null) {
            // Store null in context to indicate no coverage
            $context->setData('coverageReader', null);
            return OperationResult::success();
        }

        // Auto-detect format if not specified
        if ($format === null) {
            $format = $this->detectCoverageFormat($coverageFile);
            if ($format === null) {
                $context->getOutput()->writeln('<error>Unable to detect coverage file format. Please specify format explicitly.</error>');
                return OperationResult::failure('Unable to detect coverage file format.');
            }
        }

        try {
            $reader = match ($format) {
                'cobertura' => new CoberturaReader($coverageFile),
                'clover' => new CloverReader($coverageFile),
                default => throw new CognitiveAnalysisException("Unsupported coverage format: {$format}"),
            };

            $context->setData('coverageReader', $reader);
            return OperationResult::success();
        } catch (CognitiveAnalysisException $e) {
            $context->getOutput()->writeln(sprintf(
                '<error>Failed to load coverage file: %s</error>',
                $e->getMessage()
            ));
            return OperationResult::failure('Failed to load coverage file: ' . $e->getMessage());
        }
    }

    public function getStageName(): string
    {
        return 'Coverage';
    }

    public function shouldSkip(ChurnExecutionContext $context): bool
    {
        return false; // Coverage stage should never be skipped (it handles null coverage gracefully)
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
