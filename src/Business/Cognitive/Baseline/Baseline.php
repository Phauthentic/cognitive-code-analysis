<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Baseline;

use Phauthentic\CognitiveCodeAnalysis\Config\CognitiveConfig;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetrics;

class Baseline
{
    /**
     * @param CognitiveMetricsCollection $metricsCollection
     * @param array<string, array<string, mixed>> $baseline
     * @param bool $validateConfigHash Whether to validate config hash and emit warnings
     * @param CognitiveConfig|null $currentConfig Current configuration for hash validation
     * @return array<string> Array of warning messages
     */
    public function calculateDeltas(
        CognitiveMetricsCollection $metricsCollection,
        array $baseline,
        bool $validateConfigHash = false,
        ?CognitiveConfig $currentConfig = null
    ): array {
        $warnings = [];

        foreach ($baseline as $class => $data) {
            foreach ($data['methods'] as $methodName => $methodData) {
                $metrics = $metricsCollection->getClassWithMethod($class, $methodName);
                if (!$metrics) {
                    continue;
                }

                $previousMetrics = new CognitiveMetrics($methodData);
                $metrics->calculateDeltas($previousMetrics);
            }
        }

        return $warnings;
    }

    /**
     * Loads the baseline file and returns the data as an array.
     * Supports both old and new baseline file formats.
     *
     * @param string $baselineFile
     * @return array{metrics: array<string, array<string, mixed>>, baselineFile: BaselineFile|null, warnings: array<string>}
     * @throws \JsonException|\Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException
     */
    public function loadBaseline(string $baselineFile): array
    {
        if (!file_exists($baselineFile)) {
            throw new CognitiveAnalysisException('Baseline file does not exist.');
        }

        $baseline = file_get_contents($baselineFile);
        if ($baseline === false) {
            throw new CognitiveAnalysisException('Failed to read baseline file.');
        }

        $data = json_decode($baseline, true, 512, JSON_THROW_ON_ERROR);

        // Validate against JSON schema
        $validator = new BaselineSchemaValidator();
        $validationErrors = $validator->validate($data);

        if (!empty($validationErrors)) {
            $errorMessage = 'Invalid baseline file format: ' . implode(', ', $validationErrors);
            throw new CognitiveAnalysisException($errorMessage);
        }

        $result = BaselineFile::fromJson($data);

        return [
            'metrics' => $result['metrics'],
            'baselineFile' => $result['baselineFile'],
            'warnings' => []
        ];
    }

    /**
     * Loads baseline and validates config hash if provided.
     *
     * @param string $baselineFile
     * @param CognitiveConfig|null $currentConfig
     * @return array{metrics: array<string, array<string, mixed>>, baselineFile: BaselineFile|null, warnings: array<string>}
     * @throws \JsonException|\Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException
     */
    public function loadBaselineWithValidation(string $baselineFile, ?CognitiveConfig $currentConfig = null): array
    {
        $result = $this->loadBaseline($baselineFile);
        $warnings = $result['warnings'];

        // Validate config hash if we have both baseline file and current config
        if ($result['baselineFile'] !== null && $currentConfig !== null) {
            if (!$result['baselineFile']->validateConfigHash($currentConfig)) {
                $warnings[] = sprintf(
                    'Warning: Baseline config hash (%s) does not match current config hash (%s). ' .
                    'Metrics comparison may not be accurate.',
                    $result['baselineFile']->getConfigHash(),
                    BaselineFile::generateConfigHash($currentConfig)
                );
            }
        }

        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Find the latest baseline file in the default directory.
     *
     * @param string $baselineDirectory
     * @return string|null Path to the latest baseline file, or null if none found
     */
    public function findLatestBaselineFile(string $baselineDirectory = './.phpcca/baseline'): ?string
    {
        if (!is_dir($baselineDirectory)) {
            return null;
        }

        $baselineFiles = $this->getBaselineFiles($baselineDirectory);

        if (empty($baselineFiles)) {
            return null;
        }

        // Sort by modification time (newest first)
        usort($baselineFiles, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        return $baselineFiles[0];
    }

    /**
     * Get all baseline files from the specified directory.
     *
     * @param string $baselineDirectory
     * @return array<string> Array of baseline file paths
     */
    public function getBaselineFiles(string $baselineDirectory): array
    {
        if (!is_dir($baselineDirectory)) {
            return [];
        }

        $files = glob($baselineDirectory . '/baseline-*.json');

        if ($files === false) {
            return [];
        }

        // Filter out non-baseline files and validate they are readable
        $baselineFiles = [];
        foreach ($files as $file) {
            if (!is_file($file) || !is_readable($file)) {
                continue;
            }

            $baselineFiles[] = $file;
        }

        return $baselineFiles;
    }

    /**
     * Check if a file is a valid baseline file (either old or new format).
     *
     * @param string $filePath
     * @return bool
     */
    public function isValidBaselineFile(string $filePath): bool
    {
        if (!is_file($filePath) || !is_readable($filePath)) {
            return false;
        }

        try {
            $content = file_get_contents($filePath);
            if ($content === false) {
                return false;
            }

            $data = json_decode($content, true);
            if ($data === null) {
                return false;
            }

            // Use schema validator for comprehensive validation
            $validator = new BaselineSchemaValidator();
            return $validator->isValidBaseline($data);
        } catch (\Exception) {
            return false;
        }
    }
}
