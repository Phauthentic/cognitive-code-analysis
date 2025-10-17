<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis;

use Phauthentic\CognitiveCodeAnalysis\Business\Churn\ChangeCounter\ChangeCounterFactory;
use Phauthentic\CognitiveCodeAnalysis\Business\Churn\ChurnCalculator;
use Phauthentic\CognitiveCodeAnalysis\Business\Churn\Report\ChurnReportFactory;
use Phauthentic\CognitiveCodeAnalysis\Business\Churn\Report\ChurnReportFactoryInterface;
use Phauthentic\CognitiveCodeAnalysis\Business\CodeCoverage\CodeCoverageFactory;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Baseline;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollector;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsSorter;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Events\FileProcessed;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Events\ParserFailed;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Events\SourceFilesFound;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Parser;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Report\CognitiveReportFactory;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Report\CognitiveReportFactoryInterface;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\ScoreCalculator;
use Phauthentic\CognitiveCodeAnalysis\Business\MetricsFacade;
use Phauthentic\CognitiveCodeAnalysis\Business\Utility\DirectoryScanner;
use Phauthentic\CognitiveCodeAnalysis\Cache\FileCache;
use Phauthentic\CognitiveCodeAnalysis\Command\ChurnCommand;
use Phauthentic\CognitiveCodeAnalysis\Command\ChurnSpecifications\ChurnValidationSpecificationFactory;
use Phauthentic\CognitiveCodeAnalysis\Command\CognitiveMetricsCommand;
use Phauthentic\CognitiveCodeAnalysis\Command\CognitiveMetricsSpecifications\CognitiveMetricsValidationSpecificationFactory;
use Phauthentic\CognitiveCodeAnalysis\Command\CognitiveMetricsSpecifications\CompositeCognitiveMetricsValidationSpecification;
use Phauthentic\CognitiveCodeAnalysis\Command\EventHandler\ParserErrorHandler;
use Phauthentic\CognitiveCodeAnalysis\Command\EventHandler\ProgressBarHandler;
use Phauthentic\CognitiveCodeAnalysis\Command\EventHandler\VerboseHandler;
use Phauthentic\CognitiveCodeAnalysis\Command\Handler\ChurnReportHandler;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\CommandPipelineFactory;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\Stages\BaselineStage;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\Stages\ConfigurationStage;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\Stages\CoverageStage;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\Stages\MetricsCollectionStage;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\Stages\OutputStage;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\Stages\ReportGenerationStage;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\Stages\SortingStage;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\Stages\ValidationStage;
use Phauthentic\CognitiveCodeAnalysis\Command\Presentation\ChurnTextRenderer;
use Phauthentic\CognitiveCodeAnalysis\Command\Presentation\CognitiveMetricTextRenderer;
use Phauthentic\CognitiveCodeAnalysis\Command\Presentation\CognitiveMetricTextRendererInterface;
use Phauthentic\CognitiveCodeAnalysis\Config\ConfigLoader;
use Phauthentic\CognitiveCodeAnalysis\Config\ConfigService;
use PhpParser\NodeTraverser;
use PhpParser\NodeTraverserInterface;
use PhpParser\ParserFactory;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;

class Application
{
    public const VERSION = '1.3.1';

    private ContainerBuilder $containerBuilder;

    public function __construct()
    {
        $this->containerBuilder = new ContainerBuilder();
        $this->bootstrap();
    }

    private function registerServices(): void
    {
        $this->registerCoreServices();
        $this->registerReportFactories();
        $this->registerPresentationServices();
        $this->registerUtilityServices();
        $this->registerCommandHandlers();
    }

