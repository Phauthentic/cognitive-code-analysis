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
            'line_count' => 10,
            'arg_count' => 5,
            'return_count' => 2,
            'variable_count' => 3,
            'property_call_count' => 2,
            'if_count' => 3,
            'if_nesting_level' => 2,
            'else_count' => 2,
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
