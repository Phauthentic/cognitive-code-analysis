<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\Stages;

use Exception;
use Phauthentic\CognitiveCodeAnalysis\Business\MetricsFacade;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\ExecutionContext;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\PipelineStage;
use Phauthentic\CognitiveCodeAnalysis\Command\Result\OperationResult;

/**
 * Pipeline stage for loading configuration files.
 * Encapsulates configuration loading logic and error handling.
 */
class ConfigurationStage extends PipelineStage
{
    public function __construct(
        private readonly MetricsFacade $metricsFacade
    ) {
    }

    public function execute(ExecutionContext $context): OperationResult
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
            return OperationResult::failure('Failed to load configuration: ' . $e->getMessage());
        }
    }

    public function getStageName(): string
    {
        return 'Configuration';
    }
}
