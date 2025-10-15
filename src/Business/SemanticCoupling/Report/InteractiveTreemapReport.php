<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\SemanticCoupling\Report;

use Phauthentic\CognitiveCodeAnalysis\Business\SemanticCoupling\SemanticCouplingCollection;

/**
 * Interactive hierarchical treemap report for semantic coupling analysis.
 * 
 * Allows users to select terms and visualize which areas of the program
 * share those terms using color gradients. Supports zooming into directories.
 */
class InteractiveTreemapReport extends AbstractReport
{
    private const SVG_WIDTH = 1400;
    private const SVG_HEIGHT = 900;

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
        
        // Build hierarchical tree structure from entity paths
        $tree = $this->buildHierarchicalTree($couplings);
        
        // Generate HTML with interactive controls
        return $this->wrapInHtml($allTerms, $granularity, $tree);
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
     * Build hierarchical tree structure from entity paths.
     */
    private function buildHierarchicalTree(SemanticCouplingCollection $couplings): array
    {
        $tree = [
            'name' => 'root',
            'path' => '',
            'type' => 'directory',
            'children' => [],
            'terms' => [],
            'score' => 0,
            'size' => 0
        ];
        
        $entityData = [];
        
        // Collect all entities and their data
        foreach ($couplings as $coupling) {
            $entity1 = $coupling->getEntity1();
            $entity2 = $coupling->getEntity2();
            
            if (!isset($entityData[$entity1])) {
                $entityData[$entity1] = [
                    'terms' => [],
                    'score' => 0
                ];
            }
            
            if (!isset($entityData[$entity2])) {
                $entityData[$entity2] = [
                    'terms' => [],
                    'score' => 0
                ];
            }
            
            $entityData[$entity1]['terms'] = array_unique(array_merge(
                $entityData[$entity1]['terms'],
                $coupling->getEntity1Terms()
            ));
            
            $entityData[$entity2]['terms'] = array_unique(array_merge(
                $entityData[$entity2]['terms'],
                $coupling->getEntity2Terms()
            ));
            
            $entityData[$entity1]['score'] = max($entityData[$entity1]['score'], $coupling->getScore());
            $entityData[$entity2]['score'] = max($entityData[$entity2]['score'], $coupling->getScore());
        }
        
        // Insert each entity into the tree
        foreach ($entityData as $path => $data) {
            $this->insertIntoTree($tree, $path, $data['terms'], $data['score']);
        }
        
        // Calculate sizes for directories
        $this->calculateDirectorySizes($tree);
        
        // Aggregate terms to root
        $this->aggregateTermsToRoot($tree);
        
        return $tree;
    }

