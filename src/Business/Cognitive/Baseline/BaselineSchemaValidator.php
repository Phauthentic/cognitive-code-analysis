<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Baseline;

/**
 * JSON Schema validator for baseline files.
 * Validates baseline files against the defined schema structure.
 */
class BaselineSchemaValidator
{
    /**
     * Validate baseline data against the schema.
     *
     * @param array<string, mixed> $data
     * @return array<string> Array of validation errors (empty if valid)
     */
    public function validate(array $data): array
    {
        $errors = [];

        // Check if it's new format (version 2.0)
        if (isset($data['version'])) {
            return array_merge($errors, $this->validateNewFormat($data));
        }

        // Legacy format
        return $this->validateLegacyFormat($data);
    }

    /**
     * Validate new format (version 2.0) baseline data.
     *
     * @param array<string, mixed> $data
     * @return array<string>
     */
    private function validateNewFormat(array $data): array
    {
        $errors = [];

        // Required fields
        $requiredFields = ['version', 'createdAt', 'configHash', 'metrics'];
        foreach ($requiredFields as $field) {
            if (isset($data[$field])) {
                continue;
            }

            $errors[] = "Missing required field: {$field}";
        }

        if (!empty($errors)) {
            return $errors;
        }

        // Validate version
        if ($data['version'] !== '2.0') {
            $errors[] = "Invalid version: {$data['version']}. Expected: 2.0";
        }

        // Validate createdAt format
        if (!is_string($data['createdAt']) || !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $data['createdAt'])) {
            $errors[] = "Invalid createdAt format. Expected: YYYY-MM-DD HH:MM:SS";
        }

        // Validate configHash
        if (!is_string($data['configHash']) || empty($data['configHash'])) {
            $errors[] = "Invalid configHash. Must be a non-empty string";
        }

         $errors = array_merge($errors, $this->validateMetrics($data['metrics']));
        // Validate metrics structure
        if (!is_array($data['metrics'])) {
            $errors[] = "Invalid metrics. Must be an object";
        }

        // Check for additional properties
        $allowedFields = ['version', 'createdAt', 'configHash', 'metrics'];
        foreach (array_keys($data) as $key) {
            if (in_array($key, $allowedFields, true)) {
                continue;
            }

            $errors[] = "Unexpected field: {$key}";
        }

