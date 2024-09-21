<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics\Business;

use Generator;
use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\NodeTraverserInterface;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use RuntimeException;
use SplFileInfo;

/**
 *
 */
abstract class AbstractMetricCollector
{
    protected Parser $parser;
    protected NodeTraverserInterface $traverser;
    protected DirectoryScanner $directoryScanner;

    /**
     * @param array<string, mixed> $config
     * @return array<int, string>
     */
    protected function getExcludePatternsFromConfig(array $config): array
    {
        if (isset($config['excludePatterns'])) {
            return $config['excludePatterns'];
        }

        return [];
    }

    public function __construct()
    {
        $this->parser = (new ParserFactory())->createForHostVersion();
        $this->traverser = new NodeTraverser();
        $this->directoryScanner = new DirectoryScanner();
    }

    /**
     * Find source files using DirectoryScanner
     *
     * @param string $path Path to the directory or file to scan
     * @param array<int, string> $exclude List of regx to exclude
     * @return Generator<mixed, SplFileInfo, mixed, mixed> An iterable of SplFileInfo objects
     */
    protected function findSourceFiles(string $path, array $exclude = []): iterable
    {
        return $this->directoryScanner->scan([$path], ['^(?!.*\.php$).+'] + $exclude); // Exclude non-PHP files
    }


    protected function traverseAbstractSyntaxTree(string $code): void
    {
        try {
            $ast = $this->parser->parse($code);
        } catch (Error $e) {
            throw new RuntimeException("Parse error: {$e->getMessage()}", 0, $e);
        }

        if ($ast === null) {
            throw new RuntimeException("Could not parse the code.");
        }

        $this->traverser->traverse($ast);
    }
}
