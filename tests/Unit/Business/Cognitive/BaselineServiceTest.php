<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business\Cognitive;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Baseline;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use JsonException;

class BaselineServiceTest extends TestCase
{
    protected Baseline $baselineService;

    protected function setUp(): void
    {
        $this->baselineService = new Baseline();
    }

    #[Test]
    public function testLoadBaselineThrowsExceptionIfFileDoesNotExist(): void
    {
        $this->expectException(CognitiveAnalysisException::class);
        $this->expectExceptionMessage('Baseline file does not exist.');

        $this->baselineService->loadBaseline('non_existent_file.json');
    }

    #[Test]
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

    /**
     * @throws CognitiveAnalysisException
     * @throws JsonException
     */
    #[Test]
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

        file_put_contents($filePath, json_encode($baselineData, JSON_THROW_ON_ERROR));

        $result = $this->baselineService->loadBaseline($filePath);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('TestClass', $result);
        $this->assertArrayHasKey('methods', $result['TestClass']);
        $this->assertArrayHasKey('testMethod', $result['TestClass']['methods']);

        unlink($filePath); // Clean up
    }
}
