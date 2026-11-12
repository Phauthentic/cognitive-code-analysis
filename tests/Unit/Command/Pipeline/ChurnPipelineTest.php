<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Command\Pipeline;

use Phauthentic\CognitiveCodeAnalysis\Command\ChurnSpecifications\ChurnCommandContext;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\ChurnExecutionContext;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\ChurnPipeline;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\ChurnPipelineStage;
use Phauthentic\CognitiveCodeAnalysis\Command\Result\OperationResult;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\NullOutput;

class ChurnPipelineTest extends TestCase
{
    public function testRecordsTimingPerExecutedStage(): void
    {
        $stage = new class implements ChurnPipelineStage {
            public function execute(ChurnExecutionContext $context): OperationResult
            {
                usleep(2000);

                return OperationResult::success();
            }

            public function shouldSkip(ChurnExecutionContext $context): bool
            {
                return false;
            }

            public function getStageName(): string
            {
                return 'Slow';
            }
        };

        $context = new ChurnExecutionContext($this->createStub(ChurnCommandContext::class), new NullOutput());
        (new ChurnPipeline([$stage]))->execute($context);

        $this->assertSame(['Slow'], array_keys($context->getTimings()));
        $this->assertGreaterThan(0.0, $context->getTotalTime());
    }
}
