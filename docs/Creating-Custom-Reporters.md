# Creating Custom Reporters

This guide explains how to create custom reporters for the Cognitive Code Checker to output metrics in your preferred format.

## Overview

The Cognitive Code Checker supports two types of reporters:
- **Cognitive Reporters**: Export cognitive complexity metrics
- **Churn Reporters**: Export code churn metrics

Both types follow similar patterns but have different interfaces and data structures.

## Reporter Types

### Cognitive Reporters

Cognitive reporters handle cognitive complexity metrics data and implement the `ReportGeneratorInterface` from the `Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Report` namespace.

**Interface:**
```php
interface ReportGeneratorInterface
{
    public function export(CognitiveMetricsCollection $metrics, string $filename): void;
}
```

**Data Structure:** `CognitiveMetricsCollection` contains individual `CognitiveMetrics` objects with methods like:
- `getClass()` - Class name
- `getMethod()` - Method name
- `getLineCount()` - Number of lines
- `getScore()` - Combined cognitive complexity score
- `getLineCountWeight()`, `getArgCountWeight()`, etc. - Individual metric weights
- `getLineCountWeightDelta()`, etc. - Delta values for comparison

### Churn Reporters

Churn reporters handle code churn metrics data and implement the `ReportGeneratorInterface` from the `Phauthentic\CognitiveCodeAnalysis\Business\Churn\Report` namespace.

**Interface:**
```php
interface ReportGeneratorInterface
{
    /**
     * @param array<string, array<string, mixed>> $classes
     */
    public function export(array $classes, string $filename): void;
}
```

**Data Structure:** Array with class names as keys and arrays containing:
- `file` - File path
- `score` - Churn score
- `churn` - Churn value
- `timesChanged` - Number of times changed
- `coverage` - Test coverage (optional)
- `riskLevel` - Risk level (optional)

## Configuration

Add your custom reporters to the `config.yml` file under the `customExporters` section:

```yaml
cognitive:
  # ... other cognitive settings ...
  customExporters:
    cognitive:
      pdf:  # Custom reporter name
        class: 'My\Custom\PdfReporter'
        file: '/path/to/PdfReporter.php'
        requiresConfig: true
    churn:
      xml:  # Custom reporter name
        class: 'My\Custom\XmlChurnReporter'
        file: null  # null if class is autoloaded
```

### Configuration Parameters

- **`class`** (required): Fully qualified class name of your reporter
- **`file`** (optional): Path to the PHP file containing your reporter class. Set to `null` if using autoloading
- **`requiresConfig`** (cognitive only): Whether your reporter needs the `CognitiveConfig` object in its constructor

## Creating a Custom Cognitive Reporter

Here's a complete example of a custom PDF reporter for cognitive metrics:

```php
<?php

declare(strict_types=1);

namespace My\Custom;

use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Report\ReportGeneratorInterface;
use Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\CognitiveMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\Config\CognitiveConfig;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;

class PdfReporter implements ReportGeneratorInterface
{
    private CognitiveConfig $config;

    public function __construct(CognitiveConfig $config)
    {
        $this->config = $config;
    }

    public function export(CognitiveMetricsCollection $metrics, string $filename): void
    {
        // Ensure directory exists
        $directory = dirname($filename);
        if (!is_dir($directory)) {
            throw new CognitiveAnalysisException("Directory {$directory} does not exist");
        }

        // Create PDF content
        $pdfContent = $this->generatePdfContent($metrics);

        // Write to file
        if (file_put_contents($filename, $pdfContent) === false) {
            throw new CognitiveAnalysisException("Could not write to file: {$filename}");
        }
    }

    private function generatePdfContent(CognitiveMetricsCollection $metrics): string
    {
        $content = "%PDF-1.4\n";
        $content .= "1 0 obj\n";
        $content .= "<< /Type /Catalog /Pages 2 0 R >>\n";
        $content .= "endobj\n";
        
        // Add your PDF generation logic here
        $groupedByClass = $metrics->groupBy('class');
        
        foreach ($groupedByClass as $class => $methods) {
            $content .= "% Class: {$class}\n";
            foreach ($methods as $metric) {
                $content .= "Method: {$metric->getMethod()}, Score: {$metric->getScore()}\n";
            }
        }
        
        return $content;
    }
}
```

## Creating a Custom Churn Reporter

Here's an example of a custom XML reporter for churn metrics:

```php
<?php

declare(strict_types=1);

namespace My\Custom;

use Phauthentic\CognitiveCodeAnalysis\Business\Churn\Report\ReportGeneratorInterface;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;

class XmlChurnReporter implements ReportGeneratorInterface
{
    public function export(array $classes, string $filename): void
    {
        // Ensure directory exists
        $directory = dirname($filename);
        if (!is_dir($directory)) {
            throw new CognitiveAnalysisException("Directory {$directory} does not exist");
        }

        // Generate XML content
        $xmlContent = $this->generateXmlContent($classes);

        // Write to file
        if (file_put_contents($filename, $xmlContent) === false) {
            throw new CognitiveAnalysisException("Could not write to file: {$filename}");
        }
    }

    private function generateXmlContent(array $classes): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<churn-report>' . "\n";
        
        foreach ($classes as $className => $data) {
            $xml .= "  <class name=\"{$className}\">\n";
            $xml .= "    <file>{$data['file']}</file>\n";
            $xml .= "    <score>{$data['score']}</score>\n";
            $xml .= "    <churn>{$data['churn']}</churn>\n";
            $xml .= "    <times-changed>{$data['timesChanged']}</times-changed>\n";
            $xml .= "  </class>\n";
        }
        
        $xml .= '</churn-report>';
        
        return $xml;
    }
}
```

## Using Your Custom Reporter

Once configured, you can use your custom reporter by specifying its name when generating reports:

```bash
# For cognitive metrics
php bin/cognitive-report --format=pdf --output=report.pdf

# For churn metrics  
php bin/churn-report --format=xml --output=churn.xml
```

## Best Practices

1. **Error Handling**: Always throw `CognitiveAnalysisException` for errors
2. **File Validation**: Check that directories exist and files are writable
3. **Data Access**: Use the provided methods to access metric data
4. **Configuration**: Use `CognitiveConfig` if you need access to settings
5. **Testing**: Test your reporter with real data to ensure proper formatting

## Built-in Reporters Reference

For inspiration, examine the built-in reporters:

**Cognitive Reporters:**
- `JsonReport` - JSON format
- `CsvReport` - CSV format  
- `HtmlReport` - HTML with Bootstrap styling
- `MarkdownReport` - Markdown tables

**Churn Reporters:**
- `JsonReport` - JSON format
- `CsvReport` - CSV format
- `HtmlReport` - HTML with Bootstrap styling
- `MarkdownReport` - Markdown tables
- `SvgTreemapReport` - SVG treemap visualization

## Troubleshooting

**Common Issues:**

1. **Class not found**: Ensure the `class` parameter uses the full namespace
2. **File not found**: Check the `file` path is correct and accessible
3. **Interface not implemented**: Ensure your class implements the correct `ReportGeneratorInterface`
4. **Constructor issues**: Set `requiresConfig: true` if your reporter needs `CognitiveConfig`

**Debug Tips:**

- Check the configuration syntax in `config.yml`
- Verify file paths are absolute or relative to the project root
- Test with simple reporters first before complex implementations
- Use the built-in reporters as templates for your custom ones
