<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Command;

use Phauthentic\CognitiveCodeAnalysis\Application;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;
use Phauthentic\CognitiveCodeAnalysis\Command\CognitiveMetricsCommand;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 *
 */
class CognitiveMetricsCommandTest extends TestCase
{
    #[Test]
    public function testAnalyseNonExistentPath(): void
    {
        $this->expectException(CognitiveAnalysisException::class);
        $this->expectExceptionMessage('Path does not exist: does-not-exist');

        $application = new Application();
        $command = $application->getContainer()->get(CognitiveMetricsCommand::class);
        $tester = new CommandTester($command);

        $tester->execute([
            'path' => 'does-not-exist',
        ]);
    }

    #[Test]
    public function testAnalyse(): void
    {
        $application = new Application();
        $command = $application->getContainer()->get(CognitiveMetricsCommand::class);
        $tester = new CommandTester($command);

        $tester->execute([
            'path' => __DIR__ . '/../../../src',
        ]);

        $this->assertEquals(Command::SUCCESS, $tester->getStatusCode(), 'Command should succeed');
    }

    #[Test]
    #[DataProvider('multiplePathsDataProvider')]
    public function testAnalyseWithMultiplePaths(string $path, string $description): void
    {
        $application = new Application();
        $command = $application->getContainer()->get(CognitiveMetricsCommand::class);
        $tester = new CommandTester($command);

        $tester->execute([
            'path' => $path,
        ]);

        $this->assertEquals(Command::SUCCESS, $tester->getStatusCode(), $description);
    }

    #[Test]
    #[DataProvider('reportDataProvider')]
    public function testAnalyseWithJsonReport(array $input, int $returnCode): void
    {
        $tmpDir = sys_get_temp_dir();
        $file = $tmpDir . '/cognitive-metrics.json';
        $application = new Application();
        $command = $application->getContainer()->get(CognitiveMetricsCommand::class);
        $tester = new CommandTester($command);

        $tester->execute(
            input: $input
        );

        $this->assertEquals($returnCode, $tester->getStatusCode());

        if ($returnCode === Command::SUCCESS) {
            $this->assertFileExists($file);
        } else {
            $this->assertFileDoesNotExist($file);
        }

        unlink($file);
    }

    public static function reportDataProvider(): array
    {
        $tmpDir = sys_get_temp_dir();
        $file = $tmpDir . '/cognitive-metrics.json';

        return [
            'Successful Report Generation' => [
                'input' => [
                    'path' => __DIR__ . '/../../../src',
                    '--report-type' => 'json',
                    '--report-file' => $file,
                ],
                'returnCode' => Command::SUCCESS,
            ],
            'Invalid Report Type' => [
                'input' => [
                    'path' => __DIR__ . '/../../../src',
                    '--report-type' => 'invalid-type',
                    '--report-file' => $file,
                ],
                'returnCode' => Command::FAILURE,
            ],
            'Missing Report Option' => [
                'input' => [
                    'path' => __DIR__ . '/../../../src',
                    '--report-file' => $file,
                ],
                'returnCode' => Command::FAILURE,
            ],
            'Missing Report File Option' => [
                'input' => [
                    'path' => __DIR__ . '/../../../src',
                    '--report-type' => 'json',
                ],
                'returnCode' => Command::FAILURE,
            ]
        ];
    }

    #[Test]
    public function testAnalyseWithSorting(): void
    {
        $application = new Application();
        $command = $application->getContainer()->get(CognitiveMetricsCommand::class);
        $tester = new CommandTester($command);

        $tester->execute([
            'path' => __DIR__ . '/../../../src',
            '--sort-by' => 'score',
            '--sort-order' => 'desc',
        ]);

        $this->assertEquals(Command::SUCCESS, $tester->getStatusCode(), 'Command should succeed with sorting');
    }

    #[Test]
    public function testAnalyseWithInvalidSortField(): void
    {
        $application = new Application();
        $command = $application->getContainer()->get(CognitiveMetricsCommand::class);
        $tester = new CommandTester($command);

        $tester->execute([
            'path' => __DIR__ . '/../../../src',
            '--sort-by' => 'invalid-field',
        ]);

        $this->assertEquals(Command::FAILURE, $tester->getStatusCode(), 'Command should fail with invalid sort field');
        $this->assertStringContainsString('Invalid sort field "invalid-field"', $tester->getDisplay());
    }

