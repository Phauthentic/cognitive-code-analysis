<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Command;

use Phauthentic\CognitiveCodeAnalysis\Application;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;
use Phauthentic\CognitiveCodeAnalysis\Command\ChurnCommand;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 *
 */
class ChurnCommandTest extends TestCase
{
    #[Test]
    public function testAnalyseNonExistentPath(): void
    {
        $this->expectException(CognitiveAnalysisException::class);
        $this->expectExceptionMessage('Path does not exist: does-not-exist');

        $application = new Application();
        $command = $application->getContainer()->get(ChurnCommand::class);
        $tester = new CommandTester($command);

        $tester->execute([
            'path' => 'does-not-exist',
        ]);
    }

    #[Test]
    public function testChurn(): void
    {
        $application = new Application();
        $command = $application->getContainer()->get(ChurnCommand::class);
        $tester = new CommandTester($command);

        $tester->execute([
            'path' => __DIR__ . '/../../../src',
        ]);

        $this->assertEquals(Command::SUCCESS, $tester->getStatusCode(), 'Command should succeed');
    }
}
