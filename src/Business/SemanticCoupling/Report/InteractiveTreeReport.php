<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\SemanticCoupling\Report;

use Phauthentic\CognitiveCodeAnalysis\Business\SemanticCoupling\SemanticCouplingCollection;

class InteractiveTreeReport extends AbstractReport
{
    public function export(SemanticCouplingCollection $collection, string $filename): void
    {
        $allTerms = $this->extractAllTerms($collection);
        $termTrees = $this->buildTermTrees($collection, $allTerms);
        
        $html = $this->wrapInHtml($termTrees, $allTerms);
        file_put_contents($filename, $html);
    }

    private function extractAllTerms(SemanticCouplingCollection $collection): array
    {
        $terms = [];
        foreach ($collection as $coupling) {
            $terms = array_merge($terms, $coupling->getSharedTerms());
        }
        return array_values(array_unique($terms));
    }

    private function buildTermTrees(SemanticCouplingCollection $collection, array $allTerms): array
    {
        $termTrees = [];
        
        foreach ($allTerms as $term) {
            $termTrees[$term] = $this->buildTreeForTerm($collection, $term);
        }
        
        return $termTrees;
    }

    private function buildTreeForTerm(SemanticCouplingCollection $collection, string $term): array
    {
        $filesWithTerm = [];
        
        // Find all files that contain this term
        foreach ($collection as $coupling) {
            if (in_array($term, $coupling->getSharedTerms())) {
                $entity1 = $coupling->getEntity1();
                $entity2 = $coupling->getEntity2();
                
                if (!isset($filesWithTerm[$entity1])) {
                    $filesWithTerm[$entity1] = [
                        'path' => $entity1,
                        'name' => basename($entity1),
                        'namespace' => $this->extractNamespace($entity1),
                        'couplingScore' => 0,
                        'children' => []
                    ];
                }
                
                if (!isset($filesWithTerm[$entity2])) {
                    $filesWithTerm[$entity2] = [
                        'path' => $entity2,
                        'name' => basename($entity2),
                        'namespace' => $this->extractNamespace($entity2),
                        'couplingScore' => 0,
                        'children' => []
                    ];
                }
                
                // Update coupling scores
                $filesWithTerm[$entity1]['couplingScore'] = max($filesWithTerm[$entity1]['couplingScore'], $coupling->getScore());
                $filesWithTerm[$entity2]['couplingScore'] = max($filesWithTerm[$entity2]['couplingScore'], $coupling->getScore());
            }
        }
        
        // Build hierarchical tree structure
        $root = [
            'name' => $term,
            'path' => '',
            'namespace' => '',
            'couplingScore' => 0,
            'children' => []
        ];
        
        foreach ($filesWithTerm as $file) {
            $this->insertFileIntoTree($root, $file);
        }
        
        return $root;
    }

    private function extractNamespace(string $filePath): string
    {
        $path = str_replace('\\', '/', $filePath);
        $parts = explode('/', $path);
        array_pop($parts); // Remove filename
        return implode('/', $parts);
    }