    #[Test]
    public function testAnalyseWithInvalidSortOrder(): void
    {
        $application = new Application();
        $command = $application->getContainer()->get(CognitiveMetricsCommand::class);
        $tester = new CommandTester($command);

        $tester->execute([
            'path' => __DIR__ . '/../../../src',
            '--sort-by' => 'score',
            '--sort-order' => 'invalid',
        ]);

        $this->assertEquals(Command::FAILURE, $tester->getStatusCode(), 'Command should fail with invalid sort order');
        $this->assertStringContainsString('Sort order must be "asc" or "desc", got "invalid"', $tester->getDisplay());
    }

    public function testOutputWithoutOptions(): void
    {
        $application = new Application();
        $container = $application->getContainer();

        $command = $container->get(CognitiveMetricsCommand::class);
        $tester = new CommandTester($command);

        $tester->execute([
            'path' => __DIR__ . '/../../../tests/TestCode',
        ]);

        $this->assertStringEqualsFile(__DIR__ . '/OutputWithoutOptions.txt', $tester->getDisplay(true));
    }

    #[DataProvider('configurationOutputProvider')]
    public function testConfigurationOutput(string $configFile, string $expectedOutputFile, string $description): void
    {
        $application = new Application();
        $container = $application->getContainer();

        $command = $container->get(CognitiveMetricsCommand::class);
        $tester = new CommandTester($command);

        $tester->execute([
            'path' => __DIR__ . '/../../../tests/TestCode',
            '--config' => __DIR__ . '/../../../tests/Fixtures/' . $configFile,
        ]);

        $this->assertStringEqualsFile(__DIR__ . '/' . $expectedOutputFile, $tester->getDisplay(true), $description);
    }

    /**
     * Data provider for configuration output tests
     *
     * @return array<int, array{string, string, string}>
     */
    public static function configurationOutputProvider(): array
    {
        return [
            'all metrics enabled' => [
                'all-metrics-config.yml',
                'OutputWithAllMetrics.txt',
                'Should show all metrics including Halstead and Cyclomatic complexity'
            ],
            'halstead only' => [
                'halstead-only-config.yml',
                'OutputWithHalsteadOnly.txt',
                'Should show only Halstead complexity metrics'
            ],
            'cyclomatic only' => [
                'cyclomatic-only-config.yml',
                'OutputWithCyclomaticOnly.txt',
                'Should show only Cyclomatic complexity metrics'
            ],
            'no detailed metrics' => [
                'no-detailed-metrics-config.yml',
                'OutputWithoutDetailedMetrics.txt',
                'Should show only basic metrics without detailed breakdown'
            ],
            'single table layout' => [
                'single-table-config.yml',
                'OutputWithSingleTable.txt',
                'Should display all methods in a single table instead of grouped by class'
            ],
            'threshold filtering' => [
                'threshold-config.yml',
                'OutputWithThreshold.txt',
                'Should only show methods exceeding the complexity threshold'
            ],
            'minimal configuration' => [
                'minimal-config.yml',
                'OutputWithMinimalConfig.txt',
                'Should show only basic cognitive complexity with minimal columns'
            ],
        ];
    }

    /**
     * Data provider for multiple paths tests
     *
     * @return array<int, array{string, string}>
     */
    public static function multiplePathsDataProvider(): array
    {
        return [
            'multiple files' => [
                __DIR__ . '/../../../src/Command/CognitiveMetricsCommand.php,' . __DIR__ . '/../../../src/Business/MetricsFacade.php',
                'Command should succeed with multiple files'
            ],
            'multiple files with spaces' => [
                __DIR__ . '/../../../src/Command/CognitiveMetricsCommand.php, ' . __DIR__ . '/../../../src/Business/MetricsFacade.php, ' . __DIR__ . '/../../../src/Business/Utility/DirectoryScanner.php',
                'Command should succeed with multiple files and spaces'
            ],
            'multiple directories' => [
                __DIR__ . '/../../../src/Command,' . __DIR__ . '/../../../src/Business',
                'Command should succeed with multiple directories'
            ],
            'mixed paths' => [
                __DIR__ . '/../../../src/Command,' . __DIR__ . '/../../../src/Business/MetricsFacade.php',
                'Command should succeed with mixed directories and files'
            ],
            'mixed paths with spaces' => [
                __DIR__ . '/../../../src/Command, ' . __DIR__ . '/../../../src/Business/MetricsFacade.php, ' . __DIR__ . '/../../../src/Business/Utility/DirectoryScanner.php',
                'Command should succeed with mixed paths and spaces'
            ],
        ];
    }

