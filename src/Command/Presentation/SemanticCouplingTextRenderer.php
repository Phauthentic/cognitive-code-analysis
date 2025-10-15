<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Command\Presentation;

use Phauthentic\CognitiveCodeAnalysis\Business\SemanticCoupling\SemanticCouplingCollection;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Text renderer for semantic coupling analysis console output.
 */
class SemanticCouplingTextRenderer
{
    public function __construct(
        private readonly OutputInterface $output
    ) {
    }

    /**
     * Render semantic coupling results in various formats.
     */
    public function render(SemanticCouplingCollection $couplings, string $viewType = 'top-pairs', int $limit = 20): void
    {
        switch ($viewType) {
            case 'top-pairs':
                $this->renderTopPairs($couplings, $limit);
                break;
            case 'matrix':
                $this->renderMatrix($couplings);
                break;
            case 'per-entity':
                $this->renderPerEntity($couplings, $limit);
                break;
            case 'summary':
                $this->renderSummary($couplings);
                break;
            default:
                $this->renderTopPairs($couplings, $limit);
        }
    }

    /**
     * Render top N most coupled pairs.
     */
    private function renderTopPairs(SemanticCouplingCollection $couplings, int $limit): void
    {
        $topCouplings = $couplings->getTop($limit);
        
        if ($topCouplings->count() === 0) {
            $this->output->writeln('<info>No semantic couplings found.</info>');
            return;
        }

        $granularity = $topCouplings->count() > 0 ? $topCouplings->current()->getGranularity() : 'unknown';
        
        $this->output->writeln("<info>Top {$limit} Most Coupled {$granularity}s</info>");
        $this->output->writeln('');

        foreach ($topCouplings as $index => $coupling) {
            $this->output->writeln(sprintf('<comment>%d.</comment> <info>Coupling Score: %s</info>', 
                $index + 1, 
                number_format($coupling->getScore(), 4)
            ));
            
            $this->output->writeln('');
            
            // Entity 1
            $this->output->writeln(sprintf('<comment>Entity 1:</comment> %s', $coupling->getEntity1()));
            $entity1Terms = $coupling->getEntity1Terms();
            if (!empty($entity1Terms)) {
                $this->output->writeln(sprintf('<comment>Terms:</comment> %s', implode(', ', $entity1Terms)));
            } else {
                $this->output->writeln('<comment>Terms:</comment> <fg=gray>(not available)</>');
            }
            
            $this->output->writeln('');
            
            // Entity 2
            $this->output->writeln(sprintf('<comment>Entity 2:</comment> %s', $coupling->getEntity2()));
            $entity2Terms = $coupling->getEntity2Terms();
            if (!empty($entity2Terms)) {
                $this->output->writeln(sprintf('<comment>Terms:</comment> %s', implode(', ', $entity2Terms)));
            } else {
                $this->output->writeln('<comment>Terms:</comment> <fg=gray>(not available)</>');
            }
            
            $this->output->writeln('');
            
            // Shared Terms
            $sharedTerms = $coupling->getSharedTerms();
            if (!empty($sharedTerms)) {
                $this->output->writeln(sprintf('<comment>Shared Terms:</comment> %s', implode(', ', $sharedTerms)));
            } else {
                $this->output->writeln('<comment>Shared Terms:</comment> <fg=gray>none</>');
            }
            
            $this->output->writeln('');
            $this->output->writeln(str_repeat('-', 80));
            $this->output->writeln('');
        }

        $this->renderSummaryStats($couplings);
    }

    /**
     * Render coupling matrix (for small datasets).
     */
    private function renderMatrix(SemanticCouplingCollection $couplings): void
    {
        if ($couplings->count() === 0) {
            $this->output->writeln('<info>No semantic couplings found.</info>');
            return;
        }

        // Build matrix
        $matrix = [];
        $entities = [];
        
        foreach ($couplings as $coupling) {
            $entity1 = $coupling->getEntity1();
            $entity2 = $coupling->getEntity2();
            
            if (!in_array($entity1, $entities, true)) {
                $entities[] = $entity1;
            }
            if (!in_array($entity2, $entities, true)) {
                $entities[] = $entity2;
            }
            
            $matrix[$entity1][$entity2] = $coupling->getScore();
            $matrix[$entity2][$entity1] = $coupling->getScore();
        }
        
        sort($entities);

        // Limit matrix size for readability
        if (count($entities) > 15) {
            $this->output->writeln('<comment>Matrix too large for display. Showing top 15 entities only.</comment>');
            $entities = array_slice($entities, 0, 15);
        }

        $this->output->writeln('<info>Semantic Coupling Matrix</info>');
        $this->output->writeln('');

        // Header row
        $header = sprintf('%-20s', '');
        foreach ($entities as $entity) {
            $header .= sprintf('%-8s', $this->truncateString($entity, 6));
        }
        $this->output->writeln($header);
        $this->output->writeln(str_repeat('-', strlen($header)));

        // Matrix rows
        foreach ($entities as $entity1) {
            $row = sprintf('%-20s', $this->truncateString($entity1, 18));
            foreach ($entities as $entity2) {
                $score = $matrix[$entity1][$entity2] ?? 0.0;
                $scoreStr = $score > 0 ? number_format($score, 2) : '-';
                $row .= sprintf('%-8s', $scoreStr);
            }
            $this->output->writeln($row);
        }

        $this->output->writeln('');
        $this->renderSummaryStats($couplings);
    }