    /**
     * Insert a file path into the tree structure.
     */
    private function insertIntoTree(array &$node, string $path, array $terms, float $score): void
    {
        $parts = explode('/', trim($path, '/'));
        $current = &$node;
        
        for ($i = 0; $i < count($parts); $i++) {
            $part = $parts[$i];
            $isLast = ($i === count($parts) - 1);
            
            // Find or create child node
            $found = false;
            foreach ($current['children'] as &$child) {
                if ($child['name'] === $part) {
                    $current = &$child;
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $newNode = [
                    'name' => $part,
                    'path' => implode('/', array_slice($parts, 0, $i + 1)),
                    'type' => $isLast ? 'file' : 'directory',
                    'children' => [],
                    'terms' => [],
                    'score' => $isLast ? $score : 0,
                    'size' => $isLast ? 1 : 0
                ];
                $current['children'][] = $newNode;
                $current = &$current['children'][count($current['children']) - 1];
            }
            
            // Always merge terms up the tree (for both files and directories)
            if (!empty($terms)) {
                $current['terms'] = array_unique(array_merge($current['terms'], $terms));
            }
            
            // Update score to max score of any child
            if (!$isLast && $score > $current['score']) {
                $current['score'] = $score;
            }
        }
    }

    /**
     * Calculate sizes for directories (sum of children).
     */
    private function calculateDirectorySizes(array &$node): int
    {
        if ($node['type'] === 'file') {
            return $node['size'];
        }
        
        $totalSize = 0;
        foreach ($node['children'] as &$child) {
            $totalSize += $this->calculateDirectorySizes($child);
        }
        
        $node['size'] = max(1, $totalSize);
        return $node['size'];
    }

    /**
     * Aggregate all terms up to root level.
     */
    private function aggregateTermsToRoot(array &$node): array
    {
        $allTerms = [];
        
        if ($node['type'] === 'file') {
            return $node['terms'];
        }
        
        foreach ($node['children'] as &$child) {
            $childTerms = $this->aggregateTermsToRoot($child);
            $allTerms = array_merge($allTerms, $childTerms);
        }
        
        $allTerms = array_unique($allTerms);
        $node['terms'] = array_merge($node['terms'], $allTerms);
        $node['terms'] = array_unique($node['terms']);
        
        return $node['terms'];
    }

    /**
     * Wrap in interactive HTML.
     */
    private function wrapInHtml(array $allTerms, string $granularity, array $tree): string
    {
        $termsJson = json_encode($allTerms, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $treeJson = json_encode($tree, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
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
        * { box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 15px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .breadcrumb-item {
            color: #007bff;
            cursor: pointer;
            text-decoration: none;
            padding: 4px 8px;
            border-radius: 3px;
        }
        .breadcrumb-item:hover {
            background: #e9ecef;
        }
        .breadcrumb-separator {
            color: #6c757d;
        }
        .term-selector { 
            display: flex; 
            flex-wrap: wrap; 
            gap: 10px; 
            margin-bottom: 15px;
            max-height: 200px;
            overflow-y: auto;
        }
        .term-checkbox { 
            display: flex; 
            align-items: center; 
            background: #f0f0f0; 
            padding: 6px 12px; 
            border-radius: 20px; 
            cursor: pointer; 
            transition: all 0.2s;
            font-size: 13px;
        }
        .term-checkbox:hover { 
            background: #e0e0e0; 
        }
        .term-checkbox.selected { 
            background: #007bff; 
            color: white; 
        }
        .term-checkbox input { 
            margin-right: 6px; 
        }
        .legend { 
            display: flex; 
            align-items: center; 
            gap: 20px; 
            margin-top: 15px;
            flex-wrap: wrap;
        }
        .legend-item { 
            display: flex; 
            align-items: center; 
            gap: 8px;
            font-size: 13px;
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
            overflow: hidden;
        }
        .treemap-rect {
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .treemap-rect:hover {
            opacity: 0.8;
        }
        .treemap-rect.directory {
            stroke: #333;
            stroke-width: 2;
        }
        .treemap-rect.file {
            stroke: #666;
            stroke-width: 1;
        }
        .treemap-text {
            pointer-events: none;
            font-family: Arial, sans-serif;
            font-size: 12px;
            font-weight: normal;
            fill: #333;
        }
        .stats { 
            margin-top: 15px; 
            font-size: 14px; 
            color: #666;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .clear-selection { 
            background: #dc3545; 
            color: white; 
            border: none; 
            padding: 8px 16px; 
            border-radius: 4px; 
            cursor: pointer; 
            margin-left: 10px;
            font-size: 14px;
        }
        .clear-selection:hover { 
            background: #c82333; 
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Semantic Coupling Interactive Treemap</h1>
        <p><strong>Granularity:</strong> {$granularity} | <strong>Total Terms:</strong> {$totalTerms}</p>
        <div id="levelInfo" style="margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 4px; font-size: 14px;">
            <strong>Current Level:</strong> <span id="currentPath">Root</span> | 
            <strong>Items:</strong> <span id="itemCount">0</span> | 
            <strong>Terms in this level:</strong> <span id="termCount">0</span>
        </div>
        <p style="color: #666; font-size: 14px; margin-top: 10px;">
            📁 <strong>Folders</strong> have thick borders (click to zoom in) | 
            📄 <strong>Files</strong> have thin borders | 
            Use breadcrumb navigation to go back
        </p>
    </div>

    <div class="controls">
        <h3>Select Terms to Highlight</h3>
        <div class="term-selector" id="termSelector">
            <!-- Terms will be populated by JavaScript -->
        </div>
        <button class="clear-selection" onclick="clearSelection()">Clear Selection</button>
        
        <div class="legend">
            <div class="legend-item">
                <div class="legend-color" style="background: #ff4444;"></div>
                <span>High term sharing (red)</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #ffaa44;"></div>
                <span>Medium term sharing (orange)</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #4444ff;"></div>
                <span>Low/No term sharing (blue)</span>
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

    <div class="breadcrumb" id="breadcrumb">
        <span class="breadcrumb-item" onclick="navigateToRoot()">🏠 Root</span>
    </div>

    <div class="treemap-container">
        <svg width="{$svgWidth}" height="{$svgHeight}" xmlns="http://www.w3.org/2000/svg" id="treemap">
        </svg>
    </div>

    <script>
        const allTerms = {$termsJson};
        const rootTree = {$treeJson};
        let selectedTerms = new Set();
        let currentNode = rootTree;
        let breadcrumbPath = [];

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
            
            renderTreemap();
            updateStats();
        }

        // Clear all selections
        function clearSelection() {
            selectedTerms.clear();
            document.querySelectorAll('.term-checkbox input').forEach(checkbox => {
                checkbox.checked = false;
                checkbox.closest('.term-checkbox').classList.remove('selected');
            });
            renderTreemap();
            updateStats();
        }

        // Calculate color based on term sharing
        function calculateColor(terms) {
            if (selectedTerms.size === 0) {
                return '#f5f5f5'; // Light gray when no selection
            }
            
            const sharedTerms = terms.filter(term => selectedTerms.has(term));
            const sharingRatio = sharedTerms.length / selectedTerms.size;
            
            if (sharingRatio > 0.5) {
                // High sharing - very light red
                return '#ffe6e6';
            } else if (sharingRatio > 0.2) {
                // Medium sharing - very light orange
                return '#fff2e6';
            } else if (sharingRatio > 0) {
                // Low sharing - very light blue
                return '#e6f2ff';
            } else {
                // No sharing - very light blue
                return '#f0f8ff';
            }
        }

        // Proper squarify treemap algorithm
        function squarify(children, x, y, width, height) {
            if (children.length === 0) return [];
            if (children.length === 1) {
                return [{
                    ...children[0],
                    x: x,
                    y: y,
                    width: width,
                    height: height
                }];
            }
            
            const totalSize = children.reduce((sum, child) => sum + child.size, 0);
            if (totalSize === 0) return [];
            
            // Sort by size descending for better aspect ratios
            const sorted = [...children].sort((a, b) => b.size - a.size);
            
            return squarifyHelper(sorted, [], x, y, width, height, totalSize);
        }
        
        function squarifyHelper(children, row, x, y, width, height, total) {
            if (children.length === 0) {
                return layoutRow(row, x, y, width, height, total);
            }
            
            const child = children[0];
            const newRow = [...row, child];
            
            if (row.length === 0 || improveAspectRatio(row, child, width, height, total)) {
                // Add to current row
                return squarifyHelper(children.slice(1), newRow, x, y, width, height, total);
            } else {
                // Layout current row and start new one
                const rowRects = layoutRow(row, x, y, width, height, total);
                const rowSize = row.reduce((sum, r) => sum + r.size, 0);
                
                if (width >= height) {
                    // Rows are vertical, next row shifts right
                    const rowWidth = (rowSize / total) * width;
                    return rowRects.concat(
                        squarifyHelper(children, [], x + rowWidth, y, width - rowWidth, height, total - rowSize)
                    );
                } else {
                    // Rows are horizontal, next row shifts down
                    const rowHeight = (rowSize / total) * height;
                    return rowRects.concat(
                        squarifyHelper(children, [], x, y + rowHeight, width, height - rowHeight, total - rowSize)
                    );
                }
            }
        }
        
        function improveAspectRatio(row, child, width, height, total) {
            if (row.length === 0) return true;
            
            const rowSize = row.reduce((sum, r) => sum + r.size, 0);
            const newRowSize = rowSize + child.size;
            
            const currentWorst = worstAspectRatio(row, rowSize, width, height, total);
            const newWorst = worstAspectRatio([...row, child], newRowSize, width, height, total);
            
            return newWorst <= currentWorst;
        }
        
        function worstAspectRatio(row, rowSize, width, height, total) {
            if (rowSize === 0) return Infinity;
            
            const isVertical = width >= height;
            const length = isVertical ? height : width;
            const thickness = (rowSize / total) * (isVertical ? width : height);
            
            if (thickness === 0) return Infinity;
            
            let worst = 0;
            row.forEach(child => {
                const size = (child.size / rowSize) * length;
                const ratio = Math.max(thickness / size, size / thickness);
                worst = Math.max(worst, ratio);
            });
            
            return worst;
        }
        
        function layoutRow(row, x, y, width, height, total) {
            const rowSize = row.reduce((sum, r) => sum + r.size, 0);
            if (rowSize === 0) return [];
            
            const isVertical = width >= height;
            const rects = [];
            
            if (isVertical) {
                // Vertical row (stacked vertically, extends horizontally)
                const rowWidth = (rowSize / total) * width;
                let currentY = y;
                
                row.forEach(child => {
                    const rectHeight = (child.size / rowSize) * height;
                    rects.push({
                        ...child,
                        x: x,
                        y: currentY,
                        width: Math.max(1, rowWidth),
                        height: Math.max(1, rectHeight)
                    });
                    currentY += rectHeight;
                });
            } else {
                // Horizontal row (stacked horizontally, extends vertically)
                const rowHeight = (rowSize / total) * height;
                let currentX = x;
                
                row.forEach(child => {
                    const rectWidth = (child.size / rowSize) * width;
                    rects.push({
                        ...child,
                        x: currentX,
                        y: y,
                        width: Math.max(1, rectWidth),
                        height: Math.max(1, rowHeight)
                    });
                    currentX += rectWidth;
                });
            }
            
            return rects;
        }

        // Update level information
        function updateLevelInfo() {
            const pathElement = document.getElementById('currentPath');
            const itemCountElement = document.getElementById('itemCount');
            const termCountElement = document.getElementById('termCount');
            
            pathElement.textContent = currentNode.path || 'Root';
            itemCountElement.textContent = currentNode.children.length;
            termCountElement.textContent = currentNode.terms.length;
        }

        // Render treemap
        function renderTreemap() {
            const svg = document.getElementById('treemap');
            svg.innerHTML = '';
            
            updateLevelInfo();
            
            const padding = 2;
            const rects = squarify(currentNode.children, 0, 0, {$svgWidth}, {$svgHeight});
            
            rects.forEach(rect => {
                if (rect.width < 1 || rect.height < 1) return;
                
                const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
                g.setAttribute('class', 'treemap-rect ' + rect.type);
                
                // Apply padding
                const paddedX = rect.x + padding;
                const paddedY = rect.y + padding;
                const paddedWidth = Math.max(1, rect.width - padding * 2);
                const paddedHeight = Math.max(1, rect.height - padding * 2);
                
                const rectEl = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                rectEl.setAttribute('x', paddedX);
                rectEl.setAttribute('y', paddedY);
                rectEl.setAttribute('width', paddedWidth);
                rectEl.setAttribute('height', paddedHeight);
                rectEl.setAttribute('fill', calculateColor(rect.terms));
                rectEl.setAttribute('rx', '3');  // Rounded corners
                
                // Different styling for directories vs files
                if (rect.type === 'directory') {
                    rectEl.setAttribute('stroke', '#333');
                    rectEl.setAttribute('stroke-width', '3');
                    rectEl.style.cursor = 'pointer';
                    g.onclick = () => navigateToNode(rect);
                } else {
                    rectEl.setAttribute('stroke', '#666');
                    rectEl.setAttribute('stroke-width', '1');
                }
                
                g.appendChild(rectEl);
                
                // Add title
                const title = document.createElementNS('http://www.w3.org/2000/svg', 'title');
                title.textContent = rect.name + (rect.type === 'directory' ? ' 📁 (click to open)' : ' 📄') + 
                    '\\nTerms: ' + rect.terms.length + 
                    '\\nSize: ' + rect.size +
                    (rect.type === 'directory' ? '\\nChildren: ' + rect.children.length : '');
                g.appendChild(title);
                
                // Add text label if there's enough space
                if (paddedWidth > 50 && paddedHeight > 25) {
                    const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                    text.setAttribute('class', 'treemap-text');
                    text.setAttribute('x', paddedX + 5);
                    text.setAttribute('y', paddedY + 18);
                    text.setAttribute('font-weight', rect.type === 'directory' ? 'normal' : 'normal');
                    
                    // Add folder icon for directories
                    const displayName = (rect.type === 'directory' ? '📁 ' : '') + 
                        (rect.name.length > 25 ? rect.name.substring(0, 25) + '...' : rect.name);
                    text.textContent = displayName;
                    g.appendChild(text);
                    
                    // Add size info on second line if there's space
                    if (paddedHeight > 40) {
                        const sizeText = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                        sizeText.setAttribute('class', 'treemap-text');
                        sizeText.setAttribute('x', paddedX + 5);
                        sizeText.setAttribute('y', paddedY + 33);
                        sizeText.setAttribute('font-size', '10');
                        sizeText.setAttribute('fill', '#666');
                        sizeText.textContent = rect.type === 'directory' ? 
                            rect.children.length + ' items' : 
                            rect.terms.length + ' terms';
                        g.appendChild(sizeText);
                    }
                }
                
                svg.appendChild(g);
            });
        }

        // Navigate to a node
        function navigateToNode(node) {
            if (node.type !== 'directory') return;
            
            currentNode = node;
            breadcrumbPath.push(node);
            updateBreadcrumb();
            renderTreemap();
        }

        // Navigate to root
        function navigateToRoot() {
            currentNode = rootTree;
            breadcrumbPath = [];
            updateBreadcrumb();
            renderTreemap();
        }

        // Navigate to specific breadcrumb level
        function navigateToBreadcrumb(index) {
            if (index === -1) {
                navigateToRoot();
                return;
            }
            
            currentNode = breadcrumbPath[index];
            breadcrumbPath = breadcrumbPath.slice(0, index + 1);
            updateBreadcrumb();
            renderTreemap();
        }

        // Update breadcrumb
        function updateBreadcrumb() {
            const breadcrumb = document.getElementById('breadcrumb');
            breadcrumb.innerHTML = '<span class="breadcrumb-item" onclick="navigateToRoot()">🏠 Root</span>';
            
            breadcrumbPath.forEach((node, index) => {
                breadcrumb.innerHTML += '<span class="breadcrumb-separator">/</span>';
                breadcrumb.innerHTML += '<span class="breadcrumb-item" onclick="navigateToBreadcrumb(' + index + ')">' + 
                    node.name + '</span>';
            });
        }

        // Update statistics
        function updateStats() {
            const statsElement = document.getElementById('stats');
            
            if (selectedTerms.size === 0) {
                statsElement.innerHTML = 'No terms selected. Select terms above to highlight areas with shared terminology.<br>' +
                    '<strong>Current level has ' + currentNode.children.length + ' items with ' + 
                    currentNode.terms.length + ' unique terms.</strong>';
                return;
            }
            
            let highSharing = 0;
            let mediumSharing = 0;
            let lowSharing = 0;
            
            // Count only items at current level
            currentNode.children.forEach(child => {
                const sharedTerms = child.terms.filter(term => selectedTerms.has(term));
                const sharingRatio = sharedTerms.length / selectedTerms.size;
                
                if (sharingRatio > 0.5) {
                    highSharing++;
                } else if (sharingRatio > 0.2) {
                    mediumSharing++;
                } else if (sharingRatio > 0) {
                    lowSharing++;
                }
            });
            
            statsElement.innerHTML = 
                '<strong>Selected Terms:</strong> ' + Array.from(selectedTerms).join(', ') + '<br>' +
                '<strong>At current level:</strong> ' +
                '<strong>High Sharing (red):</strong> ' + highSharing + ' items | ' +
                '<strong>Medium Sharing (orange):</strong> ' + mediumSharing + ' items | ' +
                '<strong>Low Sharing (blue):</strong> ' + lowSharing + ' items';
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            initializeTermSelector();
            renderTreemap();
            updateStats();
        });
    </script>
</body>
</html>
HTML;
    }
}
