# Creating Custom Reporters

This guide explains how to create custom reporters for the Cognitive Code Checker to output metrics in your preferred format.

## Overview

The Cognitive Code Checker supports two types of reports:

- **Cognitive reporter**: Export cognitive complexity metrics
- **Churn reporter**: Export code churn metrics

Both types follow similar patterns but have different interfaces and data structures.

## Reporter Types

### Cognitive reporter

Cognitive reporter handle cognitive complexity metrics data and implement the `ReportGeneratorInterface` from the `Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Report` namespace.

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

### Churn reporter

Churn reporter handle code churn metrics data and implement the `ReportGeneratorInterface` from the `Phauthentic\CognitiveCodeAnalysis\Business\Churn\Report` namespace.

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

Add your custom reporter to the `config.yml` file under the `customReporters` section:

```yaml
cognitive:
  # ... other cognitive settings ...
  customReporters:
    cognitive:
      pdf:  # Custom reporter name
        class: 'My\Custom\PdfReporter'
        file: '/path/to/PdfReporter.php'
    churn:
      churn:  # Custom reporter name
        class: 'My\Custom\ChurnReporter'
        file: '/path/to/ChurnReporter.php'
```

### Configuration Parameters

- **`class`** (required): Fully qualified class name of your reporter
- **`file`** (optional): Path to the PHP file containing your reporter class. Set to `null` if using autoloading

## Constructor Patterns

The system automatically detects whether your reporter needs the `CognitiveConfig` object:

### Reporter with Config Access

```php
class PdfReporter implements ReportGeneratorInterface
{
    private CognitiveConfig $config;

    public function __construct(CognitiveConfig $config)
    {
        $this->config = $config;
    }
    // ... rest of implementation
}
```

### Reporter without Config

```php
class SimpleReporter implements ReportGeneratorInterface
{
    public function __construct()
    {
        // No config needed
    }
    // ... rest of implementation
}
```

The system will automatically try to pass the config to your constructor. If your constructor doesn't accept it, the system will fall back to calling the constructor without arguments.

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

Here's an example of a custom churn reporter for churn metrics:

```php
<?php

declare(strict_types=1);

namespace My\Custom;

use Phauthentic\CognitiveCodeAnalysis\Business\Churn\Report\ReportGeneratorInterface;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;

class ChurnReporter implements ReportGeneratorInterface
{
    public function export(array $classes, string $filename): void
    {
        // Ensure directory exists
        $directory = dirname($filename);
        if (!is_dir($directory)) {
            throw new CognitiveAnalysisException("Directory {$directory} does not exist");
        }

        // Generate churn content
        $churnContent = $this->generateChurnContent($classes);

        // Write to file
        if (file_put_contents($filename, $churnContent) === false) {
            throw new CognitiveAnalysisException("Could not write to file: {$filename}");
        }
    }

    private function generateChurnContent(array $classes): string
    {
        $content = "Churn Report\n";
        $content .= "============\n\n";
        
        foreach ($classes as $className => $data) {
            $content .= "Class: {$className}\n";
            $content .= "File: {$data['file']}\n";
            $content .= "Score: {$data['score']}\n";
            $content .= "Churn: {$data['churn']}\n";
            $content .= "Times Changed: {$data['timesChanged']}\n";
            $content .= "---\n";
        }
        
        return $content;
    }
}
```

## Using Your Custom Reporter

Once configured, you can use your custom reporter by specifying its name when generating reports:

```bash
# For cognitive metrics
php bin/cognitive-report --format=pdf --output=report.pdf

# For churn metrics  
php bin/churn-report --format=churn --output=churn.txt
```

## Best Practices

1. **Error Handling**: Always throw `CognitiveAnalysisException` for errors
2. **File Validation**: Check that directories exist and files are writable
3. **Data Access**: Use the provided methods to access metric data
4. **Configuration**: Use `CognitiveConfig` if you need access to settings
5. **Testing**: Test your reporter with real data to ensure proper formatting

## Built-in reporter Reference

For inspiration, examine the built-in reporter:

**Cognitive reporter:**
- 
- `JsonReport` - JSON format
- `CsvReport` - CSV format  
- `HtmlReport` - HTML with Bootstrap styling
- `MarkdownReport` - Markdown tables

**Churn reporter:**

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
4. **Constructor issues**: Your reporter can optionally accept `CognitiveConfig` in its constructor - the system will automatically detect this

**Debug Tips:**

- Check the configuration syntax in `config.yml`
- Verify file paths are absolute or relative to the project root
- Test with simple reporter first before complex implementations
- Use the built-in reporter as templates for your custom ones
