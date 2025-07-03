<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Churn\Exporter;

use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;

/**
 * Exports churn data as an SVG treemap.
 */
class SvgTreemapExporter implements DataExporterInterface
{
    /**
     * @param array<string, array<string, mixed>> $classes
     * @param string $filename
     * @throws CognitiveAnalysisException
     */
    public function export(array $classes, string $filename): void
    {
        $svg = $this->generateSvgTreemap($classes);

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
        $width = 1200;
        $height = 800;
        $padding = 2;

        // Prepare data: sort by churn descending, filter out zero churn
        $items = [];
        foreach ($classes as $class => $data) {
            $churn = (float)($data['churn'] ?? 0);
            if ($churn > 0) {
                $items[] = [
                    'class' => $class,
                    'churn' => $churn,
                    'score' => (float)($data['score'] ?? 0),
                ];
            }
        }
        usort($items, fn($a, $b) => $b['churn'] <=> $a['churn']);

        $totalChurn = array_sum(array_column($items, 'churn'));
        if ($totalChurn <= 0) {
            $totalChurn = 1;
        }

        // Recursively layout rectangles
        $rects = [];
        $this->layoutTreemap($items, 0, 0, $width, $height, $totalChurn, true, $rects, $padding);

        // Render SVG
        $svgRects = [];
        foreach ($rects as $rect) {
            $svgRects[] = sprintf(
                '<g><rect x="%.2f" y="%.2f" width="%.2f" height="%.2f" fill="%s" stroke="#222" stroke-width="1"/><title>%s&#10;Churn: %s&#10;Score: %s</title><text x="%.2f" y="%.2f" font-size="13" fill="#000">%s</text></g>',
                $rect['x'] + $padding,
                $rect['y'] + $padding,
                max(0, $rect['width'] - $padding * 2),
                max(0, $rect['height'] - $padding * 2),
                $this->scoreToColor($rect['score']),
                htmlspecialchars($rect['class']),
                $rect['churn'],
                $rect['score'],
                $rect['x'] + $padding + 4,
                $rect['y'] + 18,
                htmlspecialchars(mb_strimwidth($rect['class'], 0, 40, '…'))
            );
        }
        $rectsSvg = implode("\n", $svgRects);

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
     * Recursively layout rectangles for a slice-and-dice treemap.
     * @param array $items
     * @param float $x
     * @param float $y
     * @param float $width
     * @param float $height
     * @param float $totalChurn
     * @param bool $vertical
     * @param array $rects
     * @param int $padding
     */
    private function layoutTreemap(array $items, float $x, float $y, float $width, float $height, float $totalChurn, bool $vertical, array &$rects, int $padding): void
    {
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
        $accum = 0;
        $splitIdx = 0;
        foreach ($items as $i => $item) {
            $accum += $item['churn'];
            if ($accum >= $sum / 2) {
                $splitIdx = $i + 1;
                break;
            }
        }
        if ($splitIdx <= 0 || $splitIdx >= count($items)) {
            $splitIdx = 1;
        }

        $first = array_slice($items, 0, $splitIdx);
        $second = array_slice($items, $splitIdx);

        $firstSum = array_sum(array_column($first, 'churn'));
        $secondSum = array_sum(array_column($second, 'churn'));

        if ($vertical) {
            $w1 = $width * ($firstSum / $sum);
            $w2 = $width - $w1;
            $this->layoutTreemap($first, $x, $y, $w1, $height, $firstSum, !$vertical, $rects, $padding);
            $this->layoutTreemap($second, $x + $w1, $y, $w2, $height, $secondSum, !$vertical, $rects, $padding);
        } else {
            $h1 = $height * ($firstSum / $sum);
            $h2 = $height - $h1;
            $this->layoutTreemap($first, $x, $y, $width, $h1, $firstSum, !$vertical, $rects, $padding);
            $this->layoutTreemap($second, $x, $y + $h1, $width, $h2, $secondSum, !$vertical, $rects, $padding);
        }
    }

    /**
     * Maps a score (0-10) to a color from green to red.
     */
    private function scoreToColor(float $score): string
    {
        $score = max(0, min(10, $score));
        $r = (int)(255 * ($score / 10));
        $g = (int)(180 * (1 - $score / 10));
        $b = 80;
        return sprintf('rgb(%d,%d,%d)', $r, $g, $b);
    }
}
