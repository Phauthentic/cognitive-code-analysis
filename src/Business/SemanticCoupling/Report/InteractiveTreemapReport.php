<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\SemanticCoupling\Report;

use Phauthentic\CognitiveCodeAnalysis\Business\SemanticCoupling\SemanticCouplingCollection;
use Phauthentic\CognitiveCodeAnalysis\Business\Churn\Report\TreemapMath;

/**
 * Interactive treemap report for semantic coupling analysis.
 * 
 * Allows users to select terms and visualize which areas of the program
 * share those terms using color gradients (red for high sharing, blue for low).
 */
class InteractiveTreemapReport extends AbstractReport
{
    private const SVG_WIDTH = 1400;
    private const SVG_HEIGHT = 900;
    private const PADDING = 2;
    private const RECT_MIN_SIZE = 20;

    private TreemapMath $treemapMath;

    public function __construct()
    {
        $this->treemapMath = new TreemapMath();
    }

    /**
     * @throws \Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException
     */
    public function export(SemanticCouplingCollection $couplings, string $filename): void
    {
        $this->assertFileIsWritable($filename);

        $html = $this->generateInteractiveTreemap($couplings);
        $this->writeFile($filename, $html);
    }

    /**
     * Generate interactive treemap HTML.
     */
    private function generateInteractiveTreemap(SemanticCouplingCollection $couplings): string
    {
        $granularity = $couplings->count() > 0 ? $couplings->current()->getGranularity() : 'unknown';
        
        // Extract all unique terms from couplings
        $allTerms = $this->extractAllTerms($couplings);
        
        // Prepare entity data for treemap
        $entityData = $this->prepareEntityData($couplings);
        
        // Calculate treemap layout
        $rects = $this->calculateTreemapLayout($entityData);
        
        // Generate SVG treemap
        $svg = $this->generateSvgTreemap($rects, $entityData);
        
        // Generate HTML with interactive controls
        return $this->wrapInHtml($svg, $allTerms, $granularity, $entityData);
    }

    /**
     * Extract all unique terms from couplings.
     */
    private function extractAllTerms(SemanticCouplingCollection $couplings): array
    {
        $terms = [];
        foreach ($couplings as $coupling) {
            $terms = array_merge($terms, $coupling->getEntity1Terms(), $coupling->getEntity2Terms());
        }
        
        $uniqueTerms = array_unique($terms);
        sort($uniqueTerms);
        return $uniqueTerms;
    }

    /**
     * Prepare entity data for treemap calculation.
     */
    private function prepareEntityData(SemanticCouplingCollection $couplings): array
    {
        $entities = [];
        $entityTerms = [];
        
        // Collect all entities and their terms
        foreach ($couplings as $coupling) {
            $entity1 = $coupling->getEntity1();
            $entity2 = $coupling->getEntity2();
            
            if (!isset($entities[$entity1])) {
                $entities[$entity1] = [
                    'name' => $entity1,
                    'score' => 0,
                    'terms' => []
                ];
            }
            
            if (!isset($entities[$entity2])) {
                $entities[$entity2] = [
                    'name' => $entity2,
                    'score' => 0,
                    'terms' => []
                ];
            }
            
            // Store terms for each entity
            $entities[$entity1]['terms'] = array_unique(array_merge(
                $entities[$entity1]['terms'],
                $coupling->getEntity1Terms()
            ));
            
            $entities[$entity2]['terms'] = array_unique(array_merge(
                $entities[$entity2]['terms'],
                $coupling->getEntity2Terms()
            ));
            
            // Use max coupling score as entity score
            $entities[$entity1]['score'] = max($entities[$entity1]['score'], $coupling->getScore());
            $entities[$entity2]['score'] = max($entities[$entity2]['score'], $coupling->getScore());
        }
        
        return array_values($entities);
    }

    /**
     * Calculate treemap layout using TreemapMath.
     */
    private function calculateTreemapLayout(array $entityData): array
    {
        // Convert to format expected by TreemapMath
        $items = [];
        foreach ($entityData as $entity) {
            $items[] = [
                'class' => $entity['name'],
                'score' => $entity['score'],
                'churn' => $entity['score'], // Use score as churn for size calculation
                'terms' => $entity['terms']
            ];
        }
        
        [$minScore, $maxScore] = $this->treemapMath->findScoreRange($items);
        
        return $this->treemapMath->calculateTreemapLayout(
            items: $items,
            x: 0,
            y: 0,
            width: self::SVG_WIDTH,
            height: self::SVG_HEIGHT,
            vertical: true,
            padding: self::PADDING
        );
    }

    /**
     * Generate SVG treemap.
     */
    private function generateSvgTreemap(array $rects, array $entityData): string
    {
        $svgRects = [];
        
        foreach ($rects as $rect) {
            $entityName = $rect['class'];
            $entity = $this->findEntityByName($entityName, $entityData);
            
            if ($entity === null) {
                continue;
            }
            
            $svgRects[] = $this->renderSvgRect($rect, $entity);
        }
        
        return implode("\n", $svgRects);
    }

