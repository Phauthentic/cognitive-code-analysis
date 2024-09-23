<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics\Tests\Unit\Business\Cognitive;

use Phauthentic\CodeQualityMetrics\Business\Cognitive\CognitiveMetrics;
use Phauthentic\CodeQualityMetrics\Business\Cognitive\ScoreCalculator;
use Phauthentic\CodeQualityMetrics\Config\ConfigLoader;
use Phauthentic\CodeQualityMetrics\Config\ConfigService;
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

    public function testCalculate(): void
    {
        $config = (new ConfigService())->getConfig();

        $this->scoreCalculator->calculate($this->metrics, $config['cognitive']);

        // Assert the final score
        $this->assertGreaterThan(0, $this->metrics->getScore());

        // Assert the score is a float
        $this->assertIsFloat($this->metrics->getScore());
    }
}
