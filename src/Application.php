<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\BaselineService;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollector;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\ScoreCalculator;
use Phauthentic\CognitiveCodeAnalysis\Business\DirectoryScanner;
use Phauthentic\CognitiveCodeAnalysis\Command\Cognitive\CognitiveCollectorShellOutputPlugin;
use Phauthentic\CognitiveCodeAnalysis\Command\CognitiveMetricsCommand;
use Phauthentic\CognitiveCodeAnalysis\Business\MetricsFacade;
use Phauthentic\CognitiveCodeAnalysis\Command\Presentation\CognitiveMetricTextRenderer;
use Phauthentic\CognitiveCodeAnalysis\Config\ConfigLoader;
use Phauthentic\CognitiveCodeAnalysis\Config\ConfigService;
use PhpParser\NodeTraverser;
use PhpParser\NodeTraverserInterface;
use PhpParser\ParserFactory;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
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
        $this->containerBuilder->register(CognitiveMetricsCollector::class, CognitiveMetricsCollector::class)
            ->setPublic(true);

        $this->containerBuilder->register(ScoreCalculator::class, ScoreCalculator::class)
            ->setPublic(true);

        $this->containerBuilder->register(ConfigService::class, ConfigService::class)
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

        $this->containerBuilder->register(NodeTraverserInterface::class, NodeTraverser::class)
            ->setPublic(true);

        $this->containerBuilder->register(OutputInterface::class, ConsoleOutput::class)
            ->setPublic(true);

        $this->containerBuilder->register(InputInterface::class, ArgvInput::class)
            ->setPublic(true);

        $this->containerBuilder->register(CognitiveCollectorShellOutputPlugin::class, CognitiveCollectorShellOutputPlugin::class)
            ->setArguments([
                new Reference(InputInterface::class),
                new Reference(OutputInterface::class)
            ])
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
                new Reference(ConfigService::class),
                [
                    $this->containerBuilder->get(CognitiveCollectorShellOutputPlugin::class),
                ]
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
    }

    private function configureApplication(): void
    {
        $this->containerBuilder->register(SymfonyApplication::class, SymfonyApplication::class)
            ->setPublic(true)
            ->addMethodCall('add', [new Reference(CognitiveMetricsCommand::class)]);
    }

    public function run(): void
    {
        $application = $this->containerBuilder->get(SymfonyApplication::class);
        // @phpstan-ignore-next-line
        $application->run(
            $this->containerBuilder->get(InputInterface::class),
            $this->containerBuilder->get(OutputInterface::class)
        );
    }

    public function get(string $id): mixed
    {
        return $this->containerBuilder->get($id);
    }
}
