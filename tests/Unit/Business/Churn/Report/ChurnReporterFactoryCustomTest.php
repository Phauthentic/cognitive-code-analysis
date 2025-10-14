<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business\Churn\Report;

use InvalidArgumentException;
use Phauthentic\CognitiveCodeAnalysis\Business\Churn\Report\ChurnReportFactory;
use Phauthentic\CognitiveCodeAnalysis\Business\Churn\Report\ReportGeneratorInterface;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;
use Phauthentic\CognitiveCodeAnalysis\Config\ConfigService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business\Churn\Report\TestCognitiveConfig;

/**
 * Test case for ChurnReportFactory with custom exporters.
 */
class ChurnReporterFactoryCustomTest extends TestCase
{
    /**
     * @throws Exception
     */
    private function createMockConfigService(array $customReporters = []): ConfigService&MockObject
    {
        // Create TestCognitiveConfig with the custom exporters
        $config = new TestCognitiveConfig(customReporters: ['churn' => $customReporters]);

        $configService = $this->createMock(ConfigService::class);
        $configService->method('getConfig')->willReturn($config);

        return $configService;
    }

    #[Test]
    public function testCreateBuiltInExporter(): void
    {
        $configService = $this->createMockConfigService();
        $factory = new ChurnReportFactory($configService);

        $exporter = $factory->create('json');

        $this->assertInstanceOf(ReportGeneratorInterface::class, $exporter);
        $this->assertInstanceOf('Phauthentic\CognitiveCodeAnalysis\Business\Churn\Report\JsonReport', $exporter);
    }

    #[Test]
    public function testCreateCustomExporterWithFile(): void
    {
        // Create a temporary PHP file with a custom exporter
        $tempFile = tempnam(sys_get_temp_dir(), 'custom_churn_exporter_') . '.php';
        $classContent = <<<'PHP'
<?php
namespace TestCustomChurn;
use Phauthentic\CognitiveCodeAnalysis\Business\Churn\ChurnMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\Business\Churn\Report\ReportGeneratorInterface;

class CustomChurnExporter implements ReportGeneratorInterface {
    public function export(ChurnMetricsCollection $metrics, string $filename): void {
        file_put_contents($filename, 'custom churn data');
    }
}
PHP;
        file_put_contents($tempFile, $classContent);

        try {
            $customReporters = [
                'custom' => [
                    'class' => 'TestCustomChurn\CustomChurnExporter',
                    'file' => $tempFile
                ]
            ];

            $factory = new ChurnReportFactory($this->createMockConfigService($customReporters));
            $exporter = $factory->create('custom');

            $this->assertInstanceOf(ReportGeneratorInterface::class, $exporter);
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
use Phauthentic\CognitiveCodeAnalysis\Business\Churn\ChurnMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\Business\Churn\Report\ReportGeneratorInterface;

class AutoloadedChurnExporter implements ReportGeneratorInterface {
    public function export(ChurnMetricsCollection $metrics, string $filename): void {
        file_put_contents($filename, 'autoloaded churn data');
    }
}
PHP;
        file_put_contents($tempFile, $classContent);
        require_once $tempFile;

        try {
            $customReporters = [
                'autoloaded' => [
                    'class' => 'TestAutoloadedChurn\AutoloadedChurnExporter',
                    'file' => null
                ]
            ];

            $factory = new ChurnReportFactory($this->createMockConfigService($customReporters));
            $exporter = $factory->create('autoloaded');

            $this->assertInstanceOf(ReportGeneratorInterface::class, $exporter);
            $this->assertInstanceOf('TestAutoloadedChurn\AutoloadedChurnExporter', $exporter);
        } finally {
            unlink($tempFile);
        }
    }

    #[Test]
    public function testCreateUnsupportedExporter(): void
    {
        $factory = new ChurnReportFactory($this->createMockConfigService());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported exporter type: unsupported');

        $factory->create('unsupported');
    }

    #[Test]
    public function testGetSupportedTypesIncludesCustomExporters(): void
    {
        $customReporters = [
            'custom1' => [
                'class' => 'TestCustom1\Exporter',
                'file' => null
            ],
            'custom2' => [
                'class' => 'TestCustom2\Exporter',
                'file' => null
            ]
        ];

        $factory = new ChurnReportFactory($this->createMockConfigService($customReporters));
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
        $customReporters = [
            'custom' => [
                'class' => 'TestCustom\Exporter',
                'file' => null
            ]
        ];

        $factory = new ChurnReportFactory($this->createMockConfigService($customReporters));

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
            $customReporters = [
                'invalid' => [
                    'class' => 'TestInvalidChurn\InvalidChurnExporter',
                    'file' => $tempFile
                ]
            ];

            $factory = new ChurnReportFactory($this->createMockConfigService($customReporters));

            $this->expectException(CognitiveAnalysisException::class);
            $this->expectExceptionMessage('Exporter must implement Phauthentic\CognitiveCodeAnalysis\Business\Churn\Report\ReportGeneratorInterface');

            $factory->create('invalid');
        } finally {
            unlink($tempFile);
        }
    }

    #[Test]
    public function testCustomExporterWithNonExistentFile(): void
    {
        $customReporters = [
            'missing' => [
                'class' => 'TestMissing\Exporter',
                'file' => '/non/existent/file.php'
            ]
        ];

        $factory = new ChurnReportFactory($this->createMockConfigService($customReporters));

        $this->expectException(CognitiveAnalysisException::class);
        $this->expectExceptionMessage('Exporter file not found: /non/existent/file.php');

        $factory->create('missing');
    }
}
