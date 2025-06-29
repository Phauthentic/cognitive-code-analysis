<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business\Cognitive;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetrics;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\ScoreCalculator;
use Phauthentic\CognitiveCodeAnalysis\Config\ConfigLoader;
use Phauthentic\CognitiveCodeAnalysis\Config\ConfigService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

/**
 *
 */
class ScoreCalculatorTest extends TestCase
{
    private ScoreCalculator $scoreCalculator;
    private CognitiveMetrics $metrics;

    protected function setUp(): void
    {
        $this->scoreCalculator = new ScoreCalculator();
        $this->metrics = new CognitiveMetrics([
            'class' => 'Test',
            'method' => 'test',
            'file' => 'test.php',
            'lineCount' => 10,
            'argCount' => 5,
            'returnCount' => 2,
            'variableCount' => 3,
            'propertyCallCount' => 2,
            'ifCount' => 3,
            'ifNestingLevel' => 2,
            'elseCount' => 2,
        ]);
    }

    #[Test]
    public function testCalculate(): void
    {
        $config = (new ConfigService(
            new Processor(),
            new ConfigLoader()
        ))->getConfig();

        $this->scoreCalculator->calculate($this->metrics, $config);

        // Assert the final score
        $this->assertGreaterThan(0, $this->metrics->getScore());

        // Assert the score is a float
        $this->assertIsFloat($this->metrics->getScore());
    }
}
