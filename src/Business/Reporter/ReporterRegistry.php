<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Reporter;

use Phauthentic\CognitiveCodeAnalysis\Config\CognitiveConfig;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;

/**
 * Registry for managing dynamic exporter loading and instantiation.
 */
class ReporterRegistry
{
    /** @var array<string, bool> */
    private array $loadedFiles = [];

    /**
     * Load an exporter class, optionally including a file first.
     *
     * @param string $class The fully qualified class name
     * @param string|null $file Optional file path to include
     * @throws CognitiveAnalysisException If file doesn't exist or class is not found
     */
    public function loadExporter(string $class, ?string $file): void
    {
        if ($file !== null && !isset($this->loadedFiles[$file])) {
            if (!file_exists($file)) {
                throw new CognitiveAnalysisException("Exporter file not found: {$file}");
            }
            require_once $file;
            $this->loadedFiles[$file] = true;
        }

        if (!class_exists($class)) {
            throw new CognitiveAnalysisException("Exporter class not found: {$class}");
        }
    }

    /**
     * Instantiate an exporter class with optional CognitiveConfig dependency.
     *
     * @param string $class The fully qualified class name
     * @param CognitiveConfig|null $config The config to pass if available
     * @return object The instantiated exporter
     */
    public function instantiate(string $class, ?CognitiveConfig $config): object
    {
        // Always try to pass config first, fallback to no-arg constructor if it fails
        try {
            if ($config !== null) {
                return new $class($config);
            }
            return new $class();
        } catch (\ArgumentCountError $e) {
            // Constructor doesn't accept config parameter, try without it
            return new $class();
        }
    }

    /**
     * Validate that an exporter implements the expected interface.
     *
     * @param object $exporter The exporter instance to validate
     * @param string $expectedInterface The interface it should implement
     * @throws CognitiveAnalysisException If the exporter doesn't implement the interface
     */
    public function validateInterface(object $exporter, string $expectedInterface): void
    {
        if (!$exporter instanceof $expectedInterface) {
            throw new CognitiveAnalysisException(
                "Exporter must implement {$expectedInterface}"
            );
        }
    }
}
