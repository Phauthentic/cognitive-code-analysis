<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business\Cognitive\Exporter;

use InvalidArgumentException;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Report\CognitiveReportFactory;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Report\ReportGeneratorInterface;
use Phauthentic\CognitiveCodeAnalysis\Config\CognitiveConfig;
use Phauthentic\CognitiveCodeAnalysis\Config\ConfigService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Test case for CognitiveReportFactory with custom exporters.
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CognitiveExporterFactoryCustomTest extends TestCase
{
    private CognitiveConfig $config;

    protected function setUp(): void
    {
        parent::setUp();
        $this->config = new CognitiveConfig(
            excludeFilePatterns: [],
            excludePatterns: [],
            metrics: [],
            showOnlyMethodsExceedingThreshold: false,
            scoreThreshold: 0.5
        );
    }

    private function createMockConfigService(array $customExporters = []): ConfigService&MockObject
    {
        $config = new CognitiveConfig(
            excludeFilePatterns: [],
            excludePatterns: [],
            metrics: [],
            showOnlyMethodsExceedingThreshold: false,
            scoreThreshold: 0.5,
            customExporters: ['cognitive' => $customExporters]
        );

        $configService = $this->createMock(ConfigService::class);
        $configService->method('getConfig')->willReturn($config);

        return $configService;
    }

    #[Test]
    public function testCreateBuiltInExporter(): void
    {
        $factory = new CognitiveReportFactory($this->createMockConfigService());

        $exporter = $factory->create('json');

        $this->assertInstanceOf(ReportGeneratorInterface::class, $exporter);
        $this->assertInstanceOf('Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Report\JsonReport', $exporter);
    }

    #[Test]
    public function testCreateBuiltInExporterWithConfig(): void
    {
        $factory = new CognitiveReportFactory($this->createMockConfigService());

        $exporter = $factory->create('markdown');

        $this->assertInstanceOf(ReportGeneratorInterface::class, $exporter);
        $this->assertInstanceOf('Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Report\MarkdownReport', $exporter);
    }

    #[Test]
    public function testCreateCustomExporterWithFile(): void
    {
        // Create a temporary PHP file with a custom exporter
        $tempFile = tempnam(sys_get_temp_dir(), 'custom_cognitive_exporter_') . '.php';
        $classContent = <<<'PHP'
<?php
namespace TestCustomCognitive;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Report\ReportGeneratorInterface;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;

class CustomCognitiveExporter implements ReportGeneratorInterface {
    public function export(CognitiveMetricsCollection $metrics, string $filename): void {
        file_put_contents($filename, 'custom cognitive data');
    }
}
PHP;
        file_put_contents($tempFile, $classContent);

        try {
            $customExporters = [
                'custom' => [
                    'class' => 'TestCustomCognitive\CustomCognitiveExporter',
                    'file' => $tempFile,
                ]
            ];

            $factory = new CognitiveReportFactory($this->createMockConfigService($customExporters));
            $exporter = $factory->create('custom');

            $this->assertInstanceOf(ReportGeneratorInterface::class, $exporter);
            $this->assertInstanceOf('TestCustomCognitive\CustomCognitiveExporter', $exporter);
        } finally {
            unlink($tempFile);
        }
    }

    #[Test]
    public function testCreateCustomExporterWithConfig(): void
    {
        // Create a temporary PHP file with a custom exporter that requires config
        $tempFile = tempnam(sys_get_temp_dir(), 'config_cognitive_exporter_') . '.php';
        $classContent = <<<'PHP'
<?php
namespace TestConfigCognitive;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Report\ReportGeneratorInterface;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\Config\CognitiveConfig;

class ConfigCognitiveExporter implements ReportGeneratorInterface {
    private CognitiveConfig $config;
    
    public function __construct(CognitiveConfig $config) {
        $this->config = $config;
    }
    
    public function export(CognitiveMetricsCollection $metrics, string $filename): void {
        file_put_contents($filename, 'config cognitive data: ' . $this->config->scoreThreshold);
    }
}
PHP;
        file_put_contents($tempFile, $classContent);

        try {
            $customExporters = [
                'config' => [
                    'class' => 'TestConfigCognitive\ConfigCognitiveExporter',
                    'file' => $tempFile,
                ]
            ];

            $factory = new CognitiveReportFactory($this->createMockConfigService($customExporters));
            $exporter = $factory->create('config');

            $this->assertInstanceOf(ReportGeneratorInterface::class, $exporter);
            $this->assertInstanceOf('TestConfigCognitive\ConfigCognitiveExporter', $exporter);
        } finally {
            unlink($tempFile);
        }
    }

    #[Test]
    public function testCreateCustomExporterWithoutFile(): void
    {
        // Create a temporary PHP file and include it manually to simulate autoloading
        $tempFile = tempnam(sys_get_temp_dir(), 'autoloaded_cognitive_exporter_') . '.php';
        $classContent = <<<'PHP'
<?php
namespace TestAutoloadedCognitive;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Report\ReportGeneratorInterface;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;

class AutoloadedCognitiveExporter implements ReportGeneratorInterface {
    public function export(CognitiveMetricsCollection $metrics, string $filename): void {
        file_put_contents($filename, 'autoloaded cognitive data');
    }
}
PHP;
        file_put_contents($tempFile, $classContent);
        require_once $tempFile;

        try {
            $customExporters = [
                'autoloaded' => [
                    'class' => 'TestAutoloadedCognitive\AutoloadedCognitiveExporter',
                    'file' => null,
                ]
            ];

            $factory = new CognitiveReportFactory($this->createMockConfigService($customExporters));
            $exporter = $factory->create('autoloaded');

            $this->assertInstanceOf(ReportGeneratorInterface::class, $exporter);
            $this->assertInstanceOf('TestAutoloadedCognitive\AutoloadedCognitiveExporter', $exporter);
        } finally {
            unlink($tempFile);
        }
    }

    #[Test]
    public function testCreateUnsupportedExporter(): void
    {
        $factory = new CognitiveReportFactory($this->createMockConfigService());

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
                'file' => null,
                'requiresConfig' => false
            ],
            'custom2' => [
                'class' => 'TestCustom2\Exporter',
                'file' => null,
            ]
        ];

        $factory = new CognitiveReportFactory($this->createMockConfigService($customExporters));
        $supportedTypes = $factory->getSupportedTypes();

        $expectedBuiltInTypes = ['json', 'csv', 'html', 'markdown'];
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
                'file' => null,
                'requiresConfig' => false
            ]
        ];

        $factory = new CognitiveReportFactory($this->createMockConfigService($customExporters));

        $this->assertTrue($factory->isSupported('json'));
        $this->assertTrue($factory->isSupported('custom'));
        $this->assertFalse($factory->isSupported('unsupported'));
    }

    #[Test]
    public function testCustomExporterWithInvalidInterface(): void
    {
        // Create a temporary PHP file with a class that doesn't implement the interface
        $tempFile = tempnam(sys_get_temp_dir(), 'invalid_cognitive_exporter_') . '.php';
        $classContent = <<<'PHP'
<?php
namespace TestInvalidCognitive;

class InvalidCognitiveExporter {
    public function export($metrics, $filename): void {
        file_put_contents($filename, 'invalid cognitive data');
    }
}
PHP;
        file_put_contents($tempFile, $classContent);

        try {
            $customExporters = [
                'invalid' => [
                    'class' => 'TestInvalidCognitive\InvalidCognitiveExporter',
                    'file' => $tempFile,
                ]
            ];

            $factory = new CognitiveReportFactory($this->createMockConfigService($customExporters));

            $this->expectException(\Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException::class);
            $this->expectExceptionMessage('Exporter must implement Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Report\ReportGeneratorInterface');

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
                'file' => '/non/existent/file.php',
                'requiresConfig' => false
            ]
        ];

        $factory = new CognitiveReportFactory($this->createMockConfigService($customExporters));

        $this->expectException(\Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException::class);
        $this->expectExceptionMessage('Exporter file not found: /non/existent/file.php');

        $factory->create('missing');
    }

    #[Test]
    public function testCustomExporterRequiresConfigButConfigIsNull(): void
    {
        // Create a temporary PHP file with a custom exporter that requires config
        $tempFile = tempnam(sys_get_temp_dir(), 'null_config_exporter_') . '.php';
        $classContent = <<<'PHP'
<?php
namespace TestNullConfigCognitive;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Report\ReportGeneratorInterface;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\Config\CognitiveConfig;

class NullConfigCognitiveExporter implements ReportGeneratorInterface {
    private ?CognitiveConfig $config;
    
    public function __construct(?CognitiveConfig $config = null) {
        $this->config = $config;
    }
    
    public function export(CognitiveMetricsCollection $metrics, string $filename): void {
        file_put_contents($filename, 'null config cognitive data');
    }
}
PHP;
        file_put_contents($tempFile, $classContent);

        try {
            $customExporters = [
                'nullconfig' => [
                    'class' => 'TestNullConfigCognitive\NullConfigCognitiveExporter',
                    'file' => $tempFile,
 // This should create without config
                ]
            ];

            $factory = new CognitiveReportFactory($this->createMockConfigService($customExporters));
            $exporter = $factory->create('nullconfig');

            $this->assertInstanceOf(ReportGeneratorInterface::class, $exporter);
            $this->assertInstanceOf('TestNullConfigCognitive\NullConfigCognitiveExporter', $exporter);
        } finally {
            unlink($tempFile);
        }
    }
}
