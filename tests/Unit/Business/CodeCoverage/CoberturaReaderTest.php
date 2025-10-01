<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business\CodeCoverage;

use Phauthentic\CognitiveCodeAnalysis\Business\CodeCoverage\CoberturaReader;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class CoberturaReaderTest extends TestCase
{
    private string $testCoverageFile;
    private CoberturaReader $reader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testCoverageFile = $this->createTestCoverageFile();
        $this->reader = new CoberturaReader($this->testCoverageFile);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testCoverageFile)) {
            unlink($this->testCoverageFile);
        }

        parent::tearDown();
    }

    public function testConstructorThrowsExceptionForNonExistentFile(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Coverage file not found');

        new CoberturaReader('/non/existent/file.xml');
    }

    public function testConstructorThrowsExceptionForInvalidXml(): void
    {
        $invalidFile = tempnam(sys_get_temp_dir(), 'invalid_xml');
        file_put_contents($invalidFile, 'This is not valid XML');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to parse coverage XML file');

        try {
            new CoberturaReader($invalidFile);
        } finally {
            unlink($invalidFile);
        }
    }

    public function testGetLineCoverageReturnsCorrectValue(): void
    {
        $coverage = $this->reader->getLineCoverage('App\TestClass');

        $this->assertIsFloat($coverage);
        $this->assertEquals(0.85, $coverage);
    }

    public function testGetLineCoverageReturnsNullForNonExistentClass(): void
    {
        $coverage = $this->reader->getLineCoverage('App\NonExistentClass');

        $this->assertNull($coverage);
    }

    public function testGetBranchCoverageReturnsCorrectValue(): void
    {
        $coverage = $this->reader->getBranchCoverage('App\TestClass');

        $this->assertIsFloat($coverage);
        $this->assertEquals(0.75, $coverage);
    }

    public function testGetBranchCoverageReturnsNullForNonExistentClass(): void
    {
        $coverage = $this->reader->getBranchCoverage('App\NonExistentClass');

        $this->assertNull($coverage);
    }

    public function testGetComplexityReturnsCorrectValue(): void
    {
        $complexity = $this->reader->getComplexity('App\TestClass');

        $this->assertIsInt($complexity);
        $this->assertEquals(10, $complexity);
    }

    public function testGetComplexityReturnsNullForNonExistentClass(): void
    {
        $complexity = $this->reader->getComplexity('App\NonExistentClass');

        $this->assertNull($complexity);
    }

    public function testGetCoverageDetailsReturnsCompleteInformation(): void
    {
        $details = $this->reader->getCoverageDetails('App\TestClass');

        $this->assertInstanceOf(\Phauthentic\CognitiveCodeAnalysis\Business\CodeCoverage\CoverageDetails::class, $details);
        $this->assertEquals('App\TestClass', $details->getName());
        $this->assertEquals('src/TestClass.php', $details->getFilename());
        $this->assertEquals(0.85, $details->getLineRate());
        $this->assertEquals(0.75, $details->getBranchRate());
        $this->assertEquals(10, $details->getComplexity());
        $this->assertIsArray($details->getMethods());
        $this->assertArrayHasKey('testMethod', $details->getMethods());
    }

    public function testGetCoverageDetailsReturnsNullForNonExistentClass(): void
    {
        $details = $this->reader->getCoverageDetails('App\NonExistentClass');

        $this->assertNull($details);
    }

    public function testGetAllClassesReturnsListOfFqcns(): void
    {
        $classes = $this->reader->getAllClasses();

        $this->assertIsArray($classes);
        $this->assertContains('App\TestClass', $classes);
        $this->assertContains('App\AnotherClass', $classes);
        $this->assertCount(2, $classes);
    }

    public function testGetCoverageDetailsIncludesMethodsCoverage(): void
    {
        $details = $this->reader->getCoverageDetails('App\TestClass');

        $methods = $details->getMethods();
        $this->assertIsArray($methods);
        $this->assertArrayHasKey('testMethod', $methods);
        $method = $methods['testMethod'];
        $this->assertInstanceOf(\Phauthentic\CognitiveCodeAnalysis\Business\CodeCoverage\MethodCoverage::class, $method);
        $this->assertEquals(1.0, $method->getLineRate());
        $this->assertEquals(0.5, $method->getBranchRate());
        $this->assertEquals(3, $method->getComplexity());
    }

    public function testCacheIsUsedForRepeatedQueries(): void
    {
        // First call
        $coverage1 = $this->reader->getLineCoverage('App\TestClass');

        // Second call should use cache
        $coverage2 = $this->reader->getLineCoverage('App\TestClass');

        $this->assertEquals($coverage1, $coverage2);
    }

    public function testHandlesClassNameWithBackslashes(): void
    {
        $coverage = $this->reader->getLineCoverage('App\TestClass');

        $this->assertNotNull($coverage);
        $this->assertIsFloat($coverage);
    }

    private function createTestCoverageFile(): string
    {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE coverage SYSTEM "http://cobertura.sourceforge.net/xml/coverage-04.dtd">
<coverage line-rate="0.80" branch-rate="0.70" lines-covered="800" lines-valid="1000" branches-covered="70" branches-valid="100" complexity="50" version="0.4" timestamp="1234567890">
  <sources>
    <source>/home/user/project/src</source>
  </sources>
  <packages>
    <package name="App" line-rate="0.80" branch-rate="0.70" complexity="30">
      <classes>
        <class name="App\TestClass" filename="src/TestClass.php" line-rate="0.85" branch-rate="0.75" complexity="10">
          <methods>
            <method name="testMethod" signature="" line-rate="1.0" branch-rate="0.5" complexity="3">
              <lines>
                <line number="10" hits="5"/>
                <line number="11" hits="5"/>
                <line number="12" hits="5"/>
              </lines>
            </method>
            <method name="anotherMethod" signature="" line-rate="0.8" branch-rate="0.6" complexity="5">
              <lines>
                <line number="20" hits="4"/>
                <line number="21" hits="4"/>
                <line number="22" hits="0"/>
                <line number="23" hits="4"/>
                <line number="24" hits="4"/>
              </lines>
            </method>
          </methods>
          <lines>
            <line number="10" hits="5"/>
            <line number="11" hits="5"/>
            <line number="12" hits="5"/>
            <line number="20" hits="4"/>
            <line number="21" hits="4"/>
            <line number="22" hits="0"/>
            <line number="23" hits="4"/>
            <line number="24" hits="4"/>
          </lines>
        </class>
        <class name="App\AnotherClass" filename="src/AnotherClass.php" line-rate="0.75" branch-rate="0.65" complexity="20">
          <methods>
            <method name="someMethod" signature="" line-rate="0.75" branch-rate="0.65" complexity="20">
              <lines>
                <line number="15" hits="3"/>
                <line number="16" hits="3"/>
                <line number="17" hits="0"/>
                <line number="18" hits="3"/>
              </lines>
            </method>
          </methods>
          <lines>
            <line number="15" hits="3"/>
            <line number="16" hits="3"/>
            <line number="17" hits="0"/>
            <line number="18" hits="3"/>
          </lines>
        </class>
      </classes>
    </package>
  </packages>
</coverage>
XML;

        $tempFile = tempnam(sys_get_temp_dir(), 'coverage_test');
        file_put_contents($tempFile, $xml);

        return $tempFile;
    }
}
