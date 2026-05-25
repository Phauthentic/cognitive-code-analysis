<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\ChurnSpecifications;

use InvalidArgumentException;
use Phauthentic\CognitiveCodeAnalysis\Config\ConfigFileResolver;
use Symfony\Component\Console\Input\InputInterface;

class ChurnCommandContext
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

    public function getPath(): string
    {
        return $this->getRequiredStringArgument('path');
    }

    public function getVcsType(): string
    {
        return $this->getStringOptionWithDefault('vcs', 'git');
    }

    public function getSince(): string
    {
        return $this->getStringOptionWithDefault('since', '2000-01-01');
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
