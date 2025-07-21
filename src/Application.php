<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis;

use Phauthentic\CognitiveCodeAnalysis\Business\Churn\ChangeCounter\ChangeCounterFactory;
use Phauthentic\CognitiveCodeAnalysis\Business\Churn\ChurnCalculator;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Baseline;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollector;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Events\FileProcessed;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Events\ParserFailed;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Events\SourceFilesFound;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Parser;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\ScoreCalculator;
use Phauthentic\CognitiveCodeAnalysis\Business\DirectoryScanner;
use Phauthentic\CognitiveCodeAnalysis\Business\MetricsFacade;
use Phauthentic\CognitiveCodeAnalysis\Command\ChurnCommand;
use Phauthentic\CognitiveCodeAnalysis\Command\CognitiveMetricsCommand;
use Phauthentic\CognitiveCodeAnalysis\Command\EventHandler\ParserErrorHandler;
use Phauthentic\CognitiveCodeAnalysis\Command\EventHandler\ProgressBarHandler;
use Phauthentic\CognitiveCodeAnalysis\Command\EventHandler\VerboseHandler;
use Phauthentic\CognitiveCodeAnalysis\Command\Handler\ChurnReportHandler;
use Phauthentic\CognitiveCodeAnalysis\Command\Handler\CognitiveMetricsReportHandler;
use Phauthentic\CognitiveCodeAnalysis\Command\Presentation\ChurnTextRenderer;
use Phauthentic\CognitiveCodeAnalysis\Command\Presentation\CognitiveMetricTextRenderer;
use Phauthentic\CognitiveCodeAnalysis\Command\Presentation\CognitiveMetricTextRendererInterface;
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
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;

/**
 *
 */
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

        $this->containerBuilder->register(ChurnTextRenderer::class, ChurnTextRenderer::class)
            ->setArguments([
                new Reference(OutputInterface::class)
            ])
            ->setPublic(true);

        $this->containerBuilder->register(CognitiveMetricTextRendererInterface::class, CognitiveMetricTextRenderer::class)
            ->setArguments([
                new Reference(OutputInterface::class),
                new Reference(ConfigService::class)
            ])
            ->setPublic(true);

        $this->containerBuilder->register(Baseline::class, Baseline::class)
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

        $this->containerBuilder->register(ChurnReportHandler::class, ChurnReportHandler::class)
            ->setArguments([
                new Reference(MetricsFacade::class),
                new Reference(OutputInterface::class),
            ])
            ->setPublic(true);

        $this->containerBuilder->register(CognitiveMetricsReportHandler::class, CognitiveMetricsReportHandler::class)
            ->setArguments([
                new Reference(MetricsFacade::class),
                new Reference(OutputInterface::class),
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
                new Reference(MessageBusInterface::class)
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
            ])
            ->setPublic(true);
    }

    private function registerCommands(): void
    {
        $this->containerBuilder->register(CognitiveMetricsCommand::class, CognitiveMetricsCommand::class)
            ->setArguments([
                new Reference(MetricsFacade::class),
                new Reference(CognitiveMetricTextRendererInterface::class),
                new Reference(Baseline::class),
                new Reference(CognitiveMetricsReportHandler::class),
            ])
            ->setPublic(true);

        $this->containerBuilder->register(ChurnCommand::class, ChurnCommand::class)
            ->setArguments([
                new Reference(MetricsFacade::class),
                new Reference(ChurnTextRenderer::class),
                new Reference(ChurnReportHandler::class),
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

    /**
     * @param ProgressBarHandler $progressbar
     * @param VerboseHandler $verbose
     * @return HandlersLocator
     */
    private function setUpEventHandlersLocator(ProgressBarHandler $progressbar, VerboseHandler $verbose): HandlersLocator
    {
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
