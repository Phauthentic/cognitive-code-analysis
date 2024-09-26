<?php

declare(strict_types=1);

namespace Phauthentic\CodeQualityMetrics\Business\Halstead;

use Phauthentic\CodeQualityMetrics\Business\DirectoryScanner;
use Phauthentic\CodeQualityMetrics\PhpParser\HalsteadMetricsVisitor;
use PhpParser\Error;
use PhpParser\NodeTraverserInterface;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use RuntimeException;
use SplFileInfo;

/**
 * HalsteadMetricsCollector class that collects Halstead metrics from source files.
 */
class HalsteadMetricsCollector
{
    protected Parser $parser;

    public function __construct(
        protected readonly ParserFactory $parserFactory,
        protected readonly NodeTraverserInterface $traverser,
        protected readonly DirectoryScanner $directoryScanner,
    ) {
        $this->parser = $parserFactory->createForHostVersion();
    }

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

    /**
     * Find source files using DirectoryScanner
     *
     * @param string $path Path to the directory or file to scan
     * @param array<int, string> $exclude List of regx to exclude
     * @return iterable<mixed, SplFileInfo> An iterable of SplFileInfo objects
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

    /**
     * Collect Halstead metrics from the given path.
     *
     * @param string $path
     * @param array<string, mixed> $config
     * @return HalsteadMetricsCollection
     */
    public function collect(string $path, array $config = []): HalsteadMetricsCollection
    {
        $files = $this->findSourceFiles($path, $this->getExcludePatternsFromConfig($config));

        return $this->findMetrics($files);
    }

    /**
     * Collect Halstead metrics from the found source files.
     *
     * @param iterable<SplFileInfo> $files
     * @return HalsteadMetricsCollection
     */
    protected function findMetrics(iterable $files): HalsteadMetricsCollection
    {
        $metricsCollection = new HalsteadMetricsCollection();

        foreach ($files as $file) {
            $code = file_get_contents($file->getRealPath());

            if ($code === false) {
                throw new RuntimeException("Could not read file: {$file->getRealPath()}");
            }

            $halsteadMetricsVisitor = new HalsteadMetricsVisitor();
            $this->traverser->addVisitor($halsteadMetricsVisitor);

            $this->traverseAbstractSyntaxTree($code);

            $metricsData = $halsteadMetricsVisitor->getMetrics();
            $this->traverser->removeVisitor($halsteadMetricsVisitor);

            foreach ($metricsData as $class => $data) {
                $data['class'] = $class;
                $data['file'] = $file->getRealPath();
                $metrics = HalsteadMetrics::fromArray($data);

                if (!$metricsCollection->contains($metrics)) {
                    $metricsCollection->add($metrics);
                }
            }
        }

        return $metricsCollection;
    }
}
