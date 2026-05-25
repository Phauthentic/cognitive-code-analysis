<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\ChurnStages;

use Exception;
use Phauthentic\CognitiveCodeAnalysis\Business\MetricsFacade;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\ChurnExecutionContext;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\ChurnPipelineStage;
use Phauthentic\CognitiveCodeAnalysis\Command\Presentation\RuntimeStatusRenderer;
use Phauthentic\CognitiveCodeAnalysis\Command\Result\OperationResult;

/**
 * Pipeline stage for loading configuration files for churn analysis.
 */
class ConfigurationStage implements ChurnPipelineStage
{
    public function __construct(
        private readonly MetricsFacade $metricsFacade,
        private readonly RuntimeStatusRenderer $runtimeStatusRenderer,
    ) {
    }

    public function execute(ChurnExecutionContext $context): OperationResult
    {
        $commandContext = $context->getCommandContext();
        $configFile = $commandContext->getConfigFile();

        if ($configFile !== null) {
            try {
                $this->metricsFacade->loadConfig($configFile);
            } catch (Exception $e) {
                $context->getOutput()->writeln('<error>Failed to load configuration: ' . $e->getMessage() . '</error>');
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

    public function shouldSkip(ChurnExecutionContext $context): bool
    {
        return false;
    }

    public function getStageName(): string
    {
        return 'Configuration';
    }
}
