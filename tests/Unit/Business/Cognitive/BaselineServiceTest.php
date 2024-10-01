<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business\Cognitive;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\BaselineService;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetrics;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use JsonException;

/**
 *
 */
class BaselineServiceTest extends TestCase
{
    protected BaselineService $baselineService;

    protected function setUp(): void
    {
        $this->baselineService = new BaselineService();
    }

    public function testLoadBaselineThrowsExceptionIfFileDoesNotExist(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Baseline file does not exist.');

        $this->baselineService->loadBaseline('non_existent_file.json');
    }

    public function testLoadBaselineThrowsExceptionIfInvalidJson(): void
    {
        $filePath = tempnam(sys_get_temp_dir(), 'baseline');
        file_put_contents($filePath, 'invalid json');

        $this->expectException(JsonException::class);

        try {
            $this->baselineService->loadBaseline($filePath);
        } finally {
            unlink($filePath);
        }
    }

    public function testLoadBaselineSuccess(): void
    {
        $filePath = tempnam(sys_get_temp_dir(), 'baseline');
        $baselineData = [
            'TestClass' => [
                'methods' => [
                    'testMethod' => [
                        'complexity' => 8,
                        'size' => 18
                    ]
                ]
            ]
        ];

        file_put_contents($filePath, json_encode($baselineData));

        $result = $this->baselineService->loadBaseline($filePath);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('TestClass', $result);
        $this->assertArrayHasKey('methods', $result['TestClass']);
        $this->assertArrayHasKey('testMethod', $result['TestClass']['methods']);

        unlink($filePath); // Clean up
    }
}
