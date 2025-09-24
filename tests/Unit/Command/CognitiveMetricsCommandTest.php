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
        $this->assertStringContainsString('Sorting error', $tester->getDisplay());
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
        $this->assertStringContainsString('Sorting error', $tester->getDisplay());
    }

    public function testOutputWithoutOptions(): void
    {
        $application = new Application();
        $container = $application->getContainer();

        $command = $container->get(CognitiveMetricsCommand::class);
        $tester = new CommandTester($command);

        $tester->execute([
            'path' => __DIR__ . '/../../../tests/TestCode', // Smaller path for faster test
        ]);

        $this->assertStringEqualsFile(__DIR__ . '/OutputWithoutOptions.txt', $tester->getDisplay(true));
    }
}
