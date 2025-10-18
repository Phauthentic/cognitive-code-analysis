<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business\Cognitive;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Baseline\Baseline;
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
                        'class' => 'TestClass',
                        'method' => 'testMethod',
                        'file' => 'TestClass.php',
                        'line' => 10,
                        'lineCount' => 5,
                        'argCount' => 2,
                        'returnCount' => 1,
                        'variableCount' => 3,
                        'propertyCallCount' => 1,
                        'ifCount' => 2,
                        'ifNestingLevel' => 1,
                        'elseCount' => 1,
                        'lineCountWeight' => 0.5,
                        'argCountWeight' => 0.3,
                        'returnCountWeight' => 0.2,
                        'variableCountWeight' => 0.4,
                        'propertyCallCountWeight' => 0.1,
                        'ifCountWeight' => 0.6,
                        'ifNestingLevelWeight' => 0.7,
                        'elseCountWeight' => 0.2,
                        'score' => 8.5
                    ]
                ]
            ]
        ];

        file_put_contents($filePath, json_encode($baselineData, JSON_THROW_ON_ERROR));

        $result = $this->baselineService->loadBaseline($filePath);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('metrics', $result);
        $this->assertArrayHasKey('baselineFile', $result);
        $this->assertArrayHasKey('warnings', $result);
        $this->assertArrayHasKey('TestClass', $result['metrics']);
        $this->assertArrayHasKey('methods', $result['metrics']['TestClass']);
        $this->assertArrayHasKey('testMethod', $result['metrics']['TestClass']['methods']);

        unlink($filePath); // Clean up
    }
}
