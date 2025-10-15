<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\SemanticAnalysisSpecifications;

use Symfony\Component\Console\Input\InputInterface;

/**
 * Context for semantic analysis command validation.
 */
class SemanticAnalysisCommandContext
{
    public function __construct(
        private readonly InputInterface $input
    ) {
    }

    public function getPath(): string
    {
        return $this->input->getArgument('path') ?? '';
    }

    public function getGranularity(): string
    {
        return $this->input->getOption('granularity') ?? 'file';
    }

    public function getThreshold(): ?float
    {
        $threshold = $this->input->getOption('threshold');
        return $threshold !== null ? (float)$threshold : null;
    }

    public function getLimit(): int
    {
        return (int)($this->input->getOption('limit') ?? 20);
    }

    public function getViewType(): string
    {
        return $this->input->getOption('view') ?? 'top-pairs';
    }

    public function hasReportOptions(): bool
    {
        return $this->input->getOption('report-type') !== null;
    }

    public function getReportType(): ?string
    {
        return $this->input->getOption('report-type');
    }

    public function getReportFile(): ?string
    {
        return $this->input->getOption('report-file');
    }

    public function hasConfigFile(): bool
    {
        return $this->input->getOption('config') !== null;
    }

    public function getConfigFile(): ?string
    {
        return $this->input->getOption('config');
    }

    public function isDebug(): bool
    {
        return (bool)$this->input->getOption('debug');
    }

    public function getInput(): InputInterface
    {
        return $this->input;
    }
}
