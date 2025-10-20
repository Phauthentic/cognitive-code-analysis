<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\CognitiveStages;

use Phauthentic\CognitiveCodeAnalysis\Business\CodeCoverage\CodeCoverageFactory;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\ExecutionContext;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\PipelineStage;
use Phauthentic\CognitiveCodeAnalysis\Command\Result\OperationResult;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;

/**
 * Pipeline stage for loading coverage files.
 * Encapsulates coverage loading logic, format detection, and error handling.
 */
class CoverageStage extends PipelineStage
{
    public function __construct(
        private readonly CodeCoverageFactory $coverageFactory
    ) {
    }

    public function execute(ExecutionContext $context): OperationResult
    {
        $commandContext = $context->getCommandContext();
        $coverageFile = $commandContext->getCoverageFile();
        $format = $commandContext->getCoverageFormat();

        if ($coverageFile === null) {
            return OperationResult::success();
        }

        // Auto-detect format if not specified
        if ($format === null) {
            if (!file_exists($coverageFile)) {
                return OperationResult::failure('Coverage file not found: ' . $coverageFile);
            }

            $format = $this->detectCoverageFormat($coverageFile);
            if ($format === null) {
                return OperationResult::failure('Unable to detect coverage file format. Please specify format explicitly.');
            }
        }

        try {
            $reader = $this->coverageFactory->createFromName($format, $coverageFile);
            $context->setData('coverageReader', $reader);
            return OperationResult::success();
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

    public function shouldSkip(ExecutionContext $context): bool
    {
        $commandContext = $context->getCommandContext();
        return $commandContext->getCoverageFile() === null;
    }

    public function getStageName(): string
    {
        return 'Coverage';
    }
}
