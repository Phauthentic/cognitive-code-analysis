<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Command;

use Phauthentic\CognitiveCodeAnalysis\Application;
use Phauthentic\CognitiveCodeAnalysis\Command\InitCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Yaml\Yaml;

class InitCommandTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/init-command-' . uniqid('', true);
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    #[Test]
    public function silentModeCreatesConfigWithDefaultsAtGivenPath(): void
    {
        $targetPath = $this->tempDir . '/phpcca.yaml';
        $tester = $this->createCommandTester();

        $statusCode = $tester->execute([
            '--silent' => true,
            '--path' => $targetPath,
        ]);

        $this->assertSame(Command::SUCCESS, $statusCode);
        $this->assertFileExists($targetPath);

        $config = Yaml::parseFile($targetPath);
        $this->assertSame(0.5, $config['cognitive']['scoreThreshold']);
        $this->assertTrue($config['cognitive']['groupByClass']);
        $this->assertFalse($config['cognitive']['showOnlyMethodsExceedingThreshold']);
        $this->assertFalse($config['cognitive']['showHalsteadComplexity']);
        $this->assertFalse($config['cognitive']['showCyclomaticComplexity']);
        $this->assertTrue($config['cognitive']['showDetailedCognitiveMetrics']);
    }

    #[Test]
    public function silentModeUsesDefaultPathInWorkingDirectory(): void
    {
        $originalWorkingDirectory = getcwd();
        chdir($this->tempDir);

        try {
            $tester = $this->createCommandTester();
            $statusCode = $tester->execute(['--silent' => true]);

            $this->assertSame(Command::SUCCESS, $statusCode);
            $this->assertFileExists($this->tempDir . '/phpcca.yaml');
        } finally {
            chdir($originalWorkingDirectory);
        }
    }

    #[Test]
    public function interactiveModeWritesSelectedSettings(): void
    {
        $targetPath = $this->tempDir . '/interactive-cca.yaml';
        $tester = $this->createCommandTester();
        $tester->setInputs([
            '0.8',
            'yes',
            'yes',
            'no',
            'yes',
            'no',
        ]);

        $statusCode = $tester->execute([
            '--path' => $targetPath,
        ]);

        $this->assertSame(Command::SUCCESS, $statusCode);

        $config = Yaml::parseFile($targetPath);
        $this->assertSame(0.8, $config['cognitive']['scoreThreshold']);
        $this->assertTrue($config['cognitive']['showOnlyMethodsExceedingThreshold']);
        $this->assertTrue($config['cognitive']['showHalsteadComplexity']);
        $this->assertFalse($config['cognitive']['showCyclomaticComplexity']);
        $this->assertTrue($config['cognitive']['showDetailedCognitiveMetrics']);
        $this->assertFalse($config['cognitive']['groupByClass']);
    }

    #[Test]
    public function failsWhenFileExistsWithoutForce(): void
    {
        $targetPath = $this->tempDir . '/existing.yaml';
        file_put_contents($targetPath, 'existing: true');
        $tester = $this->createCommandTester();

        $statusCode = $tester->execute([
            '--silent' => true,
            '--path' => $targetPath,
        ]);

        $this->assertSame(Command::FAILURE, $statusCode);
        $this->assertSame('existing: true', file_get_contents($targetPath));
    }

    #[Test]
    public function forceOverwritesExistingFile(): void
    {
        $targetPath = $this->tempDir . '/overwrite.yaml';
        file_put_contents($targetPath, 'existing: true');
        $tester = $this->createCommandTester();

        $statusCode = $tester->execute([
            '--silent' => true,
            '--force' => true,
            '--path' => $targetPath,
        ]);

        $this->assertSame(Command::SUCCESS, $statusCode);

        $config = Yaml::parseFile($targetPath);
        $this->assertArrayHasKey('cognitive', $config);
    }

    #[Test]
    public function rejectsInvalidScoreThreshold(): void
    {
        $targetPath = $this->tempDir . '/invalid-threshold.yaml';
        $tester = $this->createCommandTester();
        $tester->setInputs([
            'invalid',
            '0.5',
            'no',
            'no',
            'no',
            'yes',
            'yes',
        ]);

        $statusCode = $tester->execute([
            '--path' => $targetPath,
        ]);

        $this->assertSame(Command::SUCCESS, $statusCode);
        $this->assertSame(0.5, Yaml::parseFile($targetPath)['cognitive']['scoreThreshold']);
    }

    private function createCommandTester(): CommandTester
    {
        $application = new Application();
        $command = $application->getContainer()->get(InitCommand::class);

        return new CommandTester($command);
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
