<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Churn\Report;

/**
 * Handles mathematical operations for treemap generation.
 *
 * This class contains all the mathematical calculations needed for:
 * - Score normalization and color mapping
 * - Treemap layout calculations using slice-and-dice algorithm
 * - Data preparation and sorting
 *
 * @SuppressWarnings("PHPMD.ShortVariable")
 */
class TreemapMath
{
    private const COLOR_MIN = 0;
    private const COLOR_MAX = 10;

    /**
     * Prepares and filters items for the treemap.
     *
     * @param array<string, array<string, mixed>> $classes
     * @return array<int, array{class: string, churn: float, score: float}>
     */
    public function prepareItems(array $classes): array
    {
        $items = [];
        foreach ($classes as $class => $data) {
            $churn = (float)($data['churn'] ?? 0);
            $score = (float)($data['score'] ?? 0);
            if ($churn > 0) {
                $items[] = [
                    'class' => $class,
                    'churn' => $churn,
                    'score' => $score,
                ];
            }
        }
        usort($items, fn($a, $b) => $b['churn'] <=> $a['churn']);

        return $items;
    }

    /**
     * Finds the minimum and maximum score for normalization.
     *
     * @param array<int, array{class: string, churn: float, score: float}> $items
     * @return array{float, float}
     */
    public function findScoreRange(array $items): array
    {
        $scores = array_column($items, 'score');
        if (empty($scores)) {
            return [self::COLOR_MIN, self::COLOR_MAX];
        }

        $minScore = min($scores);
        $maxScore = max($scores);

        if ($minScore === $maxScore) {
            return [self::COLOR_MIN, self::COLOR_MAX];
        }

        return [$minScore, $maxScore];
    }

    /**
     * Normalizes a score to a 0-10 range for color mapping.
     *
     * @param float $score
     * @param float $minScore
     * @param float $maxScore
     * @return float
     */
    public function normalizeScore(float $score, float $minScore, float $maxScore): float
    {
        if ($maxScore > $minScore) {
            return self::COLOR_MAX * ($score - $minScore) / ($maxScore - $minScore);
        }

        return 0.0;
    }

    /**
     * Maps a score (0-10) to a color from green to red.
     *
     * @param float $score
     * @return string
     */
    public function scoreToColor(float $score): string
    {
        $score = max(self::COLOR_MIN, min(self::COLOR_MAX, $score));
        $r = (int)(255 * ($score / self::COLOR_MAX));
        $g = (int)(180 * (1 - $score / self::COLOR_MAX));
        $b = 80;

        return sprintf('rgb(%d,%d,%d)', $r, $g, $b);
    }

    /**
     * Calculates treemap layout using slice-and-dice algorithm.
     *
     * @param array<int, array{class: string, churn: float, score: float}> $items
     * @param float $x
     * @param float $y
     * @param float $width
     * @param float $height
     * @param bool $vertical
     * @param int $padding
     * @return array<int, array<string, mixed>>
     */
    public function calculateTreemapLayout(
        array $items,
        float $x,
        float $y,
        float $width,
        float $height,
        bool $vertical,
        int $padding
    ): array {
        $rects = [];
        $this->layoutTreemap(
            items: $items,
            x: $x,
            y: $y,
            width: $width,
            height: $height,
            vertical: $vertical,
            rects: $rects,
            padding: $padding
        );

        return $rects;
    }

    /**
     * Recursively layout rectangles for a slice-and-dice treemap.
     *
     * @param array<int, array{class: string, churn: float, score: float}> $items
     * @param float $x
     * @param float $y
     * @param float $width
     * @param float $height
     * @param bool $vertical
     * @param array<int, array<string, mixed>> $rects
     * @param int $padding
     * @return void
     */
    private function layoutTreemap(
        array $items,
        float $x,
        float $y,
        float $width,
        float $height,
        bool $vertical,
        array &$rects,
        int $padding
    ): void {
        if (empty($items)) {
            return;
        }

        if (count($items) === 1) {
            $item = $items[0];
            $rects[] = [
                'x' => $x,
                'y' => $y,
                'width' => $width,
                'height' => $height,
                'class' => $item['class'],
                'churn' => $item['churn'],
                'score' => $item['score'],
            ];

            return;
        }

        $sum = array_sum(array_column($items, 'churn'));
        $splitIdx = $this->findSplitIndex(
            items: $items,
            sum: $sum
        );

        $first = array_slice($items, 0, $splitIdx);
        $second = array_slice($items, $splitIdx);

        $firstSum = array_sum(array_column($first, 'churn'));

        if ($vertical) {
            $w1 = $width * ($firstSum / $sum);
            $w2 = $width - $w1;
            $this->layoutTreemap(
                items: $first,
                x: $x,
                y: $y,
                width: $w1,
                height: $height,
                vertical: false,
                rects: $rects,
                padding: $padding
            );
            $this->layoutTreemap(
                items: $second,
                x: $x + $w1,
                y: $y,
                width: $w2,
                height: $height,
                vertical: false,
                rects: $rects,
                padding: $padding
            );
            return;
        }

        $h1 = $height * ($firstSum / $sum);
        $h2 = $height - $h1;
        $this->layoutTreemap(
            items: $first,
            x: $x,
            y: $y,
            width: $width,
            height: $h1,
            vertical: true,
            rects: $rects,
            padding: $padding
        );
        $this->layoutTreemap(
            items: $second,
            x: $x,
            y: $y + $h1,
            width: $width,
            height: $h2,
            vertical: true,
            rects: $rects,
            padding: $padding
        );
    }

    /**
     * Finds the index to split the items for the treemap layout.
     *
     * @param array<int, array{class: string, churn: float, score: float}> $items
     * @param float $sum
     * @return int
     */
    private function findSplitIndex(array $items, float $sum): int
    {
        $accum = 0;
        foreach ($items as $i => $item) {
            $accum += $item['churn'];
            if ($accum >= $sum / 2) {
                $splitIdx = $i + 1;
                return ($splitIdx <= 0 || $splitIdx >= count($items)) ? 1 : $splitIdx;
            }
        }

        return 1;
    }
}
