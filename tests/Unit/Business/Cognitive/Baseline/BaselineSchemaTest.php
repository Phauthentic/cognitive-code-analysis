<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business\Cognitive\Baseline;

use JsonSchema\Validator;
use Phauthentic\CognitiveCodeAnalysis\Application;
use Phauthentic\CognitiveCodeAnalysis\Command\CognitiveMetricsCommand;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Guards schemas/baseline.json against drift from what BaselineFile writes.
 */
class BaselineSchemaTest extends TestCase
{
    private string $tmpDir;
    private string $originalCwd;

    protected function setUp(): void
    {
        $this->originalCwd = (string)getcwd();
        $this->tmpDir = sys_get_temp_dir() . '/phpcca-baseline-schema-' . uniqid();
        mkdir($this->tmpDir);
        // The command writes the baseline to ./.phpcca/baseline/ relative to the cwd.
        chdir($this->tmpDir);
    }

    protected function tearDown(): void
    {
        chdir($this->originalCwd);
        (new Filesystem())->remove($this->tmpDir);
    }

    /**
     * @return array<string, array{bool}>
     */
    public static function metricsProvider(): array
    {
        return [
            'halstead and cyclomatic enabled' => [true],
            'halstead and cyclomatic disabled' => [false],
        ];
    }

    #[DataProvider('metricsProvider')]
    public function testGeneratedBaselineValidatesAgainstSchema(bool $extraMetrics): void
    {
        $flag = $extraMetrics ? 'true' : 'false';
        $config = (string)file_get_contents(__DIR__ . '/../../../Command/minimal-config.yml');
        $config = preg_replace('/^(\s+show(?:Halstead|Cyclomatic)Complexity:).*$/m', '$1 ' . $flag, $config);
        file_put_contents($this->tmpDir . '/config.yml', $config);

        $command = (new Application())->getContainer()->get(CognitiveMetricsCommand::class);
        $tester = new CommandTester($command);
        $tester->execute([
            'path' => __DIR__ . '/../../../../../src/Config',
            '--config' => $this->tmpDir . '/config.yml',
            '--generate-baseline' => true,
        ]);

        $files = glob($this->tmpDir . '/.phpcca/baseline/*.json') ?: [];
        $this->assertCount(1, $files);
        $data = json_decode((string)file_get_contents($files[0]));

        $schema = json_decode((string)file_get_contents(__DIR__ . '/../../../../../schemas/baseline.json'));
        $validator = new Validator();
        $validator->validate($data, $schema);

        $this->assertTrue($validator->isValid(), json_encode($validator->getErrors(), JSON_PRETTY_PRINT) ?: '');
    }
}
