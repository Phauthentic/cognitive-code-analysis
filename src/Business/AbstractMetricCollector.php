<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics\Business;

use Generator;
use PhpParser\Error;
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

    public function __construct(
        protected readonly ParserFactory $parserFactory,
        protected readonly NodeTraverserInterface $traverser,
        protected readonly DirectoryScanner $directoryScanner
    ) {
        $this->parser = $this->parserFactory->createForHostVersion();
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
