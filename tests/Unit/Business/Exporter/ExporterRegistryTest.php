<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business\Exporter;

use Phauthentic\CognitiveCodeAnalysis\Business\Exporter\ExporterRegistry;
use Phauthentic\CognitiveCodeAnalysis\Config\CognitiveConfig;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Test case for ExporterRegistry class.
 */
class ExporterRegistryTest extends TestCase
{
    private ExporterRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new ExporterRegistry();
    }

    #[Test]
    public function testLoadExporterWithExistingClass(): void
    {
        // Test loading a class that already exists (JsonExporter)
        $this->registry->loadExporter(
            'Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Exporter\JsonExporter',
            null
        );

        // Should not throw an exception
        $this->assertTrue(true);
    }

    #[Test]
    public function testLoadExporterWithFile(): void
    {
        // Create a temporary PHP file with a test class
        $tempFile = tempnam(sys_get_temp_dir(), 'test_exporter_') . '.php';
        $classContent = <<<'PHP'
<?php
namespace TestNamespace;
class TestExporter {
    public function export($data, $filename) {}
}
PHP;
        file_put_contents($tempFile, $classContent);

        try {
            $this->registry->loadExporter('TestNamespace\TestExporter', $tempFile);
            $this->assertTrue(true);
        } finally {
            unlink($tempFile);
        }
    }

    #[Test]
    public function testLoadExporterWithNonExistentFile(): void
    {
        $this->expectException(CognitiveAnalysisException::class);
        $this->expectExceptionMessage('Exporter file not found: /non/existent/file.php');

        $this->registry->loadExporter('TestClass', '/non/existent/file.php');
    }

    #[Test]
    public function testLoadExporterWithNonExistentClass(): void
    {
        $this->expectException(CognitiveAnalysisException::class);
        $this->expectExceptionMessage('Exporter class not found: NonExistentClass');

        $this->registry->loadExporter('NonExistentClass', null);
    }

    #[Test]
    public function testInstantiateWithoutConfig(): void
    {
        $exporter = $this->registry->instantiate(
            'Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Exporter\JsonExporter',
            false,
            null
        );

        $this->assertInstanceOf('Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Exporter\JsonExporter', $exporter);
    }

    #[Test]
    public function testInstantiateWithConfig(): void
    {
        $config = new CognitiveConfig(
            excludeFilePatterns: [],
            excludePatterns: [],
            metrics: [],
            showOnlyMethodsExceedingThreshold: false,
            scoreThreshold: 0.5
        );

        $exporter = $this->registry->instantiate(
            'Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Exporter\MarkdownExporter',
            true,
            $config
        );

        $this->assertInstanceOf('Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Exporter\MarkdownExporter', $exporter);
    }

    #[Test]
    public function testValidateInterfaceWithValidExporter(): void
    {
        $exporter = new \Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Exporter\JsonExporter();

        $this->registry->validateInterface(
            $exporter,
            'Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Exporter\DataExporterInterface'
        );

        // Should not throw an exception
        $this->assertTrue(true);
    }

    #[Test]
    public function testValidateInterfaceWithInvalidExporter(): void
    {
        $this->expectException(CognitiveAnalysisException::class);
        $this->expectExceptionMessage('Exporter must implement InvalidInterface');

        $exporter = new \Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Exporter\JsonExporter();

        $this->registry->validateInterface($exporter, 'InvalidInterface');
    }

    #[Test]
    public function testFileIsLoadedOnlyOnce(): void
    {
        // Create a temporary PHP file
        $tempFile = tempnam(sys_get_temp_dir(), 'test_exporter_once_') . '.php';
        $classContent = <<<'PHP'
<?php
namespace TestOnceNamespace;
class TestOnceExporter {
    public function export($data, $filename) {}
}
PHP;
        file_put_contents($tempFile, $classContent);

        try {
            // Load the same file twice
            $this->registry->loadExporter('TestOnceNamespace\TestOnceExporter', $tempFile);
            $this->registry->loadExporter('TestOnceNamespace\TestOnceExporter', $tempFile);

            // Should not throw an exception (file should be loaded only once)
            $this->assertTrue(true);
        } finally {
            unlink($tempFile);
        }
    }
}
