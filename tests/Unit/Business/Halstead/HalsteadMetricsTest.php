<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics\Tests\Unit\Business\Halstead;

use InvalidArgumentException;
use Phauthentic\CodeQualityMetrics\Business\Halstead\HalsteadMetrics;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class HalsteadMetricsTest extends TestCase
{
    public function testConstructorWithValidMetrics(): void
    {
        $metrics = [
            'n1' => 10,
            'n2' => 20,
            'N1' => 30,
            'N2' => 40,
            'class' => 'Test\ClassName',
            'file' => '/path/to/file.php'
        ];

        $halsteadMetrics = new HalsteadMetrics($metrics);

        $this->assertSame(10, $halsteadMetrics->getN1());
        $this->assertSame(20, $halsteadMetrics->getN2());
        $this->assertSame(30, $halsteadMetrics->getTotalOperators());
        $this->assertSame(40, $halsteadMetrics->getTotalOperands());
        $this->assertSame(70, $halsteadMetrics->getProgramLength());
        $this->assertSame(30, $halsteadMetrics->getProgramVocabulary());
        $this->assertEquals(70 * log(30, 2), $halsteadMetrics->getVolume());
        $this->assertEquals((10 / 2) * (40 / 20), $halsteadMetrics->getDifficulty());
        $this->assertEquals($halsteadMetrics->getDifficulty() * $halsteadMetrics->getVolume(), $halsteadMetrics->getEffort());
        $this->assertSame('Test\ClassName', $halsteadMetrics->getClass());
        $this->assertSame('/path/to/file.php', $halsteadMetrics->getFile());
    }

    public function testConstructorThrowsExceptionForMissingKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required key: n1');

        $metrics = [
            'n2' => 20,
            'N1' => 30,
            'N2' => 40,
        ];

        new HalsteadMetrics($metrics); // This should throw an exception
    }

    public function testToArrayMethod(): void
    {
        $metrics = [
            'n1' => 5,
            'n2' => 15,
            'N1' => 25,
            'N2' => 35,
            'class' => 'Another\ClassName',
            'file' => '/another/path/to/file.php'
        ];

        $halsteadMetrics = new HalsteadMetrics($metrics);

        $expectedArray = [
            'n1' => 5,
            'n2' => 15,
            'N1' => 25,
            'N2' => 35,
            'program_length' => 60,
            'program_vocabulary' => 20,
            'volume' => 60 * log(20, 2),
            'difficulty' => (5 / 2) * (35 / 15),
            'effort' => ((5 / 2) * (35 / 15)) * (60 * log(20, 2)),
            'class' => 'Another\ClassName',
            'file' => '/another/path/to/file.php',
            'possible_bugs' => 0.08643856189774725
        ];

        $this->assertEquals($expectedArray, $halsteadMetrics->toArray());
    }

    public function testJsonSerializeMethod(): void
    {
        $metrics = [
            'n1' => 7,
            'n2' => 14,
            'N1' => 21,
            'N2' => 28,
            'class' => 'Sample\Class',
            'file' => '/sample/path.php'
        ];

        $halsteadMetrics = new HalsteadMetrics($metrics);

        $expectedJson = json_encode([
            'n1' => 7,
            'n2' => 14,
            'N1' => 21,
            'N2' => 28,
            'possible_bugs' => 0.07174118457205308,
            'program_length' => 49,
            'program_vocabulary' => 21,
            'volume' => 49 * log(21, 2),
            'difficulty' => (7 / 2) * (28 / 14),
            'effort' => ((7 / 2) * (28 / 14)) * (49 * log(21, 2)),
            'class' => 'Sample\Class',
            'file' => '/sample/path.php'
        ], JSON_THROW_ON_ERROR);

        $this->assertJsonStringEqualsJsonString($expectedJson, json_encode($halsteadMetrics));
    }

    public function testEqualsMethod(): void
    {
        $metrics1 = [
            'n1' => 3,
            'n2' => 6,
            'N1' => 9,
            'N2' => 12,
            'class' => 'TestClass',
            'file' => '/test/file.php'
        ];

        $metrics2 = [
            'n1' => 3,
            'n2' => 6,
            'N1' => 9,
            'N2' => 12,
            'class' => 'TestClass',
            'file' => '/test/file.php'
        ];

        $halsteadMetrics1 = new HalsteadMetrics($metrics1);
        $halsteadMetrics2 = new HalsteadMetrics($metrics2);

        $this->assertTrue($halsteadMetrics1->equals($halsteadMetrics2));

        $metrics3 = [
            'n1' => 5,
            'n2' => 10,
            'N1' => 15,
            'N2' => 20,
            'class' => 'DifferentClass',
            'file' => '/different/file.php'
        ];

        $halsteadMetrics3 = new HalsteadMetrics($metrics3);

        $this->assertFalse($halsteadMetrics1->equals($halsteadMetrics3));
    }
}
