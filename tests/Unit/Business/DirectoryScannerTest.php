<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Tests\Unit\Business;

use FilesystemIterator;
use Phauthentic\CognitiveCodeAnalysis\Business\DirectoryScanner;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class DirectoryScannerTest extends TestCase
{
    private string $testDir = '';

    protected function setUp(): void
    {
        $this->testDir = sys_get_temp_dir() . '/test-files';

        if (!is_dir($this->testDir)) {
            mkdir($this->testDir, 0777, true);
        }

        $files = [
            'test1.php',
            'test2.php',
            'exclude_me.txt',
            'subdir/test3.php',
            'subdir/exclude_me_too.txt',
        ];

        foreach ($files as $file) {
            $path = $this->testDir . '/' . $file;
            $dir = dirname($path);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            file_put_contents($path, 'Sample content');
        }
    }

    protected function tearDown(): void
    {
        // Clean up test directory and files
        $this->deleteDirectory($this->testDir);
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir($item->getRealPath());
            } else {
                unlink($item->getRealPath());
            }
        }

        rmdir($dir);
    }

    #[Test]
    public function testScan(): void
    {
        $scanner = new DirectoryScanner();
        $excludePatterns = ['exclude_me', 'exclude_me_too'];

        $files = [];
        foreach ($scanner->scan([$this->testDir], $excludePatterns) as $fileInfo) {
            $files[] = $fileInfo->getPathname();
        }

        $expectedFiles = [
            realpath($this->testDir . '/test1.php'),
            realpath($this->testDir . '/test2.php'),
            realpath($this->testDir . '/subdir/test3.php'),
        ];

        sort($files);
        sort($expectedFiles);

        $this->assertEquals($expectedFiles, $files);
    }
}
