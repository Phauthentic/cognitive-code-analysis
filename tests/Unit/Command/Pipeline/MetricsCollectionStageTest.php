<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Command\Pipeline;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetrics;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\Business\MetricsFacade;
use Phauthentic\CognitiveCodeAnalysis\Command\CognitiveMetricsSpecifications\CognitiveMetricsCommandContext;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\CognitiveStages\MetricsCollectionStage;
use Phauthentic\CognitiveCodeAnalysis\Command\Pipeline\ExecutionContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\NullOutput;

class MetricsCollectionStageTest extends TestCase
{
    public function testFilesProcessedCountsDistinctFilesNotPaths(): void
    {
        $collection = new CognitiveMetricsCollection();
        foreach ([['a.php', 'A', 'x'], ['a.php', 'A', 'y'], ['b.php', 'B', 'x']] as [$file, $class, $method]) {
            $metric = $this->createStub(CognitiveMetrics::class);
            $metric->method('getFileName')->willReturn($file);
            $metric->method('getClass')->willReturn($class);
            $metric->method('getMethod')->willReturn($method);
            $collection->add($metric);
        }

        $facade = $this->createStub(MetricsFacade::class);
        $facade->method('getCognitiveMetricsFromPaths')->willReturn($collection);

        $commandContext = $this->createStub(CognitiveMetricsCommandContext::class);
        $commandContext->method('getPaths')->willReturn(['src']);

        $context = new ExecutionContext($commandContext, new NullOutput());
        (new MetricsCollectionStage($facade))->execute($context);

        $this->assertSame(2, $context->getStatistic('filesProcessed'));
        $this->assertSame(3, $context->getStatistic('metricsCollected'));
    }
}
