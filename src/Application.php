<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics;

use Phauthentic\CodeQualityMetrics\Business\Cognitive\BaselineService;
use Phauthentic\CodeQualityMetrics\Business\Cognitive\CognitiveMetricsCollector;
use Phauthentic\CodeQualityMetrics\Business\Cognitive\ScoreCalculator;
use Phauthentic\CodeQualityMetrics\Business\DirectoryScanner;
use Phauthentic\CodeQualityMetrics\Business\Halstead\HalsteadMetricsCollector;
use Phauthentic\CodeQualityMetrics\Command\CognitiveMetricsCommand;
use Phauthentic\CodeQualityMetrics\Command\HalsteadMetricsCommand;
use Phauthentic\CodeQualityMetrics\Business\MetricsFacade;
use Phauthentic\CodeQualityMetrics\Command\Presentation\CognitiveMetricTextRenderer;
use Phauthentic\CodeQualityMetrics\Command\Presentation\HalsteadMetricTextRenderer;
use Phauthentic\CodeQualityMetrics\Config\ConfigLoader;
use Phauthentic\CodeQualityMetrics\Config\ConfigService;
use PhpParser\NodeTraverser;
use PhpParser\NodeTraverserInterface;
use PhpParser\ParserFactory;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 *
 */
class Application
{
    private ContainerBuilder $containerBuilder;

    public function __construct()
    {
        $this->containerBuilder = new ContainerBuilder();
        $this->bootstrap();
    }

    private function registerServices(): void
    {
        $this->containerBuilder->register(HalsteadMetricsCollector::class, HalsteadMetricsCollector::class)
            ->setPublic(true);

        $this->containerBuilder->register(CognitiveMetricsCollector::class, CognitiveMetricsCollector::class)
            ->setPublic(true);

        $this->containerBuilder->register(ScoreCalculator::class, ScoreCalculator::class)
            ->setPublic(true);

        $this->containerBuilder->register(ConfigService::class, ConfigService::class)
            ->setPublic(true);

        $this->containerBuilder->register(HalsteadMetricTextRenderer::class, HalsteadMetricTextRenderer::class)
            ->setPublic(true);

        $this->containerBuilder->register(CognitiveMetricTextRenderer::class, CognitiveMetricTextRenderer::class)
            ->setPublic(true);

        $this->containerBuilder->register(BaselineService::class, BaselineService::class)
            ->setPublic(true);

        $this->containerBuilder->register(Processor::class, Processor::class)
            ->setPublic(true);

        $this->containerBuilder->register(ConfigLoader::class, ConfigLoader::class)
            ->setPublic(true);

        $this->containerBuilder->register(ParserFactory::class, ParserFactory::class)
            ->setPublic(true);

        $this->containerBuilder->register(DirectoryScanner::class, DirectoryScanner::class)
            ->setPublic(true);

        $this->containerBuilder->register(NodeTraverserInterface::class, NodeTraverser::class)
            ->setPublic(true);
    }

    private function bootstrap(): void
    {
        $this->registerServices();
        $this->bootstrapMetricsCollectors();
        $this->configureConfigService();
        $this->registerMetricsFacade();
        $this->registerCommands();
        $this->configureApplication();
    }

    private function bootstrapMetricsCollectors(): void
    {
        $this->containerBuilder->register(CognitiveMetricsCollector::class, CognitiveMetricsCollector::class)
            ->setArguments([
                new Reference(ParserFactory::class),
                new Reference(NodeTraverserInterface::class),
                new Reference(DirectoryScanner::class),
            ])
            ->setPublic(true);

        $this->containerBuilder->register(HalsteadMetricsCollector::class, HalsteadMetricsCollector::class)
            ->setArguments([
                new Reference(ParserFactory::class),
                new Reference(NodeTraverserInterface::class),
                new Reference(DirectoryScanner::class),
            ])
            ->setPublic(true);
    }

    private function configureConfigService(): void
    {
        $this->containerBuilder->register(ConfigService::class, ConfigService::class)
            ->setArguments([
                new Reference(Processor::class),
                new Reference(ConfigLoader::class),
            ])
            ->setPublic(true);
    }

    private function registerMetricsFacade(): void
    {
        $this->containerBuilder->register(MetricsFacade::class, MetricsFacade::class)
            ->setArguments([
                new Reference(HalsteadMetricsCollector::class),
                new Reference(CognitiveMetricsCollector::class),
                new Reference(ScoreCalculator::class),
                new Reference(ConfigService::class),
            ])
            ->setPublic(true);
    }

    private function registerCommands(): void
    {
        $this->containerBuilder->register(CognitiveMetricsCommand::class, CognitiveMetricsCommand::class)
            ->setArguments([
                new Reference(MetricsFacade::class),
                new Reference(CognitiveMetricTextRenderer::class),
                new Reference(BaselineService::class),
            ])
            ->setPublic(true);

        $this->containerBuilder->register(HalsteadMetricsCommand::class, HalsteadMetricsCommand::class)
            ->setArguments([
                new Reference(MetricsFacade::class),
                new Reference(HalsteadMetricTextRenderer::class),
            ])
            ->setPublic(true);
    }

    private function configureApplication(): void
    {
        $this->containerBuilder->register(SymfonyApplication::class, SymfonyApplication::class)
            ->setPublic(true)
            ->addMethodCall('add', [new Reference(CognitiveMetricsCommand::class)])
            ->addMethodCall('add', [new Reference(HalsteadMetricsCommand::class)]);
    }

    public function run(): void
    {
        $application = $this->containerBuilder->get(SymfonyApplication::class);
        // @phpstan-ignore-next-line
        $application->run();
    }

    public function get(string $id): mixed
    {
        return $this->containerBuilder->get($id);
    }
}