    private function registerCoreServices(): void
    {
        $outputClass = getenv('APP_ENV') === 'test' ? NullOutput::class : ConsoleOutput::class;

        $this->containerBuilder->register(OutputInterface::class, $outputClass)
            ->setPublic(true);

        $this->containerBuilder->register(ChangeCounterFactory::class, ChangeCounterFactory::class)
            ->setPublic(true);

        $this->containerBuilder->register(ChurnCalculator::class, ChurnCalculator::class)
            ->setPublic(true);

        $this->containerBuilder->register(CognitiveMetricsCollector::class, CognitiveMetricsCollector::class)
            ->setPublic(true);

        $this->containerBuilder->register(ScoreCalculator::class, ScoreCalculator::class)
            ->setPublic(true);

        $this->containerBuilder->register(ConfigService::class, ConfigService::class)
            ->setPublic(true);

        $this->containerBuilder->register(CacheItemPoolInterface::class, FileCache::class)
            ->setArguments([
                './.phpcca.cache' // Default cache directory, can be overridden by config
            ])
            ->setPublic(true);

        $this->containerBuilder->register(Baseline::class, Baseline::class)
            ->setPublic(true);

        $this->containerBuilder->register(CognitiveMetricsSorter::class, CognitiveMetricsSorter::class)
            ->setPublic(true);

        $this->containerBuilder->register(CodeCoverageFactory::class, CodeCoverageFactory::class)
            ->setPublic(true);

        $this->containerBuilder->register(CognitiveMetricsValidationSpecificationFactory::class, CognitiveMetricsValidationSpecificationFactory::class)
            ->setPublic(true);

        $this->containerBuilder->register(CompositeCognitiveMetricsValidationSpecification::class, CompositeCognitiveMetricsValidationSpecification::class)
            ->setFactory([new Reference(CognitiveMetricsValidationSpecificationFactory::class), 'create'])
            ->setPublic(true);

        $this->containerBuilder->register(ChurnValidationSpecificationFactory::class, ChurnValidationSpecificationFactory::class)
            ->setPublic(true);
    }

    private function registerReportFactories(): void
    {
        $this->containerBuilder->register(ChurnReportFactoryInterface::class, ChurnReportFactory::class)
            ->setArguments([
                new Reference(ConfigService::class),
            ])
            ->setPublic(true);

        $this->containerBuilder->register(CognitiveReportFactoryInterface::class, CognitiveReportFactory::class)
            ->setArguments([
                new Reference(ConfigService::class),
            ])
            ->setPublic(true);
    }

    private function registerPresentationServices(): void
    {
        $this->containerBuilder->register(ChurnTextRenderer::class, ChurnTextRenderer::class)
            ->setArguments([
                new Reference(OutputInterface::class)
            ])
            ->setPublic(true);

        $this->containerBuilder->register(CognitiveMetricTextRendererInterface::class, CognitiveMetricTextRenderer::class)
            ->setArguments([
                new Reference(ConfigService::class)
            ])
            ->setPublic(true);
    }

    private function registerUtilityServices(): void
    {
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

        $outputClass = getenv('APP_ENV') === 'test' ? NullOutput::class : ConsoleOutput::class;
        $this->containerBuilder->register(OutputInterface::class, $outputClass)
            ->setPublic(true);

        $this->containerBuilder->register(InputInterface::class, ArgvInput::class)
            ->setPublic(true);

        $this->containerBuilder->register(Parser::class, Parser::class)
            ->setArguments([
                new Reference(ParserFactory::class),
                new Reference(NodeTraverserInterface::class),
            ])
            ->setPublic(true);
    }

    private function registerCommandHandlers(): void
    {
        $this->containerBuilder->register(ChurnReportHandler::class, ChurnReportHandler::class)
            ->setArguments([
                new Reference(MetricsFacade::class),
                new Reference(OutputInterface::class),
                new Reference(ChurnReportFactoryInterface::class),
            ])
            ->setPublic(true);
    }

    private function bootstrap(): void
    {
        $this->registerServices();
        $this->configureEventBus();
        $this->bootstrapMetricsCollectors();
        $this->configureConfigService();
        $this->registerMetricsFacade();
        $this->registerPipelineStages();
        $this->registerCommands();
        $this->configureApplication();
    }

    private function bootstrapMetricsCollectors(): void
    {
        $this->containerBuilder->register(CognitiveMetricsCollector::class, CognitiveMetricsCollector::class)
            ->setArguments([
                new Reference(Parser::class),
                new Reference(DirectoryScanner::class),
                new Reference(ConfigService::class),
                new Reference(MessageBusInterface::class),
                new Reference(CacheItemPoolInterface::class)
            ])
            ->setPublic(true);
    }

