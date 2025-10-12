<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\CognitiveMetricsSpecifications;

use Symfony\Component\Console\Input\InputInterface;

class CognitiveMetricsCommandContext
{
    public function __construct(
        private InputInterface $input
    ) {
    }

    public function getConfigFile(): ?string
    {
        return $this->input->getOption('config');
    }

    public function hasConfigFile(): bool
    {
        return $this->getConfigFile() !== null;
    }

    public function getCoberturaFile(): ?string
    {
        return $this->input->getOption('coverage-cobertura');
    }

    public function getCloverFile(): ?string
    {
        return $this->input->getOption('coverage-clover');
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
        return $this->input->getOption('report-type');
    }

    public function getReportFile(): ?string
    {
        return $this->input->getOption('report-file');
    }

    public function hasReportOptions(): bool
    {
        return $this->getReportType() !== null || $this->getReportFile() !== null;
    }

    public function getSortBy(): ?string
    {
        return $this->input->getOption('sort-by');
    }

    public function getSortOrder(): string
    {
        return $this->input->getOption('sort-order') ?? 'asc';
    }

    public function hasSortingOptions(): bool
    {
        return $this->getSortBy() !== null;
    }

    public function getBaselineFile(): ?string
    {
        return $this->input->getOption('baseline');
    }

    public function hasBaselineFile(): bool
    {
        return $this->getBaselineFile() !== null;
    }

    /**
     * @return array<string>
     */
    public function getPaths(): array
    {
        $pathInput = $this->input->getArgument('path');
        return array_map('trim', explode(',', $pathInput));
    }

    public function getDebug(): bool
    {
        return (bool) $this->input->getOption('debug');
    }
}
