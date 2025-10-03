<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Churn\Exporter;

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
class SvgTreemapExporter extends AbstractExporter
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
     * @param array<string, array<string, mixed>> $classes
     * @param string $filename
     * @throws CognitiveAnalysisException
     */
    public function export(array $classes, string $filename): void
    {
        $this->assertFileIsWritable($filename);

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
        $items = $this->treemapMath->prepareItems($classes);

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
            $normalizedScore = $this->treemapMath->normalizeScore(score: $rect['score'], minScore: $minScore, maxScore: $maxScore);
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
        $color = $this->treemapMath->scoreToColor(score: $normalizedScore);
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
}
