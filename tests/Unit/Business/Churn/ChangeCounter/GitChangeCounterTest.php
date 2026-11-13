<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business\Churn\ChangeCounter;

use Phauthentic\CognitiveCodeAnalysis\Business\Churn\ChangeCounter\GitChangeCounter;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class GitChangeCounterTest extends TestCase
{
    #[Test]
    public function testFailureIncludesErrorOutput(): void
    {
        $dir = sys_get_temp_dir() . '/phpcca-norepo-' . uniqid();
        mkdir($dir);
        file_put_contents($dir . '/a.php', '<?php');

        try {
            $this->expectException(CognitiveAnalysisException::class);
            $this->expectExceptionMessageMatches('/a\.php \(.*not a git repository/');

            (new GitChangeCounter())->getNumberOfChangesForFile($dir . '/a.php', '1900-01-01');
        } finally {
            unlink($dir . '/a.php');
            rmdir($dir);
        }
    }

    #[Test]
    public function testFileNamesWithGlobCharactersAreMatchedLiterally(): void
    {
        $dir = sys_get_temp_dir() . '/phpcca-literal-' . uniqid();
        mkdir($dir);
        $git = static function (string ...$args) use ($dir): void {
            $command = ['git', '-C', $dir, '-c', 'user.name=Test', '-c', 'user.email=test@example.com', ...$args];
            $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            self::assertIsResource($process);
            fclose($pipes[1]);
            fclose($pipes[2]);
            self::assertSame(0, proc_close($process), implode(' ', $args));
        };

        try {
            $git('init', '-q');
            foreach (['1', '2', '3'] as $revision) {
                file_put_contents($dir . '/a[1].php', '<?php // ' . $revision);
                $git('add', '-A');
                $git('commit', '-q', '-m', 'bracket ' . $revision);
            }
            // a1.php matches the glob a[1].php, so it must not be counted for a[1].php.
            file_put_contents($dir . '/a1.php', '<?php');
            $git('add', '-A');
            $git('commit', '-q', '-m', 'plain');

            $counter = new GitChangeCounter();
            $this->assertSame(3, $counter->getNumberOfChangesForFile($dir . '/a[1].php', '1900-01-01'));
            $this->assertSame(1, $counter->getNumberOfChangesForFile($dir . '/a1.php', '1900-01-01'));
        } finally {
            exec('rm -rf ' . escapeshellarg($dir));
        }
    }
}