        return $errors;
    }

    /**
     * Validate legacy format baseline data.
     *
     * @param array<string, mixed> $data
     * @return array<string>
     */
    private function validateLegacyFormat(array $data): array
    {
        $errors = [];

        if (empty($data)) {
            $errors[] = "Empty baseline data";
            return $errors;
        }

        // Validate class structure
        foreach ($data as $className => $classData) {
            if (empty($className)) {
                $errors[] = "Invalid class name: {$className}";
                continue;
            }

            if (!is_array($classData)) {
                $errors[] = "Class data for '{$className}' must be an object";
                continue;
            }

            if (!isset($classData['methods'])) {
                $errors[] = "Missing 'methods' field for class '{$className}'";
                continue;
            }

            if (!is_array($classData['methods'])) {
                $errors[] = "Methods for class '{$className}' must be an object";
                continue;
            }

            // Validate methods
            foreach ($classData['methods'] as $methodName => $methodData) {
                if (!is_string($methodName) || empty($methodName)) {
                    $errors[] = "Invalid method name: {$methodName} in class '{$className}'";
                    continue;
                }

                $errors = array_merge($errors, $this->validateMethodData($methodData, $className, $methodName));
            }

            // Check for additional properties in class data
            $allowedClassFields = ['methods'];
            foreach (array_keys($classData) as $key) {
                if (in_array($key, $allowedClassFields, true)) {
                    continue;
                }

                $errors[] = "Unexpected field '{$key}' in class '{$className}'";
            }
        }

        return $errors;
    }

    /**
     * Validate metrics structure (used in new format).
     *
     * @param array<string, mixed> $metrics
     * @return array<string>
     */
    private function validateMetrics(array $metrics): array
    {
        $errors = [];

        if (empty($metrics)) {
            $errors[] = "Metrics object cannot be empty";
            return $errors;
        }

        foreach ($metrics as $className => $classData) {
            if (empty($className)) {
                $errors[] = "Invalid class name in metrics: {$className}";
                continue;
            }

            if (!is_array($classData)) {
                $errors[] = "Class data for '{$className}' must be an object";
                continue;
            }

            if (!isset($classData['methods'])) {
                $errors[] = "Missing 'methods' field for class '{$className}'";
                continue;
            }

            if (!is_array($classData['methods'])) {
                $errors[] = "Methods for class '{$className}' must be an object";
                continue;
            }

            // Validate methods
            foreach ($classData['methods'] as $methodName => $methodData) {
                if (!is_string($methodName) || empty($methodName)) {
                    $errors[] = "Invalid method name: {$methodName} in class '{$className}'";
                    continue;
                }

                $errors = array_merge($errors, $this->validateMethodData($methodData, $className, $methodName));
            }

            // Check for additional properties in class data
            $allowedClassFields = ['methods'];
            foreach (array_keys($classData) as $key) {
                if (in_array($key, $allowedClassFields, true)) {
                    continue;
                }

                $errors[] = "Unexpected field '{$key}' in class '{$className}'";
            }
        }

        return $errors;
    }

    /**
     * Validate method data structure.
     *
     * @param mixed $methodData
     * @param string $className
     * @param string $methodName
     * @return array<string>
     */
    private function validateMethodData($methodData, string $className, string $methodName): array
    {
        $errors = [];

        if (!is_array($methodData)) {
            $errors[] = "Method data for '{$className}::{$methodName}' must be an object";
            return $errors;
        }

        // Required fields for method data
        $requiredFields = [
            'class', 'method', 'lineCount', 'argCount', 'returnCount',
            'variableCount', 'propertyCallCount', 'ifCount', 'ifNestingLevel', 'elseCount',
            'lineCountWeight', 'argCountWeight', 'returnCountWeight', 'variableCountWeight',
            'propertyCallCountWeight', 'ifCountWeight', 'ifNestingLevelWeight', 'elseCountWeight'
        ];

        foreach ($requiredFields as $field) {
            if (isset($methodData[$field])) {
                continue;
            }

            $errors[] = "Missing required field '{$field}' in method '{$className}::{$methodName}'";
        }

        if (!empty($errors)) {
            return $errors;
        }

        // Validate string fields
        $stringFields = ['class', 'method'];
        foreach ($stringFields as $field) {
            if (is_string($methodData[$field]) && !empty($methodData[$field])) {
                continue;
            }

            $errors[] = "Field '{$field}' in method '{$className}::{$methodName}' must be a non-empty string";
        }

        // Validate integer fields
        $integerFields = ['line', 'lineCount', 'argCount', 'returnCount', 'variableCount', 'propertyCallCount', 'ifCount', 'ifNestingLevel', 'elseCount'];
        foreach ($integerFields as $field) {
            if (!isset($methodData[$field]) || (is_int($methodData[$field]) && $methodData[$field] >= 0)) {
                continue;
            }

            $errors[] = "Field '{$field}' in method '{$className}::{$methodName}' must be a non-negative integer";
        }

        // Validate line field specifically (must be >= 1 if present)
        if (isset($methodData['line']) && (!is_int($methodData['line']) || $methodData['line'] < 1)) {
            $errors[] = "Field 'line' in method '{$className}::{$methodName}' must be a positive integer";
        }

        // Validate weight fields
        $weightFields = ['lineCountWeight', 'argCountWeight', 'returnCountWeight', 'variableCountWeight', 'propertyCallCountWeight', 'ifCountWeight', 'ifNestingLevelWeight', 'elseCountWeight'];
        foreach ($weightFields as $field) {
            if (is_numeric($methodData[$field]) && $methodData[$field] >= 0) {
                continue;
            }

            $errors[] = "Field '{$field}' in method '{$className}::{$methodName}' must be a non-negative number";
        }

        // Validate optional score field
        if (isset($methodData['score']) && (!is_numeric($methodData['score']) || $methodData['score'] < 0)) {
            $errors[] = "Field 'score' in method '{$className}::{$methodName}' must be a non-negative number";
        }

        // Validate file field (optional, can be string or null)
        if (isset($methodData['file']) && !is_string($methodData['file'])) {
            $errors[] = "Field 'file' in method '{$className}::{$methodName}' must be a string or null";
        }

        return $errors;
    }

    /**
     * Check if the data represents a valid baseline format.
     *
     * @param array<string, mixed> $data
     * @return bool
     */
    public function isValidBaseline(array $data): bool
    {
        try {
            $errors = $this->validate($data);
            return empty($errors);
        } catch (\Exception) {
            return false;
        }
    }
}
