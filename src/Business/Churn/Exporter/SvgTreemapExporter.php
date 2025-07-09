<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Churn\Exporter;

use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;

/**
 * Exports churn data as an SVG treemap.
 *
 * The size of the rectangles in the treemap is scaled proportionally to the "churn" value of each class. The
 * layoutTreemap method calculates the dimensions of each rectangle based on the relative churn values of the items,
 * ensuring that the total area of the rectangles corresponds to the total churn.
 *
 * The algorithm uses a slice-and-dice approach to divide the space recursively, alternating between vertical
 * and horizontal splits.
 *
 * @SuppressWarnings("PHPMD.ShortVariable")
 */
class SvgTreemapExporter implements DataExporterInterface
{
    private const SVG_WIDTH = 1200;
    private const SVG_HEIGHT = 800;
    private const PADDING = 2;
    private const COLOR_MIN = 0;
    private const COLOR_MAX = 10;

    /**
     * @param array<string, array<string, mixed>> $classes
     * @param string $filename
     * @throws CognitiveAnalysisException
     */
    public function export(array $classes, string $filename): void
    {
        $svg = $this->generateSvgTreemap(classes: $classes);

        if (file_put_contents($filename, $svg) === false) {
            throw new CognitiveAnalysisException("Unable to write to file: $filename");
        }
    }

    /**
     * Generates a treemap SVG for the churn data.
     *
     * @param array<string, array<string, mixed>> $classes
     * @return string
     */
    private function generateSvgTreemap(array $classes): string
    {
        $items = $this->prepareItems($classes);

        [$minScore, $maxScore] = $this->findScoreRange($items);

        $rects = [];
        $this->layoutTreemap(
            items: $items,
            x: 0,
            y: 0,
            width: self::SVG_WIDTH,
            height: self::SVG_HEIGHT,
            vertical: true,
            rects: $rects,
            padding: self::PADDING
        );

        $svgRects = $this->renderSvgRects(rects: $rects, minScore: $minScore, maxScore: $maxScore);

        return $this->wrapSvg(rectsSvg: $svgRects);
    }

    /**
     * Prepares and filters items for the treemap.
     *
     * @param array<string, array<string, mixed>> $classes
     * @return array<int, array{class: string, churn: float, score: float}>
     */
    private function prepareItems(array $classes): array
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
    private function findScoreRange(array $items): array
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
     * Renders SVG rectangles for the treemap.
     *
     * @param array<int, array<string, mixed>> $rects
     * @param float $minScore
     * @param float $maxScore
     * @return string
     */
    private function renderSvgRects(array $rects, float $minScore, float $maxScore): string
    {
        $svgRects = [];
        foreach ($rects as $rect) {
            $normalizedScore = $this->normalizeScore(score: $rect['score'], minScore: $minScore, maxScore: $maxScore);
            $svgRects[] = $this->renderSvgRect(rect: $rect, normalizedScore: $normalizedScore);
        }

        return implode("\n", $svgRects);
    }

    /**
     * Renders a single SVG rectangle.
     *
     * @param array<string, mixed> $rect
     * @param float $normalizedScore
     * @return string
     */
    private function renderSvgRect(array $rect, float $normalizedScore): string
    {
        $x = $rect['x'] + self::PADDING;
        $y = $rect['y'] + self::PADDING;
        $width = max(0, $rect['width'] - self::PADDING * 2);
        $height = max(0, $rect['height'] - self::PADDING * 2);
        $color = $this->scoreToColor(score: $normalizedScore);
        $class = htmlspecialchars($rect['class']);
        $churn = $rect['churn'];
        $score = $rect['score'];
        $textX = $x + 4;
        $textY = $y + 18;
        $label = htmlspecialchars(mb_strimwidth($rect['class'], 0, 40, '…'));

        return sprintf(
            '<g><rect x="%.2f" y="%.2f" width="%.2f" height="%.2f" fill="%s" stroke="#222" stroke-width="1"/><title>%s&#10;Churn: %s&#10;Score: %s</title><text x="%.2f" y="%.2f" font-size="13" fill="#000">%s</text></g>',
            $x,
            $y,
            $width,
            $height,
            $color,
            $class,
            $churn,
            $score,
            $textX,
            $textY,
            $label
        );
    }

    /**
     * Wraps SVG rectangles in the SVG document.
     *
     * @param string $rectsSvg
     * @return string
     */
    private function wrapSvg(string $rectsSvg): string
    {
        $width = self::SVG_WIDTH;
        $height = self::SVG_HEIGHT;
        return <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg width="{$width}" height="{$height}" xmlns="http://www.w3.org/2000/svg">
    <style>
        text { pointer-events: none; font-family: Arial, sans-serif; }
        rect:hover { stroke: #000; stroke-width: 2; }
    </style>
    <rect x="0" y="0" width="{$width}" height="{$height}" fill="#f8f9fa"/>
    <g>
        <text x="20" y="30" font-size="28" fill="#333">Churn Treemap</text>
    </g>
    <g>
        {$rectsSvg}
    </g>
</svg>
SVG;
    }

    /**
     * Normalizes a score to a 0-10 range for color mapping.
     *
     * @param float $score
     * @param float $minScore
     * @param float $maxScore
     * @return float
     */
    private function normalizeScore(float $score, float $minScore, float $maxScore): float
    {
        if ($maxScore > $minScore) {
            return self::COLOR_MAX * ($score - $minScore) / ($maxScore - $minScore);
        }

        return 0.0;
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

    /**
     * Maps a score (0-10) to a color from green to red.
     *
     * @param float $score
     * @return string
     */
    private function scoreToColor(float $score): string
    {
        $score = max(self::COLOR_MIN, min(self::COLOR_MAX, $score));
        $r = (int)(255 * ($score / self::COLOR_MAX));
        $g = (int)(180 * (1 - $score / self::COLOR_MAX));
        $b = 80;

        return sprintf('rgb(%d,%d,%d)', $r, $g, $b);
    }
}
