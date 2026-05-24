<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command;

use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;
use Phauthentic\CognitiveCodeAnalysis\Config\ConfigFileResolver;
use Phauthentic\CognitiveCodeAnalysis\Config\ConfigInitializer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'init',
    description: 'Create a default cca.yaml configuration file for cognitive code analysis.'
)]
class InitCommand extends Command
{
    private const OPTION_PATH = 'path';
    private const OPTION_SILENT = 'silent';
    private const OPTION_FORCE = 'force';

    private const DEFAULT_SCORE_THRESHOLD = 0.5;
    private const DEFAULT_GROUP_BY_CLASS = true;
    private const DEFAULT_SHOW_ONLY_METHODS_EXCEEDING_THRESHOLD = false;
    private const DEFAULT_SHOW_HALSTEAD_COMPLEXITY = false;
    private const DEFAULT_SHOW_CYCLOMATIC_COMPLEXITY = false;
    private const DEFAULT_SHOW_DETAILED_COGNITIVE_METRICS = true;

    public function __construct(
        private readonly ConfigInitializer $configInitializer,
        private readonly ConfigFileResolver $configFileResolver,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                name: self::OPTION_PATH,
                shortcut: 'p',
                mode: InputOption::VALUE_OPTIONAL,
                description: 'Target config file path',
                default: null,
            )
            ->addOption(
                name: self::OPTION_SILENT,
                mode: InputOption::VALUE_NONE,
                description: 'Skip interactive prompts and use default values',
            )
            ->addOption(
                name: self::OPTION_FORCE,
                shortcut: 'f',
                mode: InputOption::VALUE_NONE,
                description: 'Overwrite an existing config file',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);
        $targetPath = $this->resolveTargetPath($input);

        if (is_file($targetPath) && !$input->getOption(self::OPTION_FORCE)) {
            $style->error(sprintf(
                'Config file already exists at %s. Use --force to overwrite.',
                $targetPath
            ));

            return Command::FAILURE;
        }

        try {
            $overrides = $this->collectSettings($input, $style);
            $config = $this->configInitializer->createDefaultConfig($overrides);
            $this->configInitializer->writeConfigFile($targetPath, $config);
        } catch (CognitiveAnalysisException $exception) {
            $style->error($exception->getMessage());

            return Command::FAILURE;
        }

        $style->success(sprintf('Created config at: %s', $targetPath));

        return Command::SUCCESS;
    }

    private function resolveTargetPath(InputInterface $input): string
    {
        $path = $input->getOption(self::OPTION_PATH);

        if ($path !== null) {
            return $path;
        }

        return $this->configFileResolver->getDefaultPath();
    }

    /**
     * @return array<string, mixed>
     */
    private function collectSettings(InputInterface $input, SymfonyStyle $style): array
    {
        if ($input->getOption(self::OPTION_SILENT) || !$input->isInteractive()) {
            return $this->getDefaultOverrides();
        }

        return [
            'cognitive' => [
                'scoreThreshold' => $this->askScoreThreshold($style),
                'showOnlyMethodsExceedingThreshold' => $this->askBooleanSetting(
                    $style,
                    'When enabled, only methods whose cognitive score exceeds the threshold are shown in output. '
                    . 'Useful to focus on the most complex methods.',
                    'Show only methods exceeding the threshold?',
                    self::DEFAULT_SHOW_ONLY_METHODS_EXCEEDING_THRESHOLD,
                ),
                'showHalsteadComplexity' => $this->askBooleanSetting(
                    $style,
                    'When enabled, Halstead complexity metrics are included in output. '
                    . 'Halstead measures program length and vocabulary as a different aspect of complexity.',
                    'Show Halstead complexity metrics?',
                    self::DEFAULT_SHOW_HALSTEAD_COMPLEXITY,
                ),
                'showCyclomaticComplexity' => $this->askBooleanSetting(
                    $style,
                    'When enabled, cyclomatic complexity metrics are included in output. '
                    . 'Cyclomatic complexity measures the number of independent paths through code.',
                    'Show cyclomatic complexity metrics?',
                    self::DEFAULT_SHOW_CYCLOMATIC_COMPLEXITY,
                ),
                'showDetailedCognitiveMetrics' => $this->askBooleanSetting(
                    $style,
                    'When enabled, individual metric columns (line count, arguments, returns, etc.) '
                    . 'are shown in table output. When disabled, only the overall cognitive score is displayed.',
                    'Show detailed cognitive metrics?',
                    self::DEFAULT_SHOW_DETAILED_COGNITIVE_METRICS,
                ),
                'groupByClass' => $this->askBooleanSetting(
                    $style,
                    'When enabled, results are grouped by class in separate tables. '
                    . 'When disabled, all methods appear in one flat table sorted by complexity score.',
                    'Group results by class?',
                    self::DEFAULT_GROUP_BY_CLASS,
                ),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getDefaultOverrides(): array
    {
        return [
            'cognitive' => [
                'scoreThreshold' => self::DEFAULT_SCORE_THRESHOLD,
                'showOnlyMethodsExceedingThreshold' => self::DEFAULT_SHOW_ONLY_METHODS_EXCEEDING_THRESHOLD,
                'showHalsteadComplexity' => self::DEFAULT_SHOW_HALSTEAD_COMPLEXITY,
                'showCyclomaticComplexity' => self::DEFAULT_SHOW_CYCLOMATIC_COMPLEXITY,
                'showDetailedCognitiveMetrics' => self::DEFAULT_SHOW_DETAILED_COGNITIVE_METRICS,
                'groupByClass' => self::DEFAULT_GROUP_BY_CLASS,
            ],
        ];
    }

    private function askScoreThreshold(SymfonyStyle $style): float
    {
        $style->writeln(
            'Methods with a score above this threshold are considered complex. '
            . 'It is used for highlighting in reports and for filtering when only showing methods '
            . 'exceeding the threshold.'
        );

        $value = $style->ask(
            'Score threshold',
            (string) self::DEFAULT_SCORE_THRESHOLD,
            static function (mixed $answer): float {
                if (!is_numeric($answer)) {
                    throw new CognitiveAnalysisException('Score threshold must be a number.');
                }

                $threshold = (float) $answer;
                if ($threshold <= 0) {
                    throw new CognitiveAnalysisException('Score threshold must be greater than 0.');
                }

                return $threshold;
            }
        );

        return (float) $value;
    }

    private function askBooleanSetting(
        SymfonyStyle $style,
        string $explanation,
        string $question,
        bool $default,
    ): bool {
        $style->writeln($explanation);

        return (bool) $style->confirm($question, $default);
    }
}
