<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command;

use Phauthentic\CognitiveCodeAnalysis\Business\SemanticCoupling\SemanticCouplingCalculator;
use Phauthentic\CognitiveCodeAnalysis\Business\SemanticCoupling\TermExtractor;
use Phauthentic\CognitiveCodeAnalysis\Command\Handler\SemanticCouplingReportHandler;
use Phauthentic\CognitiveCodeAnalysis\Command\Presentation\SemanticCouplingTextRenderer;
use Phauthentic\CognitiveCodeAnalysis\Command\SemanticAnalysisSpecifications\SemanticAnalysisCommandContext;
use Phauthentic\CognitiveCodeAnalysis\Command\SemanticAnalysisSpecifications\CompositeSemanticAnalysisValidationSpecification;
use Phauthentic\CognitiveCodeAnalysis\Command\SemanticAnalysisSpecifications\SemanticAnalysisValidationSpecificationFactory;
use Phauthentic\CognitiveCodeAnalysis\PhpParser\TermExtractionVisitor;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Command to analyze semantic coupling between code entities.
 */
#[AsCommand(
    name: 'semantic-analysis',
    description: 'Analyze semantic coupling between files, classes, or modules using TF-IDF analysis.'
)]
class SemanticAnalysisCommand extends Command
{
    private const ARGUMENT_PATH = 'path';
    public const OPTION_GRANULARITY = 'granularity';
    public const OPTION_THRESHOLD = 'threshold';
    public const OPTION_LIMIT = 'limit';
    public const OPTION_VIEW = 'view';
    public const OPTION_REPORT_TYPE = 'report-type';
    public const OPTION_REPORT_FILE = 'report-file';
    public const OPTION_CONFIG = 'config';
    public const OPTION_DEBUG = 'debug';

    private CompositeSemanticAnalysisValidationSpecification $specification;

    public function __construct(
        readonly private SemanticCouplingTextRenderer $renderer,
        readonly private SemanticCouplingReportHandler $reportHandler,
        readonly private SemanticAnalysisValidationSpecificationFactory $specificationFactory
    ) {
        parent::__construct();
        $this->specification = $this->specificationFactory->create();
    }

    /**
     * Configures the command options and arguments.
     */
    protected function configure(): void
    {
        $this
            ->addArgument(
                name: self::ARGUMENT_PATH,
                mode: InputArgument::REQUIRED,
                description: 'Path to PHP files or directories to analyze.'
            )
            ->addOption(
                name: self::OPTION_GRANULARITY,
                shortcut: 'g',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Analysis granularity: file, class, or module',
                default: 'file'
            )
            ->addOption(
                name: self::OPTION_THRESHOLD,
                shortcut: 't',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Minimum coupling score threshold (0.0-1.0)',
            )
            ->addOption(
                name: self::OPTION_LIMIT,
                shortcut: 'l',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Maximum number of results to display',
                default: 20
            )
            ->addOption(
                name: self::OPTION_VIEW,
                mode: InputOption::VALUE_REQUIRED,
                description: 'View type: top-pairs, matrix, per-entity, or summary',
                default: 'top-pairs'
            )
            ->addOption(
                name: self::OPTION_REPORT_TYPE,
                shortcut: 'r',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Type of report to generate (json, csv, html, html-heatmap, interactive-treemap, interactive-tree)',
            )
            ->addOption(
                name: self::OPTION_REPORT_FILE,
                shortcut: 'f',
                mode: InputOption::VALUE_REQUIRED,
                description: 'File to write the report to',
            )
            ->addOption(
                name: self::OPTION_CONFIG,
                shortcut: 'c',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Path to a configuration file',
            )
            ->addOption(
                name: self::OPTION_DEBUG,
                mode: InputOption::VALUE_NONE,
                description: 'Enable debug output',
            );
    }

