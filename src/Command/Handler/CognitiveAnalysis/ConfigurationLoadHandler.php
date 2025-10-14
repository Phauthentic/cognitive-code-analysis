<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Handler\CognitiveAnalysis;

use Exception;
use Phauthentic\CognitiveCodeAnalysis\Business\MetricsFacade;
use Phauthentic\CognitiveCodeAnalysis\Command\CognitiveMetricsSpecifications\CognitiveMetricsCommandContext;
use Phauthentic\CognitiveCodeAnalysis\Command\Result\OperationResult;

/**
 * Handler for loading configuration files in cognitive metrics command.
 * Encapsulates configuration loading logic and error handling.
 */
class ConfigurationLoadHandler
{
    public function __construct(
        private readonly MetricsFacade $metricsFacade
    ) {
    }

    /**
     * Load configuration from the context.
     * Returns success result if no config file is provided or loading succeeds.
     * Returns failure result if loading fails.
     */
    public function load(CognitiveMetricsCommandContext $context): OperationResult
    {
        if (!$context->hasConfigFile()) {
            return OperationResult::success();
        }

        $configFile = $context->getConfigFile();
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
}
