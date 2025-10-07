<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Command;

use Phauthentic\CognitiveCodeAnalysis\Application;
use Phauthentic\CognitiveCodeAnalysis\Command\CognitiveMetricsCommand;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Tests for coverage functionality in CognitiveMetricsCommand
 */
class CognitiveMetricsCommandCoverageTest extends TestCase
{
    #[Test]
    #[DataProvider('coverageFormatProvider')]
    public function testAnalyseWithCoverageFormats(string $option, string $file, string $format): void
    {
        $application = new Application();
        $command = $application->getContainer()->get(CognitiveMetricsCommand::class);
        $tester = new CommandTester($command);

        $tester->execute([
            'path' => __DIR__ . '/../../TestCode/Paginator.php',
            $option => __DIR__ . '/../../Fixtures/Coverage/' . $file,
        ]);

        $this->assertEquals(
            Command::SUCCESS,
            $tester->getStatusCode(),
            "Command should succeed with {$format} coverage"
        );

        $output = $tester->getDisplay();
        $this->assertStringContainsString('Coverage', $output, 'Output should contain Coverage column');
        $this->assertStringContainsString('%', $output, 'Output should contain percentage values');
    }

    #[Test]
    public function testAnalyseWithBothCoverageFormatsReturnsError(): void
    {
        $application = new Application();
        $command = $application->getContainer()->get(CognitiveMetricsCommand::class);
        $tester = new CommandTester($command);

        $tester->execute([
            'path' => __DIR__ . '/../../TestCode/Paginator.php',
            '--coverage-clover' => __DIR__ . '/../../Fixtures/Coverage/testcode-clover.xml',
            '--coverage-cobertura' => __DIR__ . '/../../Fixtures/Coverage/testcode-cobertura.xml',
        ]);

        $this->assertEquals(
            Command::FAILURE,
            $tester->getStatusCode(),
            'Command should fail when both coverage formats are specified'
        );

        $output = $tester->getDisplay();
        $this->assertStringContainsString('Only one coverage format can be specified at a time', $output);
    }

    #[Test]
    public function testAnalyseWithNonExistentCoverageFile(): void
    {
        $application = new Application();
        $command = $application->getContainer()->get(CognitiveMetricsCommand::class);
        $tester = new CommandTester($command);

        $tester->execute([
            'path' => __DIR__ . '/../../TestCode/Paginator.php',
            '--coverage-clover' => __DIR__ . '/../../Fixtures/Coverage/does-not-exist.xml',
        ]);

        $this->assertEquals(
            Command::FAILURE,
            $tester->getStatusCode(),
            'Command should fail with non-existent coverage file'
        );

        $output = $tester->getDisplay();
        $this->assertStringContainsString('Coverage file not found', $output);
    }

    #[Test]
    public function testAnalyseWithoutCoverageDoesNotShowCoverageColumn(): void
    {
        $application = new Application();
        $command = $application->getContainer()->get(CognitiveMetricsCommand::class);
        $tester = new CommandTester($command);

        $tester->execute([
            'path' => __DIR__ . '/../../TestCode/Paginator.php',
        ]);

        $this->assertEquals(Command::SUCCESS, $tester->getStatusCode());
        $output = $tester->getDisplay();
        // Check that the Coverage column header is not in the output
        $this->assertStringNotContainsString(
            'Line\nCoverage',
            $output,
            'Output should not contain Coverage column when no coverage file is provided'
        );
    }

    #[Test]
    #[DataProvider('methodLevelCoverageProvider')]
    public function testAnalyseShowsMethodLevelCoverage(string $format, string $file, string $zeroMethod, string $fullMethod): void
    {
        $application = new Application();
        $command = $application->getContainer()->get(CognitiveMetricsCommand::class);
        $tester = new CommandTester($command);

        $tester->execute([
            'path' => __DIR__ . '/../../TestCode/Paginator.php',
            "--coverage-{$format}" => __DIR__ . '/../../Fixtures/Coverage/' . $file,
        ]);

        $this->assertEquals(Command::SUCCESS, $tester->getStatusCode());
        $output = $tester->getDisplay();

        // The Paginator class has methods with different coverage levels
        $this->assertMatchesRegularExpression(
            "/{$zeroMethod}.*0\.00%/s",
            $output,
            "Should show 0.00% coverage for {$zeroMethod} method"
        );

        $this->assertMatchesRegularExpression(
            "/{$fullMethod}.*100\.00%/s",
            $output,
            "Should show 100.00% coverage for {$fullMethod} method"
        );
    }

    #[Test]
    public function testAnalyseWithCoverageAndMultiplePaths(): void
    {
        $application = new Application();
        $command = $application->getContainer()->get(CognitiveMetricsCommand::class);
        $tester = new CommandTester($command);

        $tester->execute([
            'path' => __DIR__ . '/../../TestCode/Paginator.php,' . __DIR__ . '/../../TestCode/FileWithTwoClasses.php',
            '--coverage-clover' => __DIR__ . '/../../Fixtures/Coverage/testcode-clover.xml',
        ]);

        $this->assertEquals(
            Command::SUCCESS,
            $tester->getStatusCode(),
            'Command should succeed with coverage and multiple paths'
        );

        $output = $tester->getDisplay();
        $this->assertStringContainsString('Coverage', $output, 'Output should contain Coverage column');
        $this->assertStringContainsString('Paginator', $output, 'Output should contain Paginator class');
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function coverageFormatProvider(): array
    {
        return [
            'Clover format' => [
                '--coverage-clover',
                'testcode-clover.xml',
                'Clover',
            ],
            'Cobertura format' => [
                '--coverage-cobertura',
                'testcode-cobertura.xml',
                'Cobertura',
            ],
        ];
    }

    /**
     * @return array<string, array{string, string, string, string}>
     */
    public static function methodLevelCoverageProvider(): array
    {
        return [
            'Clover format shows method coverage' => [
                'clover',
                'testcode-clover.xml',
                'count',
                'getQuery',
            ],
            'Cobertura format shows method coverage' => [
                'cobertura',
                'testcode-cobertura.xml',
                'count',
                'getQuery',
            ],
        ];
    }
}
