<?php

declare(strict_types=1);

namespace Phauthentic\CodeQuality\Business;

use InvalidArgumentException;
use Phauthentic\CodeQuality\PhpParser\MethodMetricsVisitor;
use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\NodeTraverserInterface;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use RuntimeException;
use Symfony\Component\Finder\Finder;

/**
 *
 */
class MetricsCollector
{
    private Parser $parser;
    private NodeTraverserInterface $traverser;
    private MethodMetricsVisitor $methodMetricsVisitor;

    public function __construct()
    {
        $this->parser = (new ParserFactory())->createForHostVersion();
        $this->methodMetricsVisitor = new MethodMetricsVisitor();
        $this->traverser = new NodeTraverser();
        $this->traverser->addVisitor($this->methodMetricsVisitor);
    }

    public function collect(string $path): MetricsCollection
    {
        $finder = new Finder();
        $finder->ignoreDotFiles(true);

        if (is_dir($path)) {
            $finder->files()->in($path)->name('*.php');
        } elseif (is_file($path)) {
            $finder->files()->in(dirname($path))->name(basename($path));
        } else {
            throw new InvalidArgumentException("Invalid path provided.");
        }

        return $this->findMetrics($finder);
    }

    /**
     * @param Finder $finder
     * @return MetricsCollection
     * @todo Something is wrong here, there are duplicates
     */
    private function findMetrics(Finder $finder): MetricsCollection
    {
        $metricsCollection = new MetricsCollection();

        foreach ($finder as $file) {
            $code = file_get_contents($file->getRealPath());
            if ($code === false) {
                throw new RuntimeException("Could not read file: {$file->getRealPath()}");
            }

            $methodMetrics = $this->getMethodMetrics($code);

            foreach ($methodMetrics as $classAndMethod => $metrics) {
                $parts = explode('::', $classAndMethod);
                $class = $parts[0];
                $method = $parts[1];

                $metricsArray = array_merge($metrics, [
                    'class' => $class,
                    'method' => $method
                ]);

                $metrics = Metrics::fromArray($metricsArray);

                // @todo Something is wrong here, there are duplicates
                if (!$metricsCollection->contains($metrics)) {
                    $metricsCollection->add($metrics);
                }
            }
        }

        return $metricsCollection;
    }


    /**
     * @param string $code
     * @return array<string, array<string, int>>
     */
    private function getMethodMetrics(string $code): array
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

        return $this->methodMetricsVisitor->getMethodMetrics();
    }
}
