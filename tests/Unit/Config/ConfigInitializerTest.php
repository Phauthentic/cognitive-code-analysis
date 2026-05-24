<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Config;

use Phauthentic\CognitiveCodeAnalysis\Config\ConfigInitializer;
use Phauthentic\CognitiveCodeAnalysis\Config\ConfigLoader;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;

class ConfigInitializerTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/config-initializer-' . uniqid('', true);
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    #[Test]
    public function createDefaultConfigReturnsSchemaValidDefaults(): void
    {
        $initializer = $this->createInitializer();
        $config = $initializer->createDefaultConfig();

        $this->assertArrayHasKey('cognitive', $config);
        $this->assertSame(0.5, $config['cognitive']['scoreThreshold']);
        $this->assertTrue($config['cognitive']['groupByClass']);
        $this->assertArrayHasKey('metrics', $config['cognitive']);
        $this->assertArrayHasKey('lineCount', $config['cognitive']['metrics']);
    }

    #[Test]
    public function createDefaultConfigAppliesOverrides(): void
    {
        $initializer = $this->createInitializer();
        $config = $initializer->createDefaultConfig([
            'cognitive' => [
                'scoreThreshold' => 0.8,
                'groupByClass' => false,
            ],
        ]);

        $this->assertSame(0.8, $config['cognitive']['scoreThreshold']);
        $this->assertFalse($config['cognitive']['groupByClass']);
    }

    #[Test]
    public function writeConfigFileCreatesFileAndParentDirectory(): void
    {
        $initializer = $this->createInitializer();
        $config = $initializer->createDefaultConfig();
        $targetPath = $this->tempDir . '/nested/cca.yaml';

        $initializer->writeConfigFile($targetPath, $config);

        $this->assertFileExists($targetPath);
        $parsed = Yaml::parseFile($targetPath);
        $this->assertSame(0.5, $parsed['cognitive']['scoreThreshold']);
    }

    private function createInitializer(): ConfigInitializer
    {
        return new ConfigInitializer(
            new Processor(),
            new ConfigLoader(),
            __DIR__ . '/../../../phpcca.yaml',
        );
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
