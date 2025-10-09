<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business\Churn\Exporter;

use InvalidArgumentException;
use Phauthentic\CognitiveCodeAnalysis\Business\Churn\Exporter\ChurnExporterFactory;
use Phauthentic\CognitiveCodeAnalysis\Business\Churn\Exporter\DataExporterInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Test case for ChurnExporterFactory with custom exporters.
 */
class ChurnExporterFactoryCustomTest extends TestCase
{
    #[Test]
    public function testCreateBuiltInExporter(): void
    {
        $factory = new ChurnExporterFactory();

        $exporter = $factory->create('json');

        $this->assertInstanceOf(DataExporterInterface::class, $exporter);
        $this->assertInstanceOf('Phauthentic\CognitiveCodeAnalysis\Business\Churn\Exporter\JsonExporter', $exporter);
    }

    #[Test]
    public function testCreateCustomExporterWithFile(): void
    {
        // Create a temporary PHP file with a custom exporter
        $tempFile = tempnam(sys_get_temp_dir(), 'custom_churn_exporter_') . '.php';
        $classContent = <<<'PHP'
<?php
namespace TestCustomChurn;
use Phauthentic\CognitiveCodeAnalysis\Business\Churn\Exporter\DataExporterInterface;

class CustomChurnExporter implements DataExporterInterface {
    public function export(array $classes, string $filename): void {
        file_put_contents($filename, 'custom churn data');
    }
}
PHP;
        file_put_contents($tempFile, $classContent);

        try {
            $customExporters = [
                'custom' => [
                    'class' => 'TestCustomChurn\CustomChurnExporter',
                    'file' => $tempFile
                ]
            ];

            $factory = new ChurnExporterFactory($customExporters);
            $exporter = $factory->create('custom');

            $this->assertInstanceOf(DataExporterInterface::class, $exporter);
            $this->assertInstanceOf('TestCustomChurn\CustomChurnExporter', $exporter);
        } finally {
            unlink($tempFile);
        }
    }

    #[Test]
    public function testCreateCustomExporterWithoutFile(): void
    {
        // Create a temporary PHP file and include it manually to simulate autoloading
        $tempFile = tempnam(sys_get_temp_dir(), 'autoloaded_churn_exporter_') . '.php';
        $classContent = <<<'PHP'
<?php
namespace TestAutoloadedChurn;
use Phauthentic\CognitiveCodeAnalysis\Business\Churn\Exporter\DataExporterInterface;

class AutoloadedChurnExporter implements DataExporterInterface {
    public function export(array $classes, string $filename): void {
        file_put_contents($filename, 'autoloaded churn data');
    }
}
PHP;
        file_put_contents($tempFile, $classContent);
        require_once $tempFile;

        try {
            $customExporters = [
                'autoloaded' => [
                    'class' => 'TestAutoloadedChurn\AutoloadedChurnExporter',
                    'file' => null
                ]
            ];

            $factory = new ChurnExporterFactory($customExporters);
            $exporter = $factory->create('autoloaded');

            $this->assertInstanceOf(DataExporterInterface::class, $exporter);
            $this->assertInstanceOf('TestAutoloadedChurn\AutoloadedChurnExporter', $exporter);
        } finally {
            unlink($tempFile);
        }
    }

    #[Test]
    public function testCreateUnsupportedExporter(): void
    {
        $factory = new ChurnExporterFactory();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported exporter type: unsupported');

        $factory->create('unsupported');
    }

    #[Test]
    public function testGetSupportedTypesIncludesCustomExporters(): void
    {
        $customExporters = [
            'custom1' => [
                'class' => 'TestCustom1\Exporter',
                'file' => null
            ],
            'custom2' => [
                'class' => 'TestCustom2\Exporter',
                'file' => null
            ]
        ];

        $factory = new ChurnExporterFactory($customExporters);
        $supportedTypes = $factory->getSupportedTypes();

        $expectedBuiltInTypes = ['json', 'csv', 'html', 'markdown', 'svg-treemap', 'svg'];
        $expectedCustomTypes = ['custom1', 'custom2'];

        foreach ($expectedBuiltInTypes as $type) {
            $this->assertContains($type, $supportedTypes);
        }

        foreach ($expectedCustomTypes as $type) {
            $this->assertContains($type, $supportedTypes);
        }
    }

    #[Test]
    public function testIsSupportedWithCustomExporters(): void
    {
        $customExporters = [
            'custom' => [
                'class' => 'TestCustom\Exporter',
                'file' => null
            ]
        ];

        $factory = new ChurnExporterFactory($customExporters);

        $this->assertTrue($factory->isSupported('json'));
        $this->assertTrue($factory->isSupported('custom'));
        $this->assertFalse($factory->isSupported('unsupported'));
    }

    #[Test]
    public function testCustomExporterWithInvalidInterface(): void
    {
        // Create a temporary PHP file with a class that doesn't implement the interface
        $tempFile = tempnam(sys_get_temp_dir(), 'invalid_churn_exporter_') . '.php';
        $classContent = <<<'PHP'
<?php
namespace TestInvalidChurn;

class InvalidChurnExporter {
    public function export(array $classes, string $filename): void {
        file_put_contents($filename, 'invalid churn data');
    }
}
PHP;
        file_put_contents($tempFile, $classContent);

        try {
            $customExporters = [
                'invalid' => [
                    'class' => 'TestInvalidChurn\InvalidChurnExporter',
                    'file' => $tempFile
                ]
            ];

            $factory = new ChurnExporterFactory($customExporters);

            $this->expectException(\Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException::class);
            $this->expectExceptionMessage('Exporter must implement Phauthentic\CognitiveCodeAnalysis\Business\Churn\Exporter\DataExporterInterface');

            $factory->create('invalid');
        } finally {
            unlink($tempFile);
        }
    }

    #[Test]
    public function testCustomExporterWithNonExistentFile(): void
    {
        $customExporters = [
            'missing' => [
                'class' => 'TestMissing\Exporter',
                'file' => '/non/existent/file.php'
            ]
        ];

        $factory = new ChurnExporterFactory($customExporters);

        $this->expectException(\Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException::class);
        $this->expectExceptionMessage('Exporter file not found: /non/existent/file.php');

        $factory->create('missing');
    }
}
