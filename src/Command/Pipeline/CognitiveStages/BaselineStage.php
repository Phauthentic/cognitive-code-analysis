<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\CognitiveStages;

use Exception;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Baseline\Baseline;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\ExecutionContext;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\PipelineStage;
use Phauthentic\CognitiveCodeAnalysis\Command\Result\OperationResult;
use Phauthentic\CognitiveCodeAnalysis\Config\ConfigService;

/**
 * Pipeline stage for applying baseline to metrics.
 * Encapsulates baseline loading and delta calculation logic.
 */
class BaselineStage extends PipelineStage
{
    public function __construct(
        private readonly Baseline $baselineService,
        private readonly ConfigService $configService
    ) {
    }

    public function execute(ExecutionContext $context): OperationResult
    {
        $commandContext = $context->getCommandContext();
        $metricsCollection = $context->getData('metricsCollection');

        if ($metricsCollection === null) {
            return OperationResult::failure('Metrics collection not available for baseline.');
        }

        $baselineFile = $commandContext->getBaselineFile();

        // If no baseline file provided, try to find the latest one automatically
        if ($baselineFile === null) {
            $baselineFile = $this->baselineService->findLatestBaselineFile();

            if ($baselineFile === null) {
                // No baseline file found, skip this stage
                return OperationResult::success();
            }

            // Add info message about auto-detected baseline
            $context->addWarning("Auto-detected latest baseline file: " . basename($baselineFile));
        }

        try {
            $config = $this->configService->getConfig();
            $result = $this->baselineService->loadBaselineWithValidation($baselineFile, $config);

            // Calculate deltas with the extracted metrics
            $warnings = $this->baselineService->calculateDeltas(
                $metricsCollection,
                $result['metrics']
            );

            // Add any validation warnings to the context
            if (!empty($result['warnings'])) {
                $context->addWarnings($result['warnings']);
            }

            return OperationResult::success();
        } catch (Exception $e) {
            return OperationResult::failure('Failed to process baseline: ' . $e->getMessage());
        }
    }

    public function shouldSkip(ExecutionContext $context): bool
    {
        // Never skip this stage - it will handle auto-detection internally
        return false;
    }

    public function getStageName(): string
    {
        return 'Baseline';
    }
}
