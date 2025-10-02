<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business\CodeCoverage;

use Phauthentic\CognitiveCodeAnalysis\Business\CodeCoverage\CloverReader;
use Phauthentic\CognitiveCodeAnalysis\Business\CodeCoverage\CoverageDetails;
use Phauthentic\CognitiveCodeAnalysis\Business\CodeCoverage\MethodCoverage;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;
use PHPUnit\Framework\TestCase;

class CloverReaderTest extends TestCase
{
    private string $testCoverageFile;

    protected function setUp(): void
    {
        $this->testCoverageFile = __DIR__ . '/../../../../coverage-clover.xml';
    }

    public function testConstructorThrowsExceptionWhenFileNotFound(): void
    {
        $this->expectException(CognitiveAnalysisException::class);
        $this->expectExceptionMessage('Coverage file not found');

        new CloverReader('/path/to/nonexistent/file.xml');
    }

    public function testConstructorThrowsExceptionWhenFileIsNotValidXml(): void
    {
        $invalidXmlFile = sys_get_temp_dir() . '/invalid.xml';
        file_put_contents($invalidXmlFile, 'not valid xml');

        $this->expectException(CognitiveAnalysisException::class);
        $this->expectExceptionMessage('Failed to parse coverage XML file');

        try {
            new CloverReader($invalidXmlFile);
        } finally {
            unlink($invalidXmlFile);
        }
    }

    public function testGetLineCoverageReturnsCorrectValue(): void
    {
        if (!file_exists($this->testCoverageFile)) {
            $this->markTestSkipped('Coverage file not found: ' . $this->testCoverageFile);
        }

        $reader = new CloverReader($this->testCoverageFile);

        // Test with a class that exists in the coverage file
        $coverage = $reader->getLineCoverage('Phauthentic\CognitiveCodeAnalysis\Application');

        $this->assertIsFloat($coverage);
        $this->assertGreaterThanOrEqual(0.0, $coverage);
        $this->assertLessThanOrEqual(1.0, $coverage);

        // Based on the XML: statements="150" coveredstatements="145"
        $expectedCoverage = 145 / 150;
        $this->assertEqualsWithDelta($expectedCoverage, $coverage, 0.01);
    }

    public function testGetLineCoverageReturnsNullForNonExistentClass(): void
    {
        if (!file_exists($this->testCoverageFile)) {
            $this->markTestSkipped('Coverage file not found: ' . $this->testCoverageFile);
        }

        $reader = new CloverReader($this->testCoverageFile);
        $coverage = $reader->getLineCoverage('NonExistent\Class');

        $this->assertNull($coverage);
    }

    public function testGetLineCoverageReturnsZeroForClassWithNoStatements(): void
    {
        if (!file_exists($this->testCoverageFile)) {
            $this->markTestSkipped('Coverage file not found: ' . $this->testCoverageFile);
        }

        $reader = new CloverReader($this->testCoverageFile);

        // CognitiveAnalysisException has statements="0" coveredstatements="0"
        $coverage = $reader->getLineCoverage('Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException');

        $this->assertIsFloat($coverage);
        $this->assertEquals(0.0, $coverage);
    }

    public function testGetBranchCoverageReturnsZeroForCloverFormat(): void
    {
        if (!file_exists($this->testCoverageFile)) {
            $this->markTestSkipped('Coverage file not found: ' . $this->testCoverageFile);
        }

        $reader = new CloverReader($this->testCoverageFile);

        // Clover format typically has conditionals="0"
        $coverage = $reader->getBranchCoverage('Phauthentic\CognitiveCodeAnalysis\Application');

        $this->assertIsFloat($coverage);
        $this->assertEquals(0.0, $coverage);
    }

    public function testGetBranchCoverageReturnsNullForNonExistentClass(): void
    {
        if (!file_exists($this->testCoverageFile)) {
            $this->markTestSkipped('Coverage file not found: ' . $this->testCoverageFile);
        }

        $reader = new CloverReader($this->testCoverageFile);
        $coverage = $reader->getBranchCoverage('NonExistent\Class');

        $this->assertNull($coverage);
    }

    public function testGetComplexityReturnsCorrectValue(): void
    {
        if (!file_exists($this->testCoverageFile)) {
            $this->markTestSkipped('Coverage file not found: ' . $this->testCoverageFile);
        }

        $reader = new CloverReader($this->testCoverageFile);

        // Application has complexity="15"
        $complexity = $reader->getComplexity('Phauthentic\CognitiveCodeAnalysis\Application');

        $this->assertIsInt($complexity);
        $this->assertEquals(15, $complexity);
    }

