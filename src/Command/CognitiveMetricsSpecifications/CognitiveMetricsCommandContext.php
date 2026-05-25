<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\CognitiveMetricsSpecifications;

use InvalidArgumentException;
use Phauthentic\CognitiveCodeAnalysis\Config\ConfigFileResolver;
use Symfony\Component\Console\Input\InputInterface;

class CognitiveMetricsCommandContext
{
    public function __construct(
        private InputInterface $input,
        private ConfigFileResolver $configFileResolver,
    ) {
    }

    public function getConfigFile(): ?string
    {
        return $this->configFileResolver->resolve($this->getOptionalStringOption('config'));
    }

    public function hasConfigFile(): bool
    {
        return $this->getConfigFile() !== null;
    }

    public function getCoberturaFile(): ?string
    {
        return $this->getOptionalStringOption('coverage-cobertura');
    }

    public function getCloverFile(): ?string
    {
        return $this->getOptionalStringOption('coverage-clover');
    }

    public function hasCoberturaFile(): bool
    {
        return $this->getCoberturaFile() !== null;
    }

    public function hasCloverFile(): bool
    {
        return $this->getCloverFile() !== null;
    }

    public function getCoverageFile(): ?string
    {
        return $this->getCoberturaFile() ?? $this->getCloverFile();
    }

    public function getCoverageFormat(): ?string
    {
        if ($this->hasCoberturaFile()) {
            return 'cobertura';
        }
        if ($this->hasCloverFile()) {
            return 'clover';
        }
        return null;
    }

    public function getReportType(): ?string
    {
        return $this->getOptionalStringOption('report-type');
    }

    public function getReportFile(): ?string
    {
        return $this->getOptionalStringOption('report-file');
    }

    public function hasReportOptions(): bool
    {
        return $this->getReportType() !== null || $this->getReportFile() !== null;
    }

    public function getSortBy(): ?string
    {
        return $this->getOptionalStringOption('sort-by');
    }

    public function getSortOrder(): string
    {
        return $this->getStringOptionWithDefault('sort-order', 'asc');
    }

    public function hasSortingOptions(): bool
    {
        return $this->getSortBy() !== null;
    }

    public function getBaselineFile(): ?string
    {
        return $this->getOptionalStringOption('baseline');
    }

    public function hasBaselineFile(): bool
    {
        return $this->getBaselineFile() !== null;
    }

    public function getGenerateBaseline(): ?string
    {
        $value = $this->input->getOption('generate-baseline');

        // For VALUE_NONE, true means option is present, false means not present
        if ($value === true) {
            return ''; // Auto-generate filename
        }

        return null; // Option not present
    }

    public function hasGenerateBaseline(): bool
    {
        $value = $this->input->getOption('generate-baseline');

        // For VALUE_NONE, true means option is present, false means not present
        return $value === true;
    }

    public function getBaselineOutputPath(): string
    {
        $filename = $this->getGenerateBaseline();

        if (empty($filename)) {
            // Generate timestamped filename
            $timestamp = date('Y-m-d_H-i-s');
            $filename = "./.phpcca/baseline/baseline-{$timestamp}.json";
        }

        return $filename;
    }

    /**
     * @return array<string>
     */
    public function getPaths(): array
    {
        $pathInput = $this->getRequiredStringArgument('path');
        return array_map('trim', explode(',', $pathInput));
    }

    public function getDebug(): bool
    {
        return (bool) $this->input->getOption('debug');
    }

    private function getOptionalStringOption(string $name): ?string
    {
        $value = $this->input->getOption($name);

        return is_string($value) ? $value : null;
    }

    private function getStringOptionWithDefault(string $name, string $default): string
    {
        $value = $this->input->getOption($name);

        return is_string($value) ? $value : $default;
    }

    private function getRequiredStringArgument(string $name): string
    {
        $value = $this->input->getArgument($name);
        if (!is_string($value)) {
            throw new InvalidArgumentException(sprintf('Argument "%s" must be a string.', $name));
        }

        return $value;
    }
}
