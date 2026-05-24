<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Presentation;

use Phauthentic\CognitiveCodeAnalysis\Config\CognitiveConfig;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Prints config source and cache status at the start of analyse and churn commands.
 */
class RuntimeStatusRenderer
{
    public function render(OutputInterface $output, ?string $configFile, CognitiveConfig $config): void
    {
        $output->writeln('Config: ' . $this->formatConfigSource($configFile));
        $output->writeln('Cache: ' . $this->formatCacheStatus($config));
        $output->writeln('');
    }

    private function formatConfigSource(?string $configFile): string
    {
        if ($configFile === null) {
            return 'built-in';
        }

        $workingDirectory = getcwd();
        if ($workingDirectory === false) {
            return $configFile;
        }

        $prefix = $workingDirectory . DIRECTORY_SEPARATOR;
        if (str_starts_with($configFile, $prefix)) {
            return './' . substr($configFile, strlen($prefix));
        }

        return $configFile;
    }

    private function formatCacheStatus(CognitiveConfig $config): string
    {
        return $config->cache?->enabled === true ? 'enabled' : 'disabled';
    }
}
