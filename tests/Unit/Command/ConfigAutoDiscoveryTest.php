<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Command;

use Phauthentic\CognitiveCodeAnalysis\Application;
use Phauthentic\CognitiveCodeAnalysis\Command\ChurnSpecifications\ChurnCommandContext;
use Phauthentic\CognitiveCodeAnalysis\Command\CognitiveMetricsCommand;
use Phauthentic\CognitiveCodeAnalysis\Config\ConfigFileResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Yaml\Yaml;

class ConfigAutoDiscoveryTest extends TestCase
{
    private string $tempDir;
    private string $originalWorkingDirectory;
    private string $projectRoot;

    protected function setUp(): void
    {
        $this->originalWorkingDirectory = (string) getcwd();
        $this->projectRoot = dirname(__DIR__, 3);
        $this->tempDir = sys_get_temp_dir() . '/config-auto-discovery-' . uniqid('', true);
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        chdir($this->originalWorkingDirectory);
        $this->removeDirectory($this->tempDir);
    }

    #[Test]
    public function analyseAutoLoadsCcaYamlFromWorkingDirectory(): void
    {
        chdir($this->tempDir);
        $this->writeAutoDiscoveryConfig(0.75);

        $application = new Application();
        $command = $application->getContainer()->get(CognitiveMetricsCommand::class);
        $tester = new CommandTester($command);

        $tempOutputFile = tempnam(sys_get_temp_dir(), 'auto_discovery_report_') . '.txt';

        $tester->execute([
            'path' => $this->projectRoot . '/tests/TestCode',
            '--report-type' => 'configtext',
            '--report-file' => $tempOutputFile,
        ]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('Score Threshold: 0.75', file_get_contents($tempOutputFile));

        unlink($tempOutputFile);
    }

    #[Test]
    public function analyseExplicitConfigOverridesAutoDiscoveredCcaYaml(): void
    {
        chdir($this->tempDir);
        $this->writeAutoDiscoveryConfig(0.75);

        $explicitConfigPath = $this->tempDir . '/explicit-config.yaml';
        $this->writeConfigFile($explicitConfigPath, 0.80);

        $application = new Application();
        $command = $application->getContainer()->get(CognitiveMetricsCommand::class);
        $tester = new CommandTester($command);

        $tempOutputFile = tempnam(sys_get_temp_dir(), 'auto_discovery_override_report_') . '.txt';

        $tester->execute([
            'path' => $this->projectRoot . '/tests/TestCode',
            '--config' => $explicitConfigPath,
            '--report-type' => 'configtext',
            '--report-file' => $tempOutputFile,
        ]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('Score Threshold: 0.80', file_get_contents($tempOutputFile));

        unlink($tempOutputFile);
    }

    #[Test]
    public function churnCommandContextAutoDiscoversCcaYamlFromWorkingDirectory(): void
    {
        chdir($this->tempDir);
        file_put_contents(
            $this->tempDir . DIRECTORY_SEPARATOR . ConfigFileResolver::DEFAULT_FILENAME,
            "cognitive:\n  scoreThreshold: 0.75\n"
        );

        $input = new ArrayInput(
            ['path' => $this->projectRoot . '/src'],
            new InputDefinition([
                new InputArgument('path', InputArgument::REQUIRED),
                new InputOption('config', 'c', InputOption::VALUE_OPTIONAL),
            ])
        );

        $context = new ChurnCommandContext($input, new ConfigFileResolver());

        $this->assertTrue($context->hasConfigFile());
        $this->assertSame(
            $this->tempDir . DIRECTORY_SEPARATOR . ConfigFileResolver::DEFAULT_FILENAME,
            $context->getConfigFile()
        );
    }

    private function writeAutoDiscoveryConfig(float $scoreThreshold): void
    {
        $this->writeConfigFile(
            $this->tempDir . DIRECTORY_SEPARATOR . ConfigFileResolver::DEFAULT_FILENAME,
            $scoreThreshold
        );
    }

    private function writeConfigFile(string $path, float $scoreThreshold): void
    {
        $config = [
            'cognitive' => [
                'excludeFilePatterns' => [],
                'excludePatterns' => [],
                'scoreThreshold' => $scoreThreshold,
                'showOnlyMethodsExceedingThreshold' => true,
                'showHalsteadComplexity' => false,
                'showCyclomaticComplexity' => false,
                'showDetailedCognitiveMetrics' => true,
                'groupByClass' => false,
                'metrics' => [
                    'lineCount' => [
                        'threshold' => 60,
                        'scale' => 25.0,
                        'enabled' => true,
                    ],
                ],
                'customReporters' => [
                    'cognitive' => [
                        'configtext' => [
                            'class' => 'Tests\Fixtures\CustomReporters\ConfigAwareTextReporter',
                            'file' => $this->projectRoot . '/tests/Fixtures/CustomReporters/ConfigAwareTextReporter.php',
                        ],
                    ],
                ],
            ],
        ];

        file_put_contents($path, Yaml::dump($config, 4, 2));
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
                continue;
            }

            unlink($path);
        }

        rmdir($directory);
    }
}