    #[Test]
    public function testAnalyseWithCustomTextReporter(): void
    {
        $application = new Application();
        $command = $application->getContainer()->get(CognitiveMetricsCommand::class);
        $tester = new CommandTester($command);

        $tempOutputFile = tempnam(sys_get_temp_dir(), 'custom_text_report_') . '.txt';

        $tester->execute([
            'path' => __DIR__ . '/../../../tests/TestCode',
            '--config' => __DIR__ . '/../../../tests/Fixtures/custom-text-reporter-config.yml',
            '--report-type' => 'customtext',
            '--report-file' => $tempOutputFile,
        ]);

        // Debug: Show the actual output and status
        if ($tester->getStatusCode() !== Command::SUCCESS) {
            echo "Command failed with status: " . $tester->getStatusCode() . "\n";
            echo "Output: " . $tester->getDisplay() . "\n";
        }

        $this->assertEquals(Command::SUCCESS, $tester->getStatusCode(), 'Command should succeed with custom text reporter');
        $this->assertFileExists($tempOutputFile, 'Custom report file should be created');

        $content = file_get_contents($tempOutputFile);
        $this->assertNotEmpty($content, 'Report file should not be empty');
        $this->assertStringContainsString('=== Cognitive Complexity Report ===', $content, 'Should contain report header');
        $this->assertStringContainsString('Generated by CustomTextReporter', $content, 'Should contain reporter identification');
        $this->assertStringContainsString('Total Methods:', $content, 'Should contain summary');
        $this->assertStringContainsString('Average Score:', $content, 'Should contain average score');

        // Verify that actual metrics data is present
        $this->assertStringContainsString('Class:', $content, 'Should contain class information');
        $this->assertStringContainsString('Method:', $content, 'Should contain method information');
        $this->assertStringContainsString('Score:', $content, 'Should contain score information');

        unlink($tempOutputFile);
    }

    #[Test]
    public function testAnalyseWithConfigAwareCustomReporter(): void
    {
        $application = new Application();
        $command = $application->getContainer()->get(CognitiveMetricsCommand::class);
        $tester = new CommandTester($command);

        $tempOutputFile = tempnam(sys_get_temp_dir(), 'config_aware_report_') . '.txt';

        $tester->execute([
            'path' => __DIR__ . '/../../../tests/TestCode',
            '--config' => __DIR__ . '/../../../tests/Fixtures/config-aware-text-reporter-config.yml',
            '--report-type' => 'configtext',
            '--report-file' => $tempOutputFile,
        ]);

        $this->assertEquals(Command::SUCCESS, $tester->getStatusCode(), 'Command should succeed with config-aware custom reporter');
        $this->assertFileExists($tempOutputFile, 'Config-aware report file should be created');

        $content = file_get_contents($tempOutputFile);
        $this->assertNotEmpty($content, 'Config-aware report file should not be empty');
        $this->assertStringContainsString('=== Config-Aware Cognitive Complexity Report ===', $content, 'Should contain config-aware report header');
        $this->assertStringContainsString('Generated by ConfigAwareTextReporter', $content, 'Should contain reporter identification');

        // Verify config information is included
        $this->assertStringContainsString('Configuration:', $content, 'Should contain configuration section');
        $this->assertStringContainsString('Score Threshold: 0.80', $content, 'Should contain score threshold from config');
        $this->assertStringContainsString('Group By Class: No', $content, 'Should contain group by class setting');
        $this->assertStringContainsString('Show Only Methods Exceeding Threshold: Yes', $content, 'Should contain threshold filtering setting');

        // Verify threshold-based analysis
        $this->assertStringContainsString('[ABOVE THRESHOLD]', $content, 'Should mark methods above threshold');
        $this->assertStringContainsString('Methods Above Threshold (0.80):', $content, 'Should count methods above threshold');

        unlink($tempOutputFile);
    }

    #[Test]
    public function testAnalyseWithInvalidCustomReporter(): void
    {
        $application = new Application();
        $command = $application->getContainer()->get(CognitiveMetricsCommand::class);
        $tester = new CommandTester($command);

        $tempOutputFile = tempnam(sys_get_temp_dir(), 'invalid_custom_report_') . '.txt';

        $tester->execute([
            'path' => __DIR__ . '/../../../tests/TestCode',
            '--config' => __DIR__ . '/../../../tests/Fixtures/custom-text-reporter-config.yml',
            '--report-type' => 'nonexistent',
            '--report-file' => $tempOutputFile,
        ]);

        $this->assertEquals(Command::FAILURE, $tester->getStatusCode(), 'Command should fail with invalid custom reporter');
        $this->assertFileDoesNotExist($tempOutputFile, 'Report file should not be created for invalid reporter');

        // Note: Error messages are written to stderr, which CommandTester doesn't capture by default
        // The important thing is that the command fails with the correct status code
    }
}
