<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\ChurnStages;

use Exception;
use Phauthentic\CognitiveCodeAnalysis\Business\MetricsFacade;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\ChurnExecutionContext;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\ChurnPipelineStage;
use Phauthentic\CognitiveCodeAnalysis\Command\Result\OperationResult;

/**
 * Pipeline stage for loading configuration files for churn analysis.
 */
class ConfigurationStage implements ChurnPipelineStage
{
    public function __construct(
        private readonly MetricsFacade $metricsFacade
    ) {
    }

    public function execute(ChurnExecutionContext $context): OperationResult
    {
        $commandContext = $context->getCommandContext();

        if (!$commandContext->hasConfigFile()) {
            return OperationResult::success();
        }

        $configFile = $commandContext->getConfigFile();
        if ($configFile === null) {
            return OperationResult::success();
        }

        try {
            $this->metricsFacade->loadConfig($configFile);
            return OperationResult::success();
        } catch (Exception $e) {
            $context->getOutput()->writeln('<error>Failed to load configuration: ' . $e->getMessage() . '</error>');
            return OperationResult::failure('Failed to load configuration: ' . $e->getMessage());
        }
    }

    public function shouldSkip(ChurnExecutionContext $context): bool
    {
        $commandContext = $context->getCommandContext();
        return !$commandContext->hasConfigFile();
    }

    public function getStageName(): string
    {
        return 'Configuration';
    }
}