    /**
     * Executes the command.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $context = new SemanticAnalysisCommandContext($input);

        // Validate all specifications
        if (!$this->specification->isSatisfiedBy($context)) {
            $errorMessage = $this->specification->getDetailedErrorMessage($context);
            $output->writeln('<error>' . $errorMessage . '</error>');
            return Command::FAILURE;
        }

        try {
            // Calculate semantic coupling
            $couplings = $this->calculateSemanticCoupling($context);

            // Apply threshold filter if specified
            if ($context->getThreshold() !== null) {
                $couplings = $couplings->filterByThreshold($context->getThreshold());
            }

            // Handle report generation or display
            if ($context->hasReportOptions()) {
                $reportType = $context->getReportType();
                $reportFile = $context->getReportFile() ?? $this->generateDefaultReportFilename($reportType);
                
                return $this->reportHandler->exportToFile($couplings, $reportType, $reportFile);
            }

            // Display results in console
            $this->renderer->render(
                $couplings,
                $context->getViewType(),
                $context->getLimit()
            );

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $output->writeln('<error>Error during analysis: ' . $e->getMessage() . '</error>');
            
            if ($context->isDebug()) {
                $output->writeln('<error>Stack trace:</error>');
                $output->writeln('<error>' . $e->getTraceAsString() . '</error>');
            }
            
            return Command::FAILURE;
        }
    }

    /**
     * Calculate semantic coupling for the given context.
     */
    private function calculateSemanticCoupling(SemanticAnalysisCommandContext $context): \Phauthentic\CognitiveCodeAnalysis\Business\SemanticCoupling\SemanticCouplingCollection
    {
        $path = $context->getPath();
        $granularity = $context->getGranularity();

        // Find PHP files
        $phpFiles = $this->findPhpFiles($path);
        
        if (empty($phpFiles)) {
            throw new \RuntimeException('No PHP files found in the specified path.');
        }

        // Extract identifiers from each file
        $entityIdentifiers = $this->extractIdentifiersFromFiles($phpFiles, $granularity);

        if (empty($entityIdentifiers)) {
            throw new \RuntimeException('No identifiers found in the PHP files.');
        }

        // Calculate semantic coupling
        $termExtractor = new TermExtractor();
        $tfIdfCalculator = new \Phauthentic\CognitiveCodeAnalysis\Business\SemanticCoupling\TfIdfCalculator();
        $calculator = new SemanticCouplingCalculator($termExtractor, $tfIdfCalculator);

        return $calculator->calculate($entityIdentifiers, $granularity);
    }

    /**
     * Find PHP files in the given path.
     *
     * @return array<string>
     */
    private function findPhpFiles(string $path): array
    {
        $files = [];

        if (is_file($path) && pathinfo($path, PATHINFO_EXTENSION) === 'php') {
            return [$path];
        }

        if (is_dir($path)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }

    /**
     * Extract identifiers from PHP files based on granularity.
     *
     * @param array<string> $phpFiles
     * @return array<string, array<string>>
     */
    private function extractIdentifiersFromFiles(array $phpFiles, string $granularity): array
    {
        $entityIdentifiers = [];
        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        $traverser = new NodeTraverser();

        foreach ($phpFiles as $file) {
            try {
                $ast = $parser->parse(file_get_contents($file));
                if ($ast === null) {
                    continue;
                }

                $visitor = new TermExtractionVisitor();
                $visitor->setCurrentFile($file);
                $traverser->addVisitor($visitor);
                $traverser->traverse($ast);

                switch ($granularity) {
                    case 'file':
                        $entityIdentifiers[$file] = $visitor->getIdentifiers();
                        break;
                    case 'class':
                        $classIdentifiers = $visitor->getClassIdentifiers();
                        foreach ($classIdentifiers as $class => $identifiers) {
                            $entityIdentifiers[$class] = $identifiers;
                        }
                        break;
                    case 'module':
                        $module = $this->extractModuleFromPath($file);
                        if (!isset($entityIdentifiers[$module])) {
                            $entityIdentifiers[$module] = [];
                        }
                        $entityIdentifiers[$module] = array_merge(
                            $entityIdentifiers[$module],
                            $visitor->getIdentifiers()
                        );
                        break;
                }

                $traverser->removeVisitor($visitor);
            } catch (\Exception $e) {
                // Skip files that can't be parsed
                continue;
            }
        }

        return $entityIdentifiers;
    }

    /**
     * Extract module name from file path.
     */
    private function extractModuleFromPath(string $filePath): string
    {
        $pathParts = explode(DIRECTORY_SEPARATOR, dirname($filePath));
        return end($pathParts) ?: 'root';
    }

    /**
     * Generate default report filename based on type.
     */
    private function generateDefaultReportFilename(string $reportType): string
    {
        $timestamp = date('Y-m-d_H-i-s');
        $extensions = [
            'json' => 'json',
            'csv' => 'csv',
            'html' => 'html',
            'html-heatmap' => 'html',
        ];

        $extension = $extensions[$reportType] ?? 'txt';
        return "semantic-coupling-{$timestamp}.{$extension}";
    }
}
