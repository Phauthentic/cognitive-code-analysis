<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Churn\Report;

use Phauthentic\CognitiveCodeAnalysis\Business\Churn\ChurnMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;

/**
 * Exports churn data as an SVG treemap.
 *
 * The size of the rectangles in the treemap is scaled proportionally to the "churn" value of each class.
 * Mathematical calculations are delegated to the TreemapMath class, which handles score normalization,
 * color mapping, and treemap layout calculations using a slice-and-dice algorithm.
 *
 * @SuppressWarnings("PHPMD.ShortVariable")
 */
class SvgTreemapReport extends AbstractReport
{
    private const SVG_WIDTH = 1200;
    private const SVG_HEIGHT = 800;
    private const PADDING = 2;

    private TreemapMath $treemapMath;

    public function __construct()
    {
        $this->treemapMath = new TreemapMath();
    }

    /**
     * @param string $filename
     * @throws CognitiveAnalysisException
     */
    public function export(ChurnMetricsCollection $metrics, string $filename): void
    {
        $this->assertFileIsWritable($filename);

        $svg = $this->generateSvgTreemap(metrics: $metrics);

        if (file_put_contents($filename, $svg) === false) {
            throw new CognitiveAnalysisException("Unable to write to file: $filename");
        }
    }

    /**
     * Generates a treemap SVG for the churn data.
     *
     * @return string
     */
    private function generateSvgTreemap(ChurnMetricsCollection $metrics): string
    {
        $items = $this->treemapMath->prepareItems($metrics->toArray());

        [$minScore, $maxScore] = $this->treemapMath->findScoreRange($items);

        $rects = $this->treemapMath->calculateTreemapLayout(
            items: $items,
            x: 0,
            y: 0,
            width: self::SVG_WIDTH,
            height: self::SVG_HEIGHT,
            vertical: true,
            padding: self::PADDING
        );

        $svgRects = $this->renderSvgRects(rects: $rects, minScore: $minScore, maxScore: $maxScore);

        return $this->wrapSvg(rectsSvg: $svgRects);
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
            $score = $this->resolveFloatValue($rect['score'] ?? null, 0.0);
            $normalizedScore = $this->treemapMath->normalizeScore(
                score: $score,
                minScore: $minScore,
                maxScore: $maxScore
            );
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
        $x = $this->resolveFloatValue($rect['x'] ?? null, 0.0) + self::PADDING;
        $y = $this->resolveFloatValue($rect['y'] ?? null, 0.0) + self::PADDING;
        $width = max(0, $this->resolveFloatValue($rect['width'] ?? null, 0.0) - self::PADDING * 2);
        $height = max(0, $this->resolveFloatValue($rect['height'] ?? null, 0.0) - self::PADDING * 2);
        $color = $this->treemapMath->scoreToColor(score: $normalizedScore);
        $className = is_string($rect['class'] ?? null) ? $rect['class'] : '';
        $class = htmlspecialchars($className);
        $churn = $this->resolveFloatValue($rect['churn'] ?? null, 0.0);
        $score = $this->resolveFloatValue($rect['score'] ?? null, 0.0);
        $textX = $x + 4;
        $textY = $y + 18;
        $label = htmlspecialchars(mb_strimwidth($className, 0, 40, '…'));

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

    private function resolveFloatValue(mixed $value, float $default): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        return $default;
    }
}
