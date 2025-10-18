<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\CognitiveStages;

use Exception;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Baseline\BaselineFile;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\ExecutionContext;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\PipelineStage;
use Phauthentic\CognitiveCodeAnalysis\Command\Result\OperationResult;
use Phauthentic\CognitiveCodeAnalysis\Config\ConfigService;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;

/**
 * Pipeline stage for generating baseline files.
 * Creates baseline files with metadata including config hash and creation date.
 */
class BaselineGenerationStage extends PipelineStage
{
    public function __construct(
        private readonly ConfigService $configService
    ) {
    }

    public function execute(ExecutionContext $context): OperationResult
    {
        $commandContext = $context->getCommandContext();
        $metricsCollection = $context->getData('metricsCollection');

        if (!$commandContext->hasGenerateBaseline()) {
            return OperationResult::success();
        }

        if ($metricsCollection === null) {
            return OperationResult::failure('Metrics collection not available for baseline generation.');
        }

        try {
            $outputPath = $commandContext->getBaselineOutputPath();
            $config = $this->configService->getConfig();

            // Create BaselineFile object
            $baselineFile = BaselineFile::fromMetricsCollection($metricsCollection, $config);

            // Ensure directory exists
            $directory = dirname($outputPath);
            if (!is_dir($directory)) {
                if (!mkdir($directory, 0755, true)) {
                    throw new CognitiveAnalysisException("Failed to create directory: {$directory}");
                }
            }

            // Write baseline file
            $jsonData = json_encode($baselineFile, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
            if (file_put_contents($outputPath, $jsonData) === false) {
                throw new CognitiveAnalysisException("Failed to write baseline file: {$outputPath}");
            }

            // Add success message to context
            $context->setData('baselineGenerated', $outputPath);

            return OperationResult::success("Baseline file generated: {$outputPath}");
        } catch (Exception $e) {
            return OperationResult::failure('Failed to generate baseline: ' . $e->getMessage());
        }
    }

    public function shouldSkip(ExecutionContext $context): bool
    {
        $commandContext = $context->getCommandContext();
        return !$commandContext->hasGenerateBaseline();
    }

    public function getStageName(): string
    {
        return 'BaselineGeneration';
    }
}