    /**
     * Find entity by name in entity data.
     */
    private function findEntityByName(string $name, array $entityData): ?array
    {
        foreach ($entityData as $entity) {
            if ($entity['name'] === $name) {
                return $entity;
            }
        }
        return null;
    }

    /**
     * Render a single SVG rectangle.
     */
    private function renderSvgRect(array $rect, array $entity): string
    {
        $x = $rect['x'] + self::PADDING;
        $y = $rect['y'] + self::PADDING;
        $width = max(self::RECT_MIN_SIZE, $rect['width'] - self::PADDING * 2);
        $height = max(self::RECT_MIN_SIZE, $rect['height'] - self::PADDING * 2);
        
        $entityName = htmlspecialchars($rect['class']);
        $termsJson = htmlspecialchars(json_encode($entity['terms']));
        $textX = $x + 4;
        $textY = $y + 16;
        $label = htmlspecialchars(mb_strimwidth($entityName, 0, 30, '…'));
        
        return sprintf(
            '<g class="treemap-rect" data-entity="%s" data-terms=\'%s\'>' .
            '<rect x="%.2f" y="%.2f" width="%.2f" height="%.2f" fill="#e0e0e0" stroke="#333" stroke-width="1"/>' .
            '<title>%s&#10;Terms: %s</title>' .
            '<text x="%.2f" y="%.2f" font-size="12" fill="#000">%s</text>' .
            '</g>',
            $entityName,
            $termsJson,
            $x,
            $y,
            $width,
            $height,
            $entityName,
            implode(', ', $entity['terms']),
            $textX,
            $textY,
            $label
        );
    }

