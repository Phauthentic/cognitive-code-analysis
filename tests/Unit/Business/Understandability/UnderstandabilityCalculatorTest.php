<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business\Understandability;

use Phauthentic\CognitiveCodeAnalysis\Business\Understandability\UnderstandabilityCalculator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UnderstandabilityCalculatorTest extends TestCase
{
    private UnderstandabilityCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new UnderstandabilityCalculator();
    }

    #[DataProvider('riskLevelProvider')]
    public function testGetRiskLevel(int $complexity, string $expected): void
    {
        $this->assertSame($expected, $this->calculator->getRiskLevel($complexity));
    }

    /**
     * @return array<string, array{int, string}>
     */
    public static function riskLevelProvider(): array
    {
        return [
            'zero' => [0, 'low'],
            'low boundary' => [5, 'low'],
            'medium' => [10, 'medium'],
            'high' => [15, 'high'],
            'very high' => [16, 'very_high'],
        ];
    }

    public function testCreateSummaryFlagsHighRiskMethods(): void
    {
        $summary = $this->calculator->createSummary(
            [
                '\\App\\A::low' => 3,
                '\\App\\B::high' => 12,
                '\\App\\C::veryHigh' => 20,
            ],
            [],
        );

        $this->assertArrayHasKey('\\App\\B::high', $summary['high_risk_methods']);
        $this->assertArrayHasKey('\\App\\C::veryHigh', $summary['very_high_risk_methods']);
        $this->assertSame('high', $summary['methods']['\\App\\B::high']['risk_level']);
    }
}
