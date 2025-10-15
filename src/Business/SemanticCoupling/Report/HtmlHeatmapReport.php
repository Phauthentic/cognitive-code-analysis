<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\SemanticCoupling\Report;

use Phauthentic\CognitiveCodeAnalysis\Business\SemanticCoupling\SemanticCouplingCollection;

/**
 * HTML heatmap report generator for semantic coupling analysis.
 */
class HtmlHeatmapReport extends AbstractReport
{
    /**
     * @throws \Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException
     */
    public function export(SemanticCouplingCollection $couplings, string $filename): void
    {
        $this->assertFileIsWritable($filename);

        $html = $this->generateHeatmapHtml($couplings);
        $this->writeFile($filename, $html);
    }

    /**
     * Generate HTML heatmap content.
     */
    private function generateHeatmapHtml(SemanticCouplingCollection $couplings): string
    {
        $granularity = $couplings->count() > 0 ? $couplings->current()->getGranularity() : 'unknown';
        
        // Build coupling matrix
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
        
        // Sort entities for consistent display
        sort($entities);
        
        // Generate SVG heatmap
        $svg = $this->generateSvgHeatmap($matrix, $entities);

        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Semantic Coupling Heatmap</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { background-color: #f4f4f4; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .heatmap-container { overflow-x: auto; margin: 20px 0; }
        .legend { display: flex; align-items: center; margin: 20px 0; }
        .legend-item { display: flex; align-items: center; margin-right: 20px; }
        .legend-color { width: 20px; height: 20px; margin-right: 5px; border: 1px solid #ccc; }
        .entity-label { font-size: 12px; transform: rotate(-45deg); transform-origin: left top; }
        .tooltip { position: absolute; background: rgba(0,0,0,0.8); color: white; padding: 5px; border-radius: 3px; font-size: 12px; pointer-events: none; z-index: 1000; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Semantic Coupling Heatmap</h1>
        <p><strong>Generated:</strong> ' . $this->getCurrentTimestamp() . '</p>
        <p><strong>Granularity:</strong> ' . htmlspecialchars($granularity) . '</p>
        <p><strong>Total Entities:</strong> ' . count($entities) . '</p>
        <p><strong>Total Couplings:</strong> ' . $couplings->count() . '</p>
    </div>

    <div class="legend">
        <div class="legend-item">
            <div class="legend-color" style="background-color: #d32f2f;"></div>
            <span>High Coupling (≥0.7)</span>
        </div>
        <div class="legend-item">
            <div class="legend-color" style="background-color: #ff9800;"></div>
            <span>Medium Coupling (0.4-0.7)</span>
        </div>
        <div class="legend-item">
            <div class="legend-color" style="background-color: #4caf50;"></div>
            <span>Low Coupling (<0.4)</span>
        </div>
        <div class="legend-item">
            <div class="legend-color" style="background-color: #f5f5f5;"></div>
            <span>No Coupling</span>
        </div>
    </div>

    <div class="heatmap-container">
        ' . $svg . '
    </div>

    <script>
        // Add tooltip functionality
        document.addEventListener("DOMContentLoaded", function() {
            const cells = document.querySelectorAll(".heatmap-cell");
            const tooltip = document.createElement("div");
            tooltip.className = "tooltip";
            document.body.appendChild(tooltip);

            cells.forEach(cell => {
                cell.addEventListener("mouseenter", function(e) {
                    const entity1 = this.getAttribute("data-entity1");
                    const entity2 = this.getAttribute("data-entity2");
                    const score = this.getAttribute("data-score");
                    
                    tooltip.innerHTML = `${entity1} ↔ ${entity2}<br>Score: ${score}`;
                    tooltip.style.left = e.pageX + 10 + "px";
                    tooltip.style.top = e.pageY - 10 + "px";
                    tooltip.style.display = "block";
                });

                cell.addEventListener("mouseleave", function() {
                    tooltip.style.display = "none";
                });

                cell.addEventListener("mousemove", function(e) {
                    tooltip.style.left = e.pageX + 10 + "px";
                    tooltip.style.top = e.pageY - 10 + "px";
                });
            });
        });
    </script>
</body>
</html>';

        return $html;
    }

    /**
     * Generate SVG heatmap.
     */
    private function generateSvgHeatmap(array $matrix, array $entities): string
    {
        $cellSize = 20;
        $padding = 5;
        $width = count($entities) * ($cellSize + $padding) + 100; // Extra space for labels
        $height = count($entities) * ($cellSize + $padding) + 100;

        $svg = '<svg width="' . $width . '" height="' . $height . '" xmlns="http://www.w3.org/2000/svg">';

        // Add entity labels on top
        foreach ($entities as $i => $entity) {
            $x = 100 + $i * ($cellSize + $padding) + $cellSize / 2;
            $y = 20;
            $svg .= '<text x="' . $x . '" y="' . $y . '" text-anchor="middle" class="entity-label" font-size="10">' . htmlspecialchars($entity) . '</text>';
        }

        // Add entity labels on left
        foreach ($entities as $i => $entity) {
            $x = 20;
            $y = 100 + $i * ($cellSize + $padding) + $cellSize / 2;
            $svg .= '<text x="' . $x . '" y="' . $y . '" text-anchor="end" dominant-baseline="middle" font-size="10">' . htmlspecialchars($entity) . '</text>';
        }

        // Add heatmap cells
        foreach ($entities as $i => $entity1) {
            foreach ($entities as $j => $entity2) {
                $x = 100 + $j * ($cellSize + $padding);
                $y = 100 + $i * ($cellSize + $padding);
                
                $score = $matrix[$entity1][$entity2] ?? 0.0;
                $color = $this->getScoreColor($score);
                
                $svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . $cellSize . '" height="' . $cellSize . '" 
                         fill="' . $color . '" stroke="#ccc" stroke-width="0.5" 
                         class="heatmap-cell" 
                         data-entity1="' . htmlspecialchars($entity1) . '" 
                         data-entity2="' . htmlspecialchars($entity2) . '" 
                         data-score="' . number_format($score, 4) . '" />';
            }
        }

        $svg .= '</svg>';

        return $svg;
    }

    /**
     * Get color based on coupling score.
     */
    private function getScoreColor(float $score): string
    {
        if ($score >= 0.7) {
            return '#d32f2f'; // Red
        } elseif ($score >= 0.4) {
            return '#ff9800'; // Orange
        } elseif ($score > 0.0) {
            return '#4caf50'; // Green
        } else {
            return '#f5f5f5'; // Light gray
        }
    }
}
