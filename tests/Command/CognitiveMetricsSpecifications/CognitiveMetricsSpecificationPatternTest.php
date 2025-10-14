<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Command\CognitiveMetricsSpecifications;

use Phauthentic\CognitiveCodeAnalysis\Command\CognitiveMetricsSpecifications\CognitiveMetricsCommandContext;
use Phauthentic\CognitiveCodeAnalysis\Command\CognitiveMetricsSpecifications\CoverageFormatExclusivity;
use Phauthentic\CognitiveCodeAnalysis\Command\CognitiveMetricsSpecifications\CoverageFileExists;
use Phauthentic\CognitiveCodeAnalysis\Command\CognitiveMetricsSpecifications\CompositeCognitiveMetricsValidationSpecification;
use Phauthentic\CognitiveCodeAnalysis\Command\CognitiveMetricsSpecifications\SortFieldValid;
use Phauthentic\CognitiveCodeAnalysis\Command\CognitiveMetricsSpecifications\SortOrderValid;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class CognitiveMetricsSpecificationPatternTest extends TestCase
{
    private function createInput(array $parameters): ArrayInput
    {
        $definition = new InputDefinition([
            new InputArgument('path', InputArgument::REQUIRED),
            new InputOption('config', 'c', InputOption::VALUE_OPTIONAL),
            new InputOption('baseline', 'b', InputOption::VALUE_OPTIONAL),
            new InputOption('report-type', 'r', InputOption::VALUE_OPTIONAL),
            new InputOption('report-file', 'f', InputOption::VALUE_OPTIONAL),
            new InputOption('sort-by', 's', InputOption::VALUE_OPTIONAL),
            new InputOption('sort-order', null, InputOption::VALUE_OPTIONAL),
            new InputOption('debug', null, InputOption::VALUE_NONE),
            new InputOption('coverage-cobertura', null, InputOption::VALUE_OPTIONAL),
            new InputOption('coverage-clover', null, InputOption::VALUE_OPTIONAL),
        ]);

        return new ArrayInput($parameters, $definition);
    }
    public function testCoverageFormatExclusivitySpecification(): void
    {
        $spec = new CoverageFormatExclusivity();

        // Test valid case - only cobertura
        $input1 = $this->createInput([
            'path' => '/test',
            '--coverage-cobertura' => 'coverage.xml'
        ]);
        $context1 = new CognitiveMetricsCommandContext($input1);
        $this->assertTrue($spec->isSatisfiedBy($context1));

        // Test valid case - only clover
        $input2 = $this->createInput([
            'path' => '/test',
            '--coverage-clover' => 'coverage.xml'
        ]);
        $context2 = new CognitiveMetricsCommandContext($input2);
        $this->assertTrue($spec->isSatisfiedBy($context2));

        // Test invalid case - both formats
        $input3 = $this->createInput([
            'path' => '/test',
            '--coverage-cobertura' => 'cobertura.xml',
            '--coverage-clover' => 'clover.xml'
        ]);
        $context3 = new CognitiveMetricsCommandContext($input3);
        $this->assertFalse($spec->isSatisfiedBy($context3));
        $this->assertEquals('Only one coverage format can be specified at a time.', $spec->getErrorMessage());
    }

    public function testCoverageFileExistsSpecification(): void
    {
        $spec = new CoverageFileExists();

        // Test valid case - no coverage file
        $input1 = $this->createInput(['path' => '/test']);
        $context1 = new CognitiveMetricsCommandContext($input1);
        $this->assertTrue($spec->isSatisfiedBy($context1));

        // Test invalid case - non-existent file
        $input2 = $this->createInput([
            'path' => '/test',
            '--coverage-cobertura' => '/non/existent/file.xml'
        ]);
        $context2 = new CognitiveMetricsCommandContext($input2);
        $this->assertFalse($spec->isSatisfiedBy($context2));
        $this->assertStringContainsString('Coverage file not found', $spec->getErrorMessage());
    }

    public function testSortFieldValidSpecification(): void
    {
        $spec = new SortFieldValid();

        // Test valid case - no sort field
        $input1 = $this->createInput(['path' => '/test']);
        $context1 = new CognitiveMetricsCommandContext($input1);
        $this->assertTrue($spec->isSatisfiedBy($context1));

        // Test valid case - valid sort field
        $input2 = $this->createInput([
            'path' => '/test',
            '--sort-by' => 'score'
        ]);
        $context2 = new CognitiveMetricsCommandContext($input2);
        $this->assertTrue($spec->isSatisfiedBy($context2));

        // Test invalid case - invalid sort field
        $input3 = $this->createInput([
            'path' => '/test',
            '--sort-by' => 'invalid_field'
        ]);
        $context3 = new CognitiveMetricsCommandContext($input3);
        $this->assertFalse($spec->isSatisfiedBy($context3));
        $this->assertEquals('Invalid sort field provided.', $spec->getErrorMessage());

        // Test detailed error message
        $this->assertStringContainsString('Invalid sort field "invalid_field"', $spec->getErrorMessageWithContext($context3));
        $this->assertStringContainsString('Available fields:', $spec->getErrorMessageWithContext($context3));
    }

    public function testSortOrderValidSpecification(): void
    {
        $spec = new SortOrderValid();

        // Test valid case - asc
        $input1 = $this->createInput([
            'path' => '/test',
            '--sort-order' => 'asc'
        ]);
        $context1 = new CognitiveMetricsCommandContext($input1);
        $this->assertTrue($spec->isSatisfiedBy($context1));

        // Test valid case - desc
        $input2 = $this->createInput([
            'path' => '/test',
            '--sort-order' => 'desc'
        ]);
        $context2 = new CognitiveMetricsCommandContext($input2);
        $this->assertTrue($spec->isSatisfiedBy($context2));

        // Test invalid case - invalid sort order
        $input3 = $this->createInput([
            'path' => '/test',
            '--sort-order' => 'invalid'
        ]);
        $context3 = new CognitiveMetricsCommandContext($input3);
        $this->assertFalse($spec->isSatisfiedBy($context3));
        $this->assertEquals('Sort order must be "asc" or "desc"', $spec->getErrorMessage());

        // Test detailed error message
        $this->assertStringContainsString('Sort order must be "asc" or "desc", got "invalid"', $spec->getErrorMessageWithContext($context3));
    }

    public function testCompositeValidationSpecification(): void
    {
        $spec = new CompositeCognitiveMetricsValidationSpecification([
            new CoverageFormatExclusivity(),
            new CoverageFileExists(),
            new SortFieldValid(),
        ]);

        // Test valid case
        $input1 = $this->createInput(['path' => '/test']);
        $context1 = new CognitiveMetricsCommandContext($input1);
        $this->assertTrue($spec->isSatisfiedBy($context1));

        // Test invalid case - both coverage formats
        $input2 = $this->createInput([
            'path' => '/test',
            '--coverage-cobertura' => 'cobertura.xml',
            '--coverage-clover' => 'clover.xml'
        ]);
        $context2 = new CognitiveMetricsCommandContext($input2);
        $this->assertFalse($spec->isSatisfiedBy($context2));

        $failedSpec = $spec->getFirstFailedSpecification($context2);
        $this->assertInstanceOf(CoverageFormatExclusivity::class, $failedSpec);
        $this->assertEquals('Only one coverage format can be specified at a time.', $failedSpec->getErrorMessage());

        // Test detailed error message
        $detailedError = $spec->getDetailedErrorMessage($context2);
        $this->assertEquals('Only one coverage format can be specified at a time.', $detailedError);
    }

    public function testCognitiveMetricsCommandContext(): void
    {
        $input = $this->createInput([
            'path' => '/test/path',
            '--config' => 'config.yml',
            '--coverage-cobertura' => 'coverage.xml',
            '--sort-by' => 'score',
            '--sort-order' => 'desc',
            '--baseline' => 'baseline.json',
            '--debug' => true
        ]);

        $context = new CognitiveMetricsCommandContext($input);

        $this->assertEquals('/test/path', $context->getPaths()[0]);
        $this->assertTrue($context->hasConfigFile());
        $this->assertEquals('config.yml', $context->getConfigFile());
        $this->assertTrue($context->hasCoberturaFile());
        $this->assertEquals('coverage.xml', $context->getCoberturaFile());
        $this->assertEquals('cobertura', $context->getCoverageFormat());
        $this->assertEquals('score', $context->getSortBy());
        $this->assertEquals('desc', $context->getSortOrder());
        $this->assertTrue($context->hasBaselineFile());
        $this->assertEquals('baseline.json', $context->getBaselineFile());
        $this->assertTrue($context->getDebug());
    }
}
