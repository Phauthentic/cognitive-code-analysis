<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\ChurnSpecifications;

use Symfony\Component\Console\Input\InputInterface;

class ChurnCommandContext
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

    public function getPath(): string
    {
        return $this->input->getArgument('path');
    }

    public function getVcsType(): string
    {
        return $this->input->getOption('vcs') ?? 'git';
    }

    public function getSince(): string
    {
        return $this->input->getOption('since') ?? '2000-01-01';
    }
}
