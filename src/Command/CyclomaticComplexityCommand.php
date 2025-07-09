<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command;

use Exception;
use PhpParser\Node;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use Phauthentic\CognitiveCodeAnalysis\PhpParser\CyclomaticComplexityVisitor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

/**
 * Command to calculate cyclomatic complexity for PHP files.
 */
#[AsCommand(
    name: 'complexity',
    description: 'Calculate cyclomatic complexity for PHP classes and methods.'
)]
class CyclomaticComplexityCommand extends Command
{
    private const ARGUMENT_PATH = 'path';
    private const OPTION_OUTPUT_FORMAT = 'format';
    private const OPTION_OUTPUT_FILE = 'output';
    private const OPTION_THRESHOLD = 'threshold';
    private const OPTION_DETAILED = 'detailed';

    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                name: self::ARGUMENT_PATH,
                mode: InputArgument::REQUIRED,
                description: 'Path to PHP files or directories to analyze.'
            )
            ->addOption(
                name: self::OPTION_OUTPUT_FORMAT,
                shortcut: 'f',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Output format (text, json, csv)',
                default: 'text'
            )
            ->addOption(
                name: self::OPTION_OUTPUT_FILE,
                shortcut: 'o',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Output file path'
            )
            ->addOption(
                name: self::OPTION_THRESHOLD,
                shortcut: 't',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Minimum complexity threshold to report (default: 1)',
                default: 1
            )
            ->addOption(
                name: self::OPTION_DETAILED,
                shortcut: 'd',
                mode: InputOption::VALUE_NONE,
                description: 'Show detailed breakdown of complexity factors'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $input->getArgument(self::ARGUMENT_PATH);
        $format = $input->getOption(self::OPTION_OUTPUT_FORMAT);
        $outputFile = $input->getOption(self::OPTION_OUTPUT_FILE);
        $threshold = (int) $input->getOption(self::OPTION_THRESHOLD);
        $detailed = $input->getOption(self::OPTION_DETAILED);

        try {
            $complexityData = $this->analyzeComplexity($path, $threshold);

            if ($outputFile) {
                $this->writeToFile($complexityData, $outputFile, $format);
                $output->writeln("<info>Complexity analysis written to: {$outputFile}</info>");
            } else {
                $this->renderOutput($complexityData, $output, $format, $detailed);
            }

            return Command::SUCCESS;
        } catch (Exception $e) {
            $output->writeln("<error>Error analyzing complexity: {$e->getMessage()}</error>");
            return Command::FAILURE;
        }
    }

    /**
     * Analyze cyclomatic complexity for the given path.
     *
     * @param string $path Path to analyze
     * @param int $threshold Minimum complexity threshold
     * @return array Complexity analysis data
     * @throws Exception
     */
    private function analyzeComplexity(string $path, int $threshold): array
    {
        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        $traverser = new NodeTraverser();
        $visitor = new CyclomaticComplexityVisitor();
        $traverser->addVisitor($visitor);

        $phpFiles = $this->findPhpFiles($path);

        if (empty($phpFiles)) {
            throw new Exception("No PHP files found in path: {$path}");
        }

        foreach ($phpFiles as $file) {
            try {
                $code = file_get_contents($file);
                if ($code === false) {
                    continue;
                }

                $ast = $parser->parse($code);
                if ($ast !== null) {
                    $traverser->traverse($ast);
                }
            } catch (Exception $e) {
                // Skip files that can't be parsed
                continue;
            }
        }

        $summary = $visitor->getComplexitySummary();

        // Filter by threshold
        if ($threshold > 1) {
            $summary = $this->filterByThreshold($summary, $threshold);
        }

        return [
            'summary' => $summary,
            'class_complexity' => $visitor->getClassComplexity(),
            'method_complexity' => $visitor->getMethodComplexity(),
            'method_breakdown' => $visitor->getMethodComplexityBreakdown(),
            'files_analyzed' => count($phpFiles),
        ];
    }

    /**
     * Find PHP files in the given path.
     *
     * @param string $path Path to search
     * @return array List of PHP file paths
     */
    private function findPhpFiles(string $path): array
    {
        if (is_file($path) && pathinfo($path, PATHINFO_EXTENSION) === 'php') {
            return [$path];
        }

        if (!is_dir($path)) {
            return [];
        }

        $finder = new Finder();
        $finder->files()->name('*.php')->in($path);

        $files = [];
        foreach ($finder as $file) {
            $files[] = $file->getRealPath();
        }

        return $files;
    }

    /**
     * Filter complexity data by threshold.
     *
     * @param array $summary Complexity summary
     * @param int $threshold Minimum complexity threshold
     * @return array Filtered summary
     */
    private function filterByThreshold(array $summary, int $threshold): array
    {
        $filtered = $summary;

        // Filter classes
        $filtered['classes'] = array_filter(
            $summary['classes'],
            fn($data) => $data['complexity'] >= $threshold
        );

        // Filter methods
        $filtered['methods'] = array_filter(
            $summary['methods'],
            fn($data) => $data['complexity'] >= $threshold
        );

        // Filter high risk methods
        $filtered['high_risk_methods'] = array_filter(
            $summary['high_risk_methods'],
            fn($complexity) => $complexity >= $threshold
        );

        $filtered['very_high_risk_methods'] = array_filter(
            $summary['very_high_risk_methods'],
            fn($complexity) => $complexity >= $threshold
        );

        return $filtered;
    }

    /**
     * Render output in the specified format.
     *
     * @param array $data Complexity data
     * @param OutputInterface $output Console output
     * @param string $format Output format
     * @param bool $detailed Show detailed breakdown
     */
    private function renderOutput(array $data, OutputInterface $output, string $format, bool $detailed): void
    {
        switch ($format) {
            case 'json':
                $output->writeln(json_encode($data, JSON_PRETTY_PRINT));
                break;
            case 'csv':
                $this->renderCsv($data, $output, $detailed);
                break;
            case 'text':
            default:
                $this->renderText($data, $output, $detailed);
                break;
        }
    }

    /**
     * Render text output.
     *
     * @param array $data Complexity data
     * @param OutputInterface $output Console output
     * @param bool $detailed Show detailed breakdown
     */
    private function renderText(array $data, OutputInterface $output, bool $detailed): void
    {
        $output->writeln("<info>Cyclomatic Complexity Analysis</info>");
        $output->writeln("Files analyzed: {$data['files_analyzed']}");
        $output->writeln("");

        // Class complexity
        if (!empty($data['summary']['classes'])) {
            $output->writeln("<comment>Class Complexity:</comment>");
            foreach ($data['summary']['classes'] as $className => $classData) {
                $riskColor = $this->getRiskColor($classData['risk_level']);
                $output->writeln("  {$className}: <{$riskColor}>{$classData['complexity']}</{$riskColor}> ({$classData['risk_level']})");
            }
            $output->writeln("");
        }

        // Method complexity
        if (!empty($data['summary']['methods'])) {
            $output->writeln("<comment>Method Complexity:</comment>");
            foreach ($data['summary']['methods'] as $methodKey => $methodData) {
                $riskColor = $this->getRiskColor($methodData['risk_level']);
                $output->writeln("  {$methodKey}: <{$riskColor}>{$methodData['complexity']}</{$riskColor}> ({$methodData['risk_level']})");

                if ($detailed && !empty($methodData['breakdown'])) {
                    $this->renderBreakdown($methodData['breakdown'], $output);
                }
            }
            $output->writeln("");
        }

        // High risk methods
        if (!empty($data['summary']['high_risk_methods'])) {
            $output->writeln("<error>High Risk Methods (≥10):</error>");
            foreach ($data['summary']['high_risk_methods'] as $methodKey => $complexity) {
                $output->writeln("  {$methodKey}: {$complexity}");
            }
            $output->writeln("");
        }

        // Very high risk methods
        if (!empty($data['summary']['very_high_risk_methods'])) {
            $output->writeln("<error>Very High Risk Methods (≥15):</error>");
            foreach ($data['summary']['very_high_risk_methods'] as $methodKey => $complexity) {
                $output->writeln("  {$methodKey}: {$complexity}");
            }
            $output->writeln("");
        }

        // Summary statistics
        $this->renderSummaryStats($data, $output);
    }

    /**
     * Render detailed breakdown of complexity factors.
     *
     * @param array $breakdown Complexity breakdown
     * @param OutputInterface $output Console output
     */
    private function renderBreakdown(array $breakdown, OutputInterface $output): void
    {
        $factors = [];
        foreach ($breakdown as $factor => $count) {
            if ($factor !== 'total' && $factor !== 'base' && $count > 0) {
                $factors[] = "{$factor}: {$count}";
            }
        }

        if (!empty($factors)) {
            $output->writeln("    Breakdown: " . implode(', ', $factors));
        }
    }

    /**
     * Render summary statistics.
     *
     * @param array $data Complexity data
     * @param OutputInterface $output Console output
     */
    private function renderSummaryStats(array $data, OutputInterface $output): void
    {
        $methodComplexities = array_values($data['summary']['methods']);
        if (empty($methodComplexities)) {
            return;
        }

        $complexities = array_column($methodComplexities, 'complexity');
        $avg = array_sum($complexities) / count($complexities);
        $max = max($complexities);
        $min = min($complexities);

        $output->writeln("<comment>Summary Statistics:</comment>");
        $output->writeln("  Average complexity: " . round($avg, 2));
        $output->writeln("  Maximum complexity: {$max}");
        $output->writeln("  Minimum complexity: {$min}");
        $output->writeln("  Total methods: " . count($complexities));
    }

    /**
     * Render CSV output.
     *
     * @param array $data Complexity data
     * @param OutputInterface $output Console output
     * @param bool $detailed Show detailed breakdown
     */
    private function renderCsv(array $data, OutputInterface $output, bool $detailed): void
    {
        // Header
        $headers = ['Type', 'Name', 'Complexity', 'Risk Level'];
        if ($detailed) {
            $headers = array_merge($headers, ['If', 'ElseIf', 'Switch', 'Case', 'While', 'For', 'Foreach', 'Catch', 'Logical And', 'Logical Or', 'Ternary']);
        }
        $output->writeln(implode(',', $headers));

        // Classes
        foreach ($data['summary']['classes'] as $className => $classData) {
            $row = ['Class', $className, $classData['complexity'], $classData['risk_level']];
            if ($detailed) {
                $row = array_merge($row, array_fill(0, 11, ''));
            }
            $output->writeln(implode(',', $row));
        }

        // Methods
        foreach ($data['summary']['methods'] as $methodKey => $methodData) {
            $row = ['Method', $methodKey, $methodData['complexity'], $methodData['risk_level']];
            if ($detailed && !empty($methodData['breakdown'])) {
                $breakdown = $methodData['breakdown'];
                $row = array_merge($row, [
                    $breakdown['if'] ?? 0,
                    $breakdown['elseif'] ?? 0,
                    $breakdown['switch'] ?? 0,
                    $breakdown['case'] ?? 0,
                    $breakdown['while'] ?? 0,
                    $breakdown['for'] ?? 0,
                    $breakdown['foreach'] ?? 0,
                    $breakdown['catch'] ?? 0,
                    $breakdown['logical_and'] ?? 0,
                    $breakdown['logical_or'] ?? 0,
                    $breakdown['ternary'] ?? 0,
                ]);
            }
            $output->writeln(implode(',', $row));
        }
    }

    /**
     * Write output to file.
     *
     * @param array $data Complexity data
     * @param string $outputFile Output file path
     * @param string $format Output format
     */
    private function writeToFile(array $data, string $outputFile, string $format): void
    {
        $content = match ($format) {
            'json' => json_encode($data, JSON_PRETTY_PRINT),
            'csv' => $this->generateCsvContent($data),
            default => $this->generateTextContent($data),
        };

        file_put_contents($outputFile, $content);
    }

    /**
     * Generate CSV content for file output.
     *
     * @param array $data Complexity data
     * @return string CSV content
     */
    private function generateCsvContent(array $data): string
    {
        $output = fopen('php://temp', 'r+');

        // Header
        fputcsv($output, ['Type', 'Name', 'Complexity', 'Risk Level', 'If', 'ElseIf', 'Switch', 'Case', 'While', 'For', 'Foreach', 'Catch', 'Logical And', 'Logical Or', 'Ternary']);

        // Classes
        foreach ($data['summary']['classes'] as $className => $classData) {
            fputcsv($output, ['Class', $className, $classData['complexity'], $classData['risk_level'], '', '', '', '', '', '', '', '', '', '', '']);
        }

        // Methods
        foreach ($data['summary']['methods'] as $methodKey => $methodData) {
            $breakdown = $methodData['breakdown'] ?? [];
            fputcsv($output, [
                'Method',
                $methodKey,
                $methodData['complexity'],
                $methodData['risk_level'],
                $breakdown['if'] ?? 0,
                $breakdown['elseif'] ?? 0,
                $breakdown['switch'] ?? 0,
                $breakdown['case'] ?? 0,
                $breakdown['while'] ?? 0,
                $breakdown['for'] ?? 0,
                $breakdown['foreach'] ?? 0,
                $breakdown['catch'] ?? 0,
                $breakdown['logical_and'] ?? 0,
                $breakdown['logical_or'] ?? 0,
                $breakdown['ternary'] ?? 0,
            ]);
        }

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return $content;
    }

    /**
     * Generate text content for file output.
     *
     * @param array $data Complexity data
     * @return string Text content
     */
    private function generateTextContent(array $data): string
    {
        $output = fopen('php://temp', 'r+');

        fwrite($output, "Cyclomatic Complexity Analysis\n");
        fwrite($output, "Files analyzed: {$data['files_analyzed']}\n\n");

        // Classes
        if (!empty($data['summary']['classes'])) {
            fwrite($output, "Class Complexity:\n");
            foreach ($data['summary']['classes'] as $className => $classData) {
                fwrite($output, "  {$className}: {$classData['complexity']} ({$classData['risk_level']})\n");
            }
            fwrite($output, "\n");
        }

        // Methods
        if (!empty($data['summary']['methods'])) {
            fwrite($output, "Method Complexity:\n");
            foreach ($data['summary']['methods'] as $methodKey => $methodData) {
                fwrite($output, "  {$methodKey}: {$methodData['complexity']} ({$methodData['risk_level']})\n");
            }
            fwrite($output, "\n");
        }

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return $content;
    }

    /**
     * Get color for risk level.
     *
     * @param string $riskLevel Risk level
     * @return string Color name
     */
    private function getRiskColor(string $riskLevel): string
    {
        return match ($riskLevel) {
            'low' => 'info',
            'medium' => 'comment',
            'high' => 'error',
            'very_high' => 'error',
            default => 'info',
        };
    }
}
