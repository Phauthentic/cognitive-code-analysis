<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Command\ChurnSpecifications;

use Phauthentic\CognitiveCodeAnalysis\Command\ChurnSpecifications\ChurnCommandContext;
use Phauthentic\CognitiveCodeAnalysis\Command\ChurnSpecifications\CoverageFormatExclusivitySpecification;
use Phauthentic\CognitiveCodeAnalysis\Command\ChurnSpecifications\CoverageFileExistsSpecification;
use Phauthentic\CognitiveCodeAnalysis\Command\ChurnSpecifications\CompositeChurnValidationSpecification;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ChurnSpecificationPatternTest extends TestCase
{
    private function createInput(array $parameters): ArrayInput
    {
        $definition = new InputDefinition([
            new InputArgument('path', InputArgument::REQUIRED),
            new InputOption('config', 'c', InputOption::VALUE_OPTIONAL),
            new InputOption('vcs', null, InputOption::VALUE_OPTIONAL),
            new InputOption('since', 's', InputOption::VALUE_OPTIONAL),
            new InputOption('report-type', 'r', InputOption::VALUE_OPTIONAL),
            new InputOption('report-file', 'f', InputOption::VALUE_OPTIONAL),
            new InputOption('coverage-cobertura', null, InputOption::VALUE_OPTIONAL),
            new InputOption('coverage-clover', null, InputOption::VALUE_OPTIONAL),
        ]);

        return new ArrayInput($parameters, $definition);
    }
    public function testCoverageFormatExclusivitySpecification(): void
    {
        $spec = new CoverageFormatExclusivitySpecification();

        // Test valid case - only cobertura
        $input1 = $this->createInput([
            'path' => '/test',
            '--coverage-cobertura' => 'coverage.xml'
        ]);
        $context1 = new ChurnCommandContext($input1);
        $this->assertTrue($spec->isSatisfiedBy($context1));

        // Test valid case - only clover
        $input2 = $this->createInput([
            'path' => '/test',
            '--coverage-clover' => 'coverage.xml'
        ]);
        $context2 = new ChurnCommandContext($input2);
        $this->assertTrue($spec->isSatisfiedBy($context2));

        // Test invalid case - both formats
        $input3 = $this->createInput([
            'path' => '/test',
            '--coverage-cobertura' => 'cobertura.xml',
            '--coverage-clover' => 'clover.xml'
        ]);
        $context3 = new ChurnCommandContext($input3);
        $this->assertFalse($spec->isSatisfiedBy($context3));
        $this->assertEquals('Only one coverage format can be specified at a time.', $spec->getErrorMessage());
    }

    public function testCoverageFileExistsSpecification(): void
    {
        $spec = new CoverageFileExistsSpecification();

        // Test valid case - no coverage file
        $input1 = $this->createInput(['path' => '/test']);
        $context1 = new ChurnCommandContext($input1);
        $this->assertTrue($spec->isSatisfiedBy($context1));

        // Test invalid case - non-existent file
        $input2 = $this->createInput([
            'path' => '/test',
            '--coverage-cobertura' => '/non/existent/file.xml'
        ]);
        $context2 = new ChurnCommandContext($input2);
        $this->assertFalse($spec->isSatisfiedBy($context2));
        $this->assertStringContainsString('Coverage file not found', $spec->getErrorMessage());
    }

    public function testCompositeValidationSpecification(): void
    {
        $spec = new CompositeChurnValidationSpecification([
            new CoverageFormatExclusivitySpecification(),
            new CoverageFileExistsSpecification(),
        ]);

        // Test valid case
        $input1 = $this->createInput(['path' => '/test']);
        $context1 = new ChurnCommandContext($input1);
        $this->assertTrue($spec->isSatisfiedBy($context1));

        // Test invalid case - both coverage formats
        $input2 = $this->createInput([
            'path' => '/test',
            '--coverage-cobertura' => 'cobertura.xml',
            '--coverage-clover' => 'clover.xml'
        ]);
        $context2 = new ChurnCommandContext($input2);
        $this->assertFalse($spec->isSatisfiedBy($context2));

        $failedSpec = $spec->getFirstFailedSpecification($context2);
        $this->assertInstanceOf(CoverageFormatExclusivitySpecification::class, $failedSpec);
        $this->assertEquals('Only one coverage format can be specified at a time.', $failedSpec->getErrorMessage());
    }
}