    /**
     * Render couplings grouped by entity.
     */
    private function renderPerEntity(SemanticCouplingCollection $couplings, int $limit): void
    {
        if ($couplings->count() === 0) {
            $this->output->writeln('<info>No semantic couplings found.</info>');
            return;
        }

        // Group couplings by entity
        $entityCouplings = [];
        foreach ($couplings as $coupling) {
            $entityCouplings[$coupling->getEntity1()][] = $coupling;
            $entityCouplings[$coupling->getEntity2()][] = $coupling;
        }

        // Sort entities by average coupling
        $entityAverages = [];
        foreach ($entityCouplings as $entity => $entityCouplingList) {
            $totalScore = 0.0;
            foreach ($entityCouplingList as $coupling) {
                $totalScore += $coupling->getScore();
            }
            $entityAverages[$entity] = $totalScore / count($entityCouplingList);
        }
        arsort($entityAverages);

        $granularity = $couplings->count() > 0 ? $couplings->current()->getGranularity() : 'unknown';
        
        $this->output->writeln("<info>Couplings by {$granularity} (Top {$limit})</info>");
        $this->output->writeln('');

        $count = 0;
        foreach ($entityAverages as $entity => $avgScore) {
            if ($count >= $limit) {
                break;
            }

            $this->output->writeln("<comment>{$entity}</comment> (avg: " . number_format($avgScore, 4) . ")");
            
            $entityCouplingList = $entityCouplings[$entity];
            usort($entityCouplingList, fn($a, $b) => $b->getScore() <=> $a->getScore());
            
            foreach (array_slice($entityCouplingList, 0, 5) as $coupling) {
                $otherEntity = $coupling->getEntity1() === $entity ? $coupling->getEntity2() : $coupling->getEntity1();
                $sharedTerms = implode(', ', array_slice($coupling->getSharedTerms(), 0, 3));
                if (count($coupling->getSharedTerms()) > 3) {
                    $sharedTerms .= '...';
                }
                
                $this->output->writeln(sprintf(
                    '  → %-30s %-8s %s',
                    $this->truncateString($otherEntity, 28),
                    number_format($coupling->getScore(), 4),
                    $sharedTerms
                ));
            }
            
            $this->output->writeln('');
            $count++;
        }

        $this->renderSummaryStats($couplings);
    }

    /**
     * Render summary statistics.
     */
    private function renderSummary(SemanticCouplingCollection $couplings): void
    {
        if ($couplings->count() === 0) {
            $this->output->writeln('<info>No semantic couplings found.</info>');
            return;
        }

        $granularity = $couplings->count() > 0 ? $couplings->current()->getGranularity() : 'unknown';
        
        $this->output->writeln("<info>Semantic Coupling Summary ({$granularity} level)</info>");
        $this->output->writeln('');
        
        $this->renderSummaryStats($couplings);
        
        // Additional insights
        $this->output->writeln('');
        $this->output->writeln('<info>Insights:</info>');
        
        $highCoupling = $couplings->filterByThreshold(0.7);
        $mediumCoupling = $couplings->filterByThreshold(0.4)->filterByThreshold(0.7);
        
        $this->output->writeln("• High coupling pairs (≥0.7): {$highCoupling->count()}");
        $this->output->writeln("• Medium coupling pairs (0.4-0.7): {$mediumCoupling->count()}");
        $this->output->writeln("• Low coupling pairs (<0.4): " . ($couplings->count() - $highCoupling->count() - $mediumCoupling->count()));
    }

    /**
     * Render summary statistics.
     */
    private function renderSummaryStats(SemanticCouplingCollection $couplings): void
    {
        $this->output->writeln('<info>Summary Statistics:</info>');
        $this->output->writeln(sprintf('• Total couplings: %d', $couplings->count()));
        $this->output->writeln(sprintf('• Average score: %s', number_format($couplings->getAverageScore(), 4)));
        $this->output->writeln(sprintf('• Maximum score: %s', number_format($couplings->getMaxScore(), 4)));
        $this->output->writeln(sprintf('• Minimum score: %s', number_format($couplings->getMinScore(), 4)));
        $this->output->writeln(sprintf('• Median score: %s', number_format($couplings->getMedianScore(), 4)));
    }

    /**
     * Truncate string to specified length.
     */
    private function truncateString(string $string, int $length): string
    {
        if (strlen($string) <= $length) {
            return $string;
        }
        
        return substr($string, 0, $length - 3) . '...';
    }
}
