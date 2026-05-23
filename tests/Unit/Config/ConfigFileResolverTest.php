<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Config;

use Phauthentic\CognitiveCodeAnalysis\Config\ConfigFileResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ConfigFileResolverTest extends TestCase
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
    public function resolveReturnsExplicitPathWhenProvided(): void
    {
        $resolver = new ConfigFileResolver();

        $this->assertSame('/custom/config.yaml', $resolver->resolve('/custom/config.yaml'));
    }

    #[Test]
    public function resolveAutoDiscoversCcaYamlInWorkingDirectory(): void
    {
        $tempDir = sys_get_temp_dir() . '/cca-resolver-' . uniqid('', true);
        mkdir($tempDir);
        chdir($tempDir);

        $configPath = $tempDir . DIRECTORY_SEPARATOR . ConfigFileResolver::DEFAULT_FILENAME;
        file_put_contents($configPath, 'cognitive: {}');

        $resolver = new ConfigFileResolver();

        $this->assertSame($configPath, $resolver->resolve(null));

        unlink($configPath);
        rmdir($tempDir);
    }

    #[Test]
    public function resolveReturnsNullWhenNoExplicitPathOrDefaultFile(): void
    {
        $tempDir = sys_get_temp_dir() . '/cca-resolver-empty-' . uniqid('', true);
        mkdir($tempDir);
        chdir($tempDir);

        $resolver = new ConfigFileResolver();

        $this->assertNull($resolver->resolve(null));

        rmdir($tempDir);
    }

    #[Test]
    public function getDefaultPathReturnsCcaYamlInWorkingDirectory(): void
    {
        $tempDir = sys_get_temp_dir() . '/cca-resolver-default-' . uniqid('', true);
        mkdir($tempDir);
        chdir($tempDir);

        $resolver = new ConfigFileResolver();

        $this->assertSame(
            $tempDir . DIRECTORY_SEPARATOR . ConfigFileResolver::DEFAULT_FILENAME,
            $resolver->getDefaultPath()
        );

        rmdir($tempDir);
    }
}