    /**
     * Wrap SVG in interactive HTML.
     */
    private function wrapInHtml(string $svg, array $allTerms, string $granularity, array $entityData): string
    {
        $termsJson = json_encode($allTerms, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $entityDataJson = json_encode($entityData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $totalEntities = count($entityData);
        $totalTerms = count($allTerms);
        $svgWidth = self::SVG_WIDTH;
        $svgHeight = self::SVG_HEIGHT;
        
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Semantic Coupling Interactive Treemap</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 20px; 
            background-color: #f5f5f5;
        }
        .header { 
            background: white; 
            padding: 20px; 
            border-radius: 8px; 
            margin-bottom: 20px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .controls { 
            background: white; 
            padding: 20px; 
            border-radius: 8px; 
            margin-bottom: 20px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .term-selector { 
            display: flex; 
            flex-wrap: wrap; 
            gap: 10px; 
            margin-bottom: 15px;
        }
        .term-checkbox { 
            display: flex; 
            align-items: center; 
            background: #f0f0f0; 
            padding: 8px 12px; 
            border-radius: 20px; 
            cursor: pointer; 
            transition: all 0.2s;
        }
        .term-checkbox:hover { 
            background: #e0e0e0; 
        }
        .term-checkbox.selected { 
            background: #007bff; 
            color: white; 
        }
        .term-checkbox input { 
            margin-right: 8px; 
        }
        .legend { 
            display: flex; 
            align-items: center; 
            gap: 20px; 
            margin-top: 15px;
        }
        .legend-item { 
            display: flex; 
            align-items: center; 
            gap: 8px;
        }
        .legend-color { 
            width: 20px; 
            height: 20px; 
            border-radius: 3px;
        }
        .treemap-container { 
            background: white; 
            padding: 20px; 
            border-radius: 8px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: auto;
        }
        .treemap-rect { 
            cursor: pointer; 
            transition: all 0.2s;
        }
        .treemap-rect:hover { 
            stroke: #000 !important; 
            stroke-width: 3 !important; 
        }
        .stats { 
            margin-top: 15px; 
            font-size: 14px; 
            color: #666;
        }
        .clear-selection { 
            background: #dc3545; 
            color: white; 
            border: none; 
            padding: 8px 16px; 
            border-radius: 4px; 
            cursor: pointer; 
            margin-left: 10px;
        }
        .clear-selection:hover { 
            background: #c82333; 
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Semantic Coupling Interactive Treemap</h1>
        <p><strong>Granularity:</strong> {$granularity} | <strong>Total Entities:</strong> {$totalEntities} | <strong>Total Terms:</strong> {$totalTerms}</p>
    </div>

    <div class="controls">
        <h3>Select Terms to Highlight</h3>
        <div class="term-selector" id="termSelector">
            <!-- Terms will be populated by JavaScript -->
        </div>
        <button class="clear-selection" onclick="clearSelection()">Clear Selection</button>
        
        <div class="legend">
            <div class="legend-item">
                <div class="legend-color" style="background: linear-gradient(90deg, #ff0000, #ff6666);"></div>
                <span>High term sharing (red)</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: linear-gradient(90deg, #6666ff, #0000ff);"></div>
                <span>Low term sharing (blue)</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #e0e0e0;"></div>
                <span>No selection (gray)</span>
            </div>
        </div>
        
        <div class="stats" id="stats">
            No terms selected
        </div>
    </div>

    <div class="treemap-container">
        <svg width="{$svgWidth}" height="{$svgHeight}" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="redGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                    <stop offset="0%" style="stop-color:#ff0000;stop-opacity:1" />
                    <stop offset="100%" style="stop-color:#ff6666;stop-opacity:1" />
                </linearGradient>
                <linearGradient id="blueGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                    <stop offset="0%" style="stop-color:#6666ff;stop-opacity:1" />
                    <stop offset="100%" style="stop-color:#0000ff;stop-opacity:1" />
                </linearGradient>
            </defs>
            <rect x="0" y="0" width="{$svgWidth}" height="{$svgHeight}" fill="#f8f9fa"/>
            {$svg}
        </svg>
    </div>

    <script>
        const allTerms = {$termsJson};
        const entityData = {$entityDataJson};
        let selectedTerms = new Set();

        // Initialize term selector
        function initializeTermSelector() {
            const container = document.getElementById('termSelector');
            allTerms.forEach(term => {
                const checkbox = document.createElement('div');
                checkbox.className = 'term-checkbox';
                checkbox.innerHTML = 
                    '<input type="checkbox" id="term-' + term + '" value="' + term + '" onchange="toggleTerm(\'' + term + '\')">' +
                    '<label for="term-' + term + '">' + term + '</label>';
                container.appendChild(checkbox);
            });
        }

        // Toggle term selection
        function toggleTerm(term) {
            const checkbox = document.querySelector('#term-' + term);
            const container = checkbox.closest('.term-checkbox');
            
            if (checkbox.checked) {
                selectedTerms.add(term);
                container.classList.add('selected');
            } else {
                selectedTerms.delete(term);
                container.classList.remove('selected');
            }
            
            updateTreemap();
            updateStats();
        }

        // Clear all selections
        function clearSelection() {
            selectedTerms.clear();
            document.querySelectorAll('.term-checkbox input').forEach(checkbox => {
                checkbox.checked = false;
                checkbox.closest('.term-checkbox').classList.remove('selected');
            });
            updateTreemap();
            updateStats();
        }

        // Update treemap colors based on selected terms
        function updateTreemap() {
            const rects = document.querySelectorAll('.treemap-rect');
            
            rects.forEach(rect => {
                const entityName = rect.dataset.entity;
                const entityTerms = JSON.parse(rect.dataset.terms);
                const rectElement = rect.querySelector('rect');
                
                if (selectedTerms.size === 0) {
                    // No selection - gray
                    rectElement.setAttribute('fill', '#e0e0e0');
                } else {
                    // Calculate term sharing ratio
                    const sharedTerms = entityTerms.filter(term => selectedTerms.has(term));
                    const sharingRatio = sharedTerms.length / selectedTerms.size;
                    
                    if (sharingRatio > 0.5) {
                        // High sharing - red gradient
                        rectElement.setAttribute('fill', 'url(#redGradient)');
                    } else if (sharingRatio > 0) {
                        // Medium sharing - mix of red and blue
                        const redIntensity = Math.floor(sharingRatio * 255);
                        const blueIntensity = 255 - redIntensity;
                        rectElement.setAttribute('fill', 'rgb(' + redIntensity + ', 0, ' + blueIntensity + ')');
                    } else {
                        // Low sharing - blue gradient
                        rectElement.setAttribute('fill', 'url(#blueGradient)');
                    }
                }
            });
        }

        // Update statistics
        function updateStats() {
            const statsElement = document.getElementById('stats');
            
            if (selectedTerms.size === 0) {
                statsElement.textContent = 'No terms selected';
                return;
            }
            
            let highSharing = 0;
            let mediumSharing = 0;
            let lowSharing = 0;
            
            entityData.forEach(entity => {
                const sharedTerms = entity.terms.filter(term => selectedTerms.has(term));
                const sharingRatio = sharedTerms.length / selectedTerms.size;
                
                if (sharingRatio > 0.5) {
                    highSharing++;
                } else if (sharingRatio > 0) {
                    mediumSharing++;
                } else {
                    lowSharing++;
                }
            });
            
            statsElement.innerHTML = 
                '<strong>Selected Terms:</strong> ' + Array.from(selectedTerms).join(', ') + '<br>' +
                '<strong>High Sharing (red):</strong> ' + highSharing + ' entities | ' +
                '<strong>Medium Sharing:</strong> ' + mediumSharing + ' entities | ' +
                '<strong>Low Sharing (blue):</strong> ' + lowSharing + ' entities';
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            initializeTermSelector();
            updateStats();
        });
    </script>
</body>
</html>
HTML;
    }
}