    public function testGetComplexityReturnsNullForNonExistentClass(): void
    {
        if (!file_exists($this->testCoverageFile)) {
            $this->markTestSkipped('Coverage file not found: ' . $this->testCoverageFile);
        }

        $reader = new CloverReader($this->testCoverageFile);
        $complexity = $reader->getComplexity('NonExistent\Class');

        $this->assertNull($complexity);
    }

    public function testGetCoverageDetailsReturnsCorrectData(): void
    {
        if (!file_exists($this->testCoverageFile)) {
            $this->markTestSkipped('Coverage file not found: ' . $this->testCoverageFile);
        }

        $reader = new CloverReader($this->testCoverageFile);
        $details = $reader->getCoverageDetails('Phauthentic\CognitiveCodeAnalysis\Application');

        $this->assertInstanceOf(CoverageDetails::class, $details);
        $this->assertEquals('Phauthentic\CognitiveCodeAnalysis\Application', $details->getName());
        $this->assertStringContainsString('Application.php', $details->getFilename());
        $this->assertEqualsWithDelta(145 / 150, $details->getLineRate(), 0.01);
        $this->assertEquals(0.0, $details->getBranchRate());
        $this->assertEquals(15, $details->getComplexity());

        $methods = $details->getMethods();
        $this->assertIsArray($methods);
        $this->assertGreaterThan(0, count($methods));

        // Check that methods are MethodCoverage instances
        foreach ($methods as $method) {
            $this->assertInstanceOf(MethodCoverage::class, $method);
        }
    }

    public function testGetCoverageDetailsReturnsNullForNonExistentClass(): void
    {
        if (!file_exists($this->testCoverageFile)) {
            $this->markTestSkipped('Coverage file not found: ' . $this->testCoverageFile);
        }

        $reader = new CloverReader($this->testCoverageFile);
        $details = $reader->getCoverageDetails('NonExistent\Class');

        $this->assertNull($details);
    }

    public function testGetAllClassesReturnsArrayOfFqcns(): void
    {
        if (!file_exists($this->testCoverageFile)) {
            $this->markTestSkipped('Coverage file not found: ' . $this->testCoverageFile);
        }

        $reader = new CloverReader($this->testCoverageFile);
        $classes = $reader->getAllClasses();

        $this->assertIsArray($classes);
        $this->assertGreaterThan(0, count($classes));

        // Verify all elements are strings (FQCNs)
        foreach ($classes as $fqcn) {
            $this->assertIsString($fqcn);
            $this->assertStringContainsString('\\', $fqcn);
        }

        // Verify known classes are in the list
        $this->assertContains('Phauthentic\CognitiveCodeAnalysis\Application', $classes);
        $this->assertContains('Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException', $classes);
    }

    public function testResultsAreCached(): void
    {
        if (!file_exists($this->testCoverageFile)) {
            $this->markTestSkipped('Coverage file not found: ' . $this->testCoverageFile);
        }

        $reader = new CloverReader($this->testCoverageFile);

        // First call
        $coverage1 = $reader->getLineCoverage('Phauthentic\CognitiveCodeAnalysis\Application');

        // Second call (should use cache)
        $coverage2 = $reader->getLineCoverage('Phauthentic\CognitiveCodeAnalysis\Application');

        $this->assertEquals($coverage1, $coverage2);
    }

    public function testMethodCoverageExtraction(): void
    {
        if (!file_exists($this->testCoverageFile)) {
            $this->markTestSkipped('Coverage file not found: ' . $this->testCoverageFile);
        }

        $reader = new CloverReader($this->testCoverageFile);
        $details = $reader->getCoverageDetails('Phauthentic\CognitiveCodeAnalysis\Application');

        $this->assertNotNull($details);
        $methods = $details->getMethods();

        // Verify __construct method exists
        $this->assertArrayHasKey('__construct', $methods);
        $constructMethod = $methods['__construct'];

        $this->assertEquals('__construct', $constructMethod->getName());
        $this->assertEquals(1, $constructMethod->getComplexity());
        $this->assertIsFloat($constructMethod->getLineRate());
        $this->assertGreaterThanOrEqual(0.0, $constructMethod->getLineRate());
        $this->assertLessThanOrEqual(1.0, $constructMethod->getLineRate());
    }
}
