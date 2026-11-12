<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Command;

use Phauthentic\CognitiveCodeAnalysis\Application;
use Phauthentic\CognitiveCodeAnalysis\Command\ChurnCommand;
use Phauthentic\CognitiveCodeAnalysis\Command\CognitiveMetricsCommand;
use Phauthentic\CognitiveCodeAnalysis\Command\EventHandler\VerboseHandler;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArgvInput;

class DebugOptionTest extends TestCase
{
    /**
     * @return array<string, array{class-string, list<string>, bool}>
     */
    public static function debugProvider(): array
    {
        $cases = [];
        foreach ([CognitiveMetricsCommand::class, ChurnCommand::class] as $class) {
            $cases[$class . ' absent'] = [$class, [], false];
            $cases[$class . ' bare'] = [$class, ['--debug'], true];
            $cases[$class . ' =1'] = [$class, ['--debug=1'], true];
            $cases[$class . ' =true'] = [$class, ['--debug=true'], true];
            $cases[$class . ' =0'] = [$class, ['--debug=0'], false];
            $cases[$class . ' =false'] = [$class, ['--debug=false'], false];
        }

        return $cases;
    }

    /**
     * @param class-string $class
     * @param list<string> $args
     */
    #[DataProvider('debugProvider')]
    public function testDebugOptionParsing(string $class, array $args, bool $expected): void
    {
        $command = (new Application())->getContainer()->get($class);
        $input = new ArgvInput(['phpcca', 'src', ...$args], $command->getDefinition());

        $this->assertSame($expected, VerboseHandler::isDebugRequested($input));
    }
}
