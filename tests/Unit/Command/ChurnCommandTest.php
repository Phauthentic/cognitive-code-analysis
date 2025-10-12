<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Command;

use Phauthentic\CognitiveCodeAnalysis\Application;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;
use Phauthentic\CognitiveCodeAnalysis\Command\ChurnCommand;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ChurnCommandTest extends TestCase
{
    #[Test]
    public function testAnalyseNonExistentPath(): void
    {
        $this->expectException(CognitiveAnalysisException::class);
        $this->expectExceptionMessage('Path does not exist: does-not-exist');

        $application = new Application();
        $command = $application->getContainer()->get(ChurnCommand::class);
        $tester = new CommandTester($command);

        $tester->execute([
            'path' => 'does-not-exist',
        ]);
    }

    #[Test]
    public function testChurnSuccessfully(): void
    {
        $application = new Application();
        $command = $application->getContainer()->get(ChurnCommand::class);
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
        $file = $tmpDir . '/churn-metrics.json';
        $application = new Application();
        $command = $application->getContainer()->get(ChurnCommand::class);
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
        $file = $tmpDir . '/churn-metrics.json';

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
}