    private function insertFileIntoTree(array &$root, array $file): void
    {
        $namespaceParts = explode('/', $file['namespace']);
        $current = &$root;
        
        // Navigate/create namespace path
        foreach ($namespaceParts as $part) {
            if (empty($part)) continue;
            
            $found = false;
            foreach ($current['children'] as &$child) {
                if ($child['name'] === $part && $child['path'] === '') {
                    $current = &$child;
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $current['children'][] = [
                    'name' => $part,
                    'path' => '',
                    'namespace' => implode('/', array_slice($namespaceParts, 0, array_search($part, $namespaceParts) + 1)),
                    'couplingScore' => 0,
                    'children' => []
                ];
                $current = &$current['children'][count($current['children']) - 1];
            }
        }
        
        // Add the file
        $current['children'][] = $file;
    }

    private function wrapInHtml(array $termTrees, array $allTerms): string
    {
        $termTreesJson = json_encode($termTrees, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $allTermsJson = json_encode($allTerms, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Semantic Coupling - Interactive Tree</title>
    <script src="https://d3js.org/d3.v7.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f8f9fa;
        }
        
        .header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .controls {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        
        .term-selector {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .term-checkbox {
            margin-right: 5px;
        }
        
        .term-label {
            font-size: 14px;
            color: #333;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 4px;
            background: #e9ecef;
            transition: background-color 0.2s;
        }
        
        .term-label:hover {
            background: #dee2e6;
        }
        
        .term-label.selected {
            background: #007bff;
            color: white;
        }
        
        .clear-btn {
            background: #6c757d;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .clear-btn:hover {
            background: #5a6268;
        }
        
        .stats {
            margin-top: 15px;
            font-size: 14px;
            color: #666;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        
        .tree-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            height: 80vh;
            overflow: auto;
            position: relative;
        }
        
        .node {
            cursor: pointer;
        }
        
        .node circle {
            fill: #fff;
            stroke: #007bff;
            stroke-width: 2px;
        }
        
        .node circle.file {
            fill: #28a745;
            stroke: #1e7e34;
        }
        
        .node circle.namespace {
            fill: #ffc107;
            stroke: #e0a800;
        }
        
        .node circle.term {
            fill: #dc3545;
            stroke: #c82333;
            stroke-width: 3px;
        }
        
        .node text {
            font: 12px Arial, sans-serif;
            fill: #333;
        }
        
        .link {
            fill: none;
            stroke: #ccc;
            stroke-width: 1.5px;
        }
        
        .tooltip {
            position: absolute;
            padding: 10px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            border-radius: 4px;
            font-size: 12px;
            pointer-events: none;
            z-index: 1000;
        }
        
        .no-data {
            text-align: center;
            color: #666;
            font-style: italic;
            margin-top: 50px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Semantic Coupling - Interactive Tree</h1>
        <p>Select terms to visualize their usage across the codebase. Each tree shows files and namespaces that contain the selected term.</p>
        
        <div class="controls">
            <div class="term-selector">
                <label>Select Terms:</label>
                <div id="termCheckboxes"></div>
            </div>
            <button class="clear-btn" onclick="clearSelection()">Clear Selection</button>
        </div>
        
        <div class="stats" id="stats">
            <div>Selected Terms: <span id="selectedCount">0</span></div>
            <div>Total Files: <span id="totalFiles">0</span></div>
            <div>Total Namespaces: <span id="totalNamespaces">0</span></div>
        </div>
    </div>
    
    <div class="tree-container">
        <svg id="treeSvg" width="100%" height="600"></svg>
        <div id="noData" class="no-data" style="display: none;">
            No data available. Please select terms to visualize.
        </div>
    </div>

    <script>
        const termTrees = {$termTreesJson};
        const allTerms = {$allTermsJson};
        let selectedTerms = new Set();
        let currentTrees = [];
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            initializeTermSelector();
            updateDisplay();
            
            // Handle window resize
            window.addEventListener('resize', function() {
                if (selectedTerms.size > 0) {
                    updateDisplay();
                }
            });
        });
        
        function initializeTermSelector() {
            const container = document.getElementById('termCheckboxes');
            container.innerHTML = '';
            
            allTerms.forEach(term => {
                const label = document.createElement('label');
                label.className = 'term-label';
                label.innerHTML = '<input type="checkbox" class="term-checkbox" value="' + term + '" onchange="toggleTerm(\'' + term + '\')"> ' + term;
                container.appendChild(label);
            });
        }
        
        function toggleTerm(term) {
            if (selectedTerms.has(term)) {
                selectedTerms.delete(term);
            } else {
                selectedTerms.add(term);
            }
            updateDisplay();
        }
        
        function clearSelection() {
            selectedTerms.clear();
            document.querySelectorAll('.term-checkbox').forEach(cb => cb.checked = false);
            updateDisplay();
        }
        
        function updateDisplay() {
            updateStats();
            
            if (selectedTerms.size === 0) {
                document.getElementById('treeSvg').style.display = 'none';
                document.getElementById('noData').style.display = 'block';
                return;
            }
            
            document.getElementById('treeSvg').style.display = 'block';
            document.getElementById('noData').style.display = 'none';
            
            // Get selected trees
            currentTrees = Array.from(selectedTerms).map(term => termTrees[term]);
            
            // Render trees
            renderTrees();
        }
        
        function updateStats() {
            document.getElementById('selectedCount').textContent = selectedTerms.size;
            
            if (selectedTerms.size === 0) {
                document.getElementById('totalFiles').textContent = '0';
                document.getElementById('totalNamespaces').textContent = '0';
                return;
            }
            
            const allFiles = new Set();
            const allNamespaces = new Set();
            
            currentTrees.forEach(tree => {
                collectStats(tree, allFiles, allNamespaces);
            });
            
            document.getElementById('totalFiles').textContent = allFiles.size;
            document.getElementById('totalNamespaces').textContent = allNamespaces.size;
        }
        
        function collectStats(node, files, namespaces) {
            if (node.path && node.path !== '') {
                files.add(node.path);
            } else if (node.namespace && node.namespace !== '') {
                namespaces.add(node.namespace);
            }
            
            if (node.children) {
                node.children.forEach(child => collectStats(child, files, namespaces));
            }
        }
        
        function renderTrees() {
            const svg = d3.select('#treeSvg');
            svg.selectAll('*').remove();
            
            const containerWidth = document.querySelector('.tree-container').getBoundingClientRect().width - 40;
            const containerHeight = document.querySelector('.tree-container').getBoundingClientRect().height - 40;
            
            // Calculate dynamic dimensions based on content
            const treeCount = selectedTerms.size;
            let treeWidth, treeHeight, treesPerRow, treesPerCol;
            
            if (treeCount === 1) {
                treeWidth = containerWidth;
                treeHeight = Math.max(containerHeight, 600); // Horizontal trees need less height
                treesPerRow = 1;
                treesPerCol = 1;
            } else if (treeCount <= 4) {
                treesPerRow = 2;
                treesPerCol = Math.ceil(treeCount / 2);
                treeWidth = containerWidth / treesPerRow;
                treeHeight = Math.max(containerHeight / treesPerCol, 300); // Horizontal trees need less height
            } else {
                treesPerRow = Math.ceil(Math.sqrt(treeCount));
                treesPerCol = Math.ceil(treeCount / treesPerRow);
                treeWidth = containerWidth / treesPerRow;
                treeHeight = Math.max(containerHeight / treesPerCol, 250); // Horizontal trees need less height
            }
            
            // Set SVG size to accommodate all trees
            const totalWidth = treeWidth * treesPerRow;
            const totalHeight = treeHeight * treesPerCol;
            
            svg.attr('width', totalWidth).attr('height', totalHeight);
            
            const g = svg.append('g')
                .attr('transform', 'translate(20,20)');
            
            // Create tooltip
            const tooltip = d3.select('body').append('div')
                .attr('class', 'tooltip')
                .style('opacity', 0);
            
            // Render each selected tree
            let treeIndex = 0;
            selectedTerms.forEach(term => {
                const tree = termTrees[term];
                const x = (treeIndex % treesPerRow) * treeWidth;
                const y = Math.floor(treeIndex / treesPerRow) * treeHeight;
                
                const treeG = g.append('g')
                    .attr('transform', 'translate(' + x + ',' + y + ')');
                
                renderTree(tree, treeG, treeWidth - 40, treeHeight - 40, tooltip);
                treeIndex++;
            });
        }
        
        function renderTree(root, container, width, height, tooltip) {
            // Use horizontal tree layout (left to right)
            const tree = d3.tree()
                .size([height - 100, width - 200]) // Swap width/height for horizontal
                .separation((a, b) => {
                    // Larger separation for horizontal layout
                    const baseSeparation = 1.2;
                    const depthFactor = Math.max(1, a.depth);
                    return baseSeparation * depthFactor;
                });
            
            const rootNode = d3.hierarchy(root);
            tree(rootNode);
            
            // Calculate bounds for horizontal layout
            const bounds = rootNode.descendants().reduce((bounds, d) => {
                return {
                    x0: Math.min(bounds.x0, d.y), // Note: y becomes x in horizontal layout
                    x1: Math.max(bounds.x1, d.y),
                    y0: Math.min(bounds.y0, d.x), // Note: x becomes y in horizontal layout
                    y1: Math.max(bounds.y1, d.x)
                };
            }, {x0: Infinity, x1: -Infinity, y0: Infinity, y1: -Infinity});
            
            // Add padding for text labels
            const padding = 80;
            const dx = bounds.x1 - bounds.x0 + padding * 2;
            const dy = bounds.y1 - bounds.y0 + padding * 2;
            const x = (width - dx) / 2 - bounds.x0 + padding;
            const y = (height - dy) / 2 - bounds.y0 + padding;
            
            const links = container.selectAll('.link')
                .data(rootNode.links())
                .enter().append('path')
                .attr('class', 'link')
                .attr('d', d3.linkHorizontal() // Use horizontal link
                    .x(d => d.y + x) // Swap x/y coordinates
                    .y(d => d.x + y));
            
            const node = container.selectAll('.node')
                .data(rootNode.descendants())
                .enter().append('g')
                .attr('class', 'node')
                .attr('transform', d => 'translate(' + (d.y + x) + ',' + (d.x + y) + ')') // Swap coordinates
                .on('mouseover', function(event, d) {
                    tooltip.transition().duration(200).style('opacity', .9);
                    tooltip.html(getTooltipContent(d))
                        .style('left', (event.pageX + 10) + 'px')
                        .style('top', (event.pageY - 28) + 'px');
                })
                .on('mouseout', function() {
                    tooltip.transition().duration(500).style('opacity', 0);
                });
            
            node.append('circle')
                .attr('r', d => getNodeRadius(d))
                .attr('class', d => getNodeClass(d));
            
            // Text positioning for horizontal layout
            node.each(function(d) {
                const nodeGroup = d3.select(this);
                const textElement = nodeGroup.append('text')
                    .attr('dy', '.35em')
                    .style('font-size', getNodeFontSize(d))
                    .style('fill', '#333')
                    .text(d => {
                        const maxLength = d.children ? 25 : 18; // More space for horizontal layout
                        return d.data.name.length > maxLength ? 
                            d.data.name.substring(0, maxLength) + '...' : 
                            d.data.name;
                    });
                
                // Position text for horizontal layout
                if (d.children) {
                    // Parent nodes - position to the right
                    textElement
                        .attr('x', 20)
                        .attr('y', 0)
                        .style('text-anchor', 'start');
                } else {
                    // Leaf nodes - position below
                    textElement
                        .attr('x', 0)
                        .attr('y', 20)
                        .style('text-anchor', 'middle');
                }
            });
        }
        
        function getNodeRadius(d) {
            if (d.depth === 0) return 8; // Root term
            if (d.data.path && d.data.path !== '') return 6; // File
            return 5; // Namespace
        }
        
        function getNodeClass(d) {
            if (d.depth === 0) return 'term';
            if (d.data.path && d.data.path !== '') return 'file';
            return 'namespace';
        }
        
        function getNodeFontSize(d) {
            if (d.depth === 0) return '14px';
            if (d.data.path && d.data.path !== '') return '11px';
            return '12px';
        }
        
        function getTooltipContent(d) {
            let content = '<strong>' + d.data.name + '</strong><br/>';
            
            if (d.data.path && d.data.path !== '') {
                content += 'File: ' + d.data.path + '<br/>';
                content += 'Coupling Score: ' + d.data.couplingScore.toFixed(3);
            } else if (d.data.namespace && d.data.namespace !== '') {
                content += 'Namespace: ' + d.data.namespace;
            } else {
                content += 'Term: ' + d.data.name;
            }
            
            return content;
        }
    </script>
</body>
</html>
HTML;
    }
}
