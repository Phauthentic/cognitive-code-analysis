<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Command\Presentation;

use Phauthentic\CognitiveCodeAnalysis\Command\Presentation\RuntimeStatusRenderer;
use Phauthentic\CognitiveCodeAnalysis\Config\CacheConfig;
use Phauthentic\CognitiveCodeAnalysis\Config\CognitiveConfig;
use Phauthentic\CognitiveCodeAnalysis\Config\MetricsConfig;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

class RuntimeStatusRendererTest extends TestCase
{
    private string $originalWorkingDirectory;

    protected function setUp(): void
    {
        $this->originalWorkingDirectory = (string) getcwd();
    }

    protected function tearDown(): void
    {
        chdir($this->originalWorkingDirectory);
    }

    #[Test]
    public function renderShowsBuiltInConfigAndEnabledCache(): void
    {
        $output = new BufferedOutput();
        $renderer = new RuntimeStatusRenderer();

        $renderer->render($output, null, $this->createConfig(cacheEnabled: true));

        $this->assertSame(
            "Config: built-in\nCache: enabled\n\n",
            $output->fetch()
        );
    }

    #[Test]
    public function renderShowsConfigPathAndDisabledCache(): void
    {
        $tempDir = sys_get_temp_dir() . '/runtime-status-' . uniqid('', true);
        mkdir($tempDir);
        chdir($tempDir);

        $output = new BufferedOutput();
        $renderer = new RuntimeStatusRenderer();

        $renderer->render($output, $tempDir . '/cca.yaml', $this->createConfig(cacheEnabled: false));

        $this->assertSame(
            "Config: ./cca.yaml\nCache: disabled\n\n",
            $output->fetch()
        );

        rmdir($tempDir);
    }

    #[Test]
    public function renderShowsAbsoluteConfigPathWhenOutsideWorkingDirectory(): void
    {
        $output = new BufferedOutput();
        $renderer = new RuntimeStatusRenderer();

        $renderer->render($output, '/etc/phpcca/custom.yaml', $this->createConfig(cacheEnabled: false));

        $this->assertSame(
            "Config: /etc/phpcca/custom.yaml\nCache: disabled\n\n",
            $output->fetch()
        );
    }

    private function createConfig(bool $cacheEnabled): CognitiveConfig
    {
        return new CognitiveConfig(
            excludeFilePatterns: [],
            excludePatterns: [],
            metrics: [
                'lineCount' => new MetricsConfig(60, 25.0, true),
            ],
            showOnlyMethodsExceedingThreshold: false,
            scoreThreshold: 0.5,
            cache: new CacheConfig($cacheEnabled, './.phpcca.cache'),
        );
    }
}