    private function configureEventBus(): void
    {
        $progressbar = new ProgressBarHandler(
            $this->get(OutputInterface::class)
        );

        $verbose = new VerboseHandler(
            $this->get(InputInterface::class),
            $this->get(OutputInterface::class)
        );

        $handlersLocator = $this->setUpEventHandlersLocator($progressbar, $verbose);

        $messageBus = new MessageBus([
            new HandleMessageMiddleware($handlersLocator),
        ]);

        $this->containerBuilder->set(MessageBusInterface::class, $messageBus);
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
                new Reference(ChurnCalculator::class),
                new Reference(ChangeCounterFactory::class),
                new Reference(ChurnReportFactoryInterface::class),
                new Reference(CognitiveReportFactoryInterface::class),
            ])
            ->setPublic(true);
    }

    private function registerPipelineStages(): void
    {
        // Register pipeline stages
        $this->containerBuilder->register(ValidationStage::class, ValidationStage::class)
            ->setArguments([
                new Reference(CompositeCognitiveMetricsValidationSpecification::class),
            ])
            ->setPublic(true);

        $this->containerBuilder->register(ConfigurationStage::class, ConfigurationStage::class)
            ->setArguments([
                new Reference(MetricsFacade::class),
            ])
            ->setPublic(true);

        $this->containerBuilder->register(CoverageStage::class, CoverageStage::class)
            ->setArguments([
                new Reference(CodeCoverageFactory::class),
            ])
            ->setPublic(true);

        $this->containerBuilder->register(MetricsCollectionStage::class, MetricsCollectionStage::class)
            ->setArguments([
                new Reference(MetricsFacade::class),
            ])
            ->setPublic(true);

        $this->containerBuilder->register(BaselineStage::class, BaselineStage::class)
            ->setArguments([
                new Reference(Baseline::class),
            ])
            ->setPublic(true);

        $this->containerBuilder->register(SortingStage::class, SortingStage::class)
            ->setArguments([
                new Reference(CognitiveMetricsSorter::class),
            ])
            ->setPublic(true);

        $this->containerBuilder->register(ReportGenerationStage::class, ReportGenerationStage::class)
            ->setArguments([
                new Reference(MetricsFacade::class),
                new Reference(CognitiveReportFactoryInterface::class),
            ])
            ->setPublic(true);

        $this->containerBuilder->register(OutputStage::class, OutputStage::class)
            ->setArguments([
                new Reference(CognitiveMetricTextRendererInterface::class),
            ])
            ->setPublic(true);

        $this->containerBuilder->register(CommandPipelineFactory::class, CommandPipelineFactory::class)
            ->setArguments([
                new Reference(ValidationStage::class),
                new Reference(ConfigurationStage::class),
                new Reference(CoverageStage::class),
                new Reference(MetricsCollectionStage::class),
                new Reference(BaselineStage::class),
                new Reference(SortingStage::class),
                new Reference(ReportGenerationStage::class),
                new Reference(OutputStage::class),
            ])
            ->setPublic(true);
    }

    private function registerCommands(): void
    {
        $this->containerBuilder->register(CognitiveMetricsCommand::class, CognitiveMetricsCommand::class)
            ->setArguments([
                new Reference(CommandPipelineFactory::class),
            ])
            ->setPublic(true);

        $this->containerBuilder->register(ChurnCommand::class, ChurnCommand::class)
            ->setArguments([
                new Reference(MetricsFacade::class),
                new Reference(ChurnTextRenderer::class),
                new Reference(ChurnReportHandler::class),
                new Reference(ChurnValidationSpecificationFactory::class),
            ])
            ->setPublic(true);
    }

    private function configureApplication(): void
    {
        $this->containerBuilder->register(SymfonyApplication::class, SymfonyApplication::class)
            ->setArguments([
                'Cognitive Code Analysis',
                self::VERSION
            ])
            ->setPublic(true)
            ->addMethodCall('add', [new Reference(CognitiveMetricsCommand::class)])
            ->addMethodCall('add', [new Reference(ChurnCommand::class)]);
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

    public function get(string $identifier): mixed
    {
        return $this->containerBuilder->get($identifier);
    }

    public function getContainer(): ContainerBuilder
    {
        return $this->containerBuilder;
    }

    private function setUpEventHandlersLocator(
        ProgressBarHandler $progressbar,
        VerboseHandler $verbose
    ): HandlersLocator {
        return new HandlersLocator([
            SourceFilesFound::class => [
                $progressbar,
                $verbose
            ],
            FileProcessed::class => [
                $progressbar,
                $verbose
            ],
            ParserFailed::class => [
                new ParserErrorHandler($this->get(OutputInterface::class))
            ],
        ]);
    }
}
