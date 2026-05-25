<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\CognitiveStages;

use Exception;
use Phauthentic\CognitiveCodeAnalysis\Business\MetricsFacade;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\ExecutionContext;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\PipelineStage;
use Phauthentic\CognitiveCodeAnalysis\Command\Presentation\RuntimeStatusRenderer;
use Phauthentic\CognitiveCodeAnalysis\Command\Result\OperationResult;

/**
 * Pipeline stage for loading configuration files.
 * Encapsulates configuration loading logic and error handling.
 */
class ConfigurationStage extends PipelineStage
{
    public function __construct(
        private readonly MetricsFacade $metricsFacade,
        private readonly RuntimeStatusRenderer $runtimeStatusRenderer,
    ) {
    }

    public function execute(ExecutionContext $context): OperationResult
    {
        $commandContext = $context->getCommandContext();
        $configFile = $commandContext->getConfigFile();

        if ($configFile !== null) {
            try {
                $this->metricsFacade->loadConfig($configFile);
            } catch (Exception $e) {
                return OperationResult::failure('Failed to load configuration: ' . $e->getMessage());
            }
        }

        $this->runtimeStatusRenderer->render(
            $context->getOutput(),
            $configFile,
            $this->metricsFacade->getConfig()
        );

        return OperationResult::success();
    }

    public function shouldSkip(ExecutionContext $context): bool
    {
        return false;
    }

    public function getStageName(): string
    {
        return 'Configuration';
    }
}
