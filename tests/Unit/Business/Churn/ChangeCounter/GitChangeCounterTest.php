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
}
