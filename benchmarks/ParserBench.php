<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Benchmarks;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Parser;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpBench\Attributes as Bench;

/**
 * Benchmark for the Parser class to measure performance improvements.
 */
class ParserBench
{
    private Parser $parser;
    private string $simpleClass;
    private string $complexClass;
    private string $largeClass;
    private string $classWithAnnotations;

    public function __construct()
    {
        $this->parser = new Parser(
            new ParserFactory(),
            new NodeTraverser()
        );

        $this->simpleClass = $this->getSimpleClassCode();
        $this->complexClass = $this->getComplexClassCode();
        $this->largeClass = $this->getLargeClassCode();
        $this->classWithAnnotations = $this->getClassWithAnnotationsCode();
    }

    /**
     * Benchmark parsing a simple class with basic methods.
     */
    #[Bench\BeforeMethods('setUp')]
    #[Bench\Iterations(10)]
    #[Bench\Revs(100)]
    public function benchParseSimpleClass(): void
    {
        $this->parser->parse($this->simpleClass);
    }

    /**
     * Benchmark parsing a complex class with nested structures.
     */
    #[Bench\BeforeMethods('setUp')]
    #[Bench\Iterations(10)]
    #[Bench\Revs(50)]
    public function benchParseComplexClass(): void
    {
        $this->parser->parse($this->complexClass);
    }

    /**
     * Benchmark parsing a large class with many methods.
     */
    #[Bench\BeforeMethods('setUp')]
    #[Bench\Iterations(10)]
    #[Bench\Revs(20)]
    public function benchParseLargeClass(): void
    {
        $this->parser->parse($this->largeClass);
    }

    /**
     * Benchmark parsing a class with annotations (ignored items).
     */
    #[Bench\BeforeMethods('setUp')]
    #[Bench\Iterations(10)]
    #[Bench\Revs(50)]
    public function benchParseClassWithAnnotations(): void
    {
        $this->parser->parse($this->classWithAnnotations);
    }

    /**
     * Benchmark parsing multiple files in sequence (simulating real usage).
     */
    #[Bench\BeforeMethods('setUp')]
    #[Bench\Iterations(5)]
    #[Bench\Revs(10)]
    public function benchParseMultipleFiles(): void
    {
        $files = [
            $this->simpleClass,
            $this->complexClass,
            $this->largeClass,
            $this->classWithAnnotations,
            $this->simpleClass, // Repeat to test caching
            $this->complexClass,
        ];

        foreach ($files as $file) {
            $this->parser->parse($file);
        }
    }

    public function setUp(): void
    {
        // Reset any internal state if needed
        // This method is called before each benchmark iteration
    }

    private function getSimpleClassCode(): string
    {
        return <<<'PHP'
<?php

namespace Test\Simple;

class SimpleClass
{
    public function __construct()
    {
        $this->value = 0;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function setValue(int $value): void
    {
        $this->value = $value;
    }

    public function calculate(int $a, int $b): int
    {
        return $a + $b;
    }
}
PHP;
    }

    private function getComplexClassCode(): string
    {
        return <<<'PHP'
<?php

namespace Test\Complex;

use Test\Simple\SimpleClass;

class ComplexClass
{
    private array $data = [];
    private ?SimpleClass $helper = null;

    public function __construct(SimpleClass $helper = null)
    {
        $this->helper = $helper;
        $this->initializeData();
    }

    private function initializeData(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->data[$i] = $i * 2;
        }
    }

    public function processData(array $input): array
    {
        $result = [];
        
        foreach ($input as $key => $value) {
            if ($this->isValidValue($value)) {
                $processed = $this->transformValue($value);
                if ($processed !== null) {
                    $result[$key] = $processed;
                }
            }
        }

        return $result;
    }

    private function isValidValue($value): bool
    {
        if (is_string($value)) {
            return strlen($value) > 0;
        }
        
        if (is_numeric($value)) {
            return $value > 0;
        }

        return false;
    }

    private function transformValue($value)
    {
        if (is_string($value)) {
            return strtoupper($value);
        }
        
        if (is_numeric($value)) {
            return $value * 2;
        }

        return null;
    }

    public function getComplexity(): int
    {
        $complexity = 0;
        
        if ($this->helper !== null) {
            $complexity += $this->helper->getValue();
        }

        foreach ($this->data as $item) {
            if ($item > 5) {
                $complexity += $item;
            } else {
                $complexity += 1;
            }
        }

        return $complexity;
    }
}
PHP;
    }

    private function getLargeClassCode(): string
    {
        $methods = '';
        for ($i = 1; $i <= 50; $i++) {
            $methods .= <<<PHP

    public function method{$i}(int \$param1, string \$param2 = 'default'): array
    {
        \$result = [];
        
        if (\$param1 > 0) {
            for (\$j = 0; \$j < \$param1; \$j++) {
                \$result[] = \$param2 . '_' . \$j;
            }
        } else {
            \$result[] = 'empty';
        }

        return \$result;
    }

PHP;
        }

        return <<<PHP
<?php

namespace Test\Large;

class LargeClass
{
    private array \$cache = [];
    private int \$counter = 0;

    public function __construct()
    {
        \$this->initializeCache();
    }

    private function initializeCache(): void
    {
        for (\$i = 0; \$i < 100; \$i++) {
            \$this->cache[\$i] = 'cached_value_' . \$i;
        }
    }

    public function getCacheValue(int \$key): ?string
    {
        return \$this->cache[\$key] ?? null;
    }

    public function incrementCounter(): int
    {
        return ++\$this->counter;
    }

    public function resetCounter(): void
    {
        \$this->counter = 0;
    }

{$methods}
}
PHP;
    }

    private function getClassWithAnnotationsCode(): string
    {
        return <<<'PHP'
<?php

namespace Test\Annotations;

/**
 * @cca-ignore
 */
class IgnoredClass
{
    public function ignoredMethod(): void
    {
        // This class should be ignored
    }
}

class NormalClass
{
    private string $name;
    private array $items = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @cca-ignore
     */
    public function ignoredMethod(): void
    {
        // This method should be ignored
    }

    public function addItem(string $item): void
    {
        $this->items[] = $item;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function processItems(): array
    {
        $result = [];
        
        foreach ($this->items as $item) {
            if (strlen($item) > 3) {
                $result[] = strtoupper($item);
            }
        }

        return $result;
    }

    public function calculateComplexity(): int
    {
        $complexity = 0;
        
        if (count($this->items) > 0) {
            $complexity += count($this->items);
            
            foreach ($this->items as $item) {
                if (strlen($item) > 5) {
                    $complexity += 2;
                } else {
                    $complexity += 1;
                }
            }
        }

        return $complexity;
    }
}
PHP;
    }
}

