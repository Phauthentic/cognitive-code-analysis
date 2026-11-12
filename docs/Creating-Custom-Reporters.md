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
    public function export(ChurnMetricsCollection $metrics, string $filename): void;
}
```

**Data Structure:** `ChurnMetricsCollection` (from `Phauthentic\CognitiveCodeAnalysis\Business\Churn`) contains one `ChurnMetrics` object per class with methods like:

- `getClassName()` - Class name
- `getFile()` - File path
- `getScore()` - Cognitive complexity score of the class (sum of its method scores)
- `getTimesChanged()` - Number of times the file changed
- `getChurn()` - Churn value (`timesChanged × score`)
- `getCoverage()` - Test coverage between `0.0` and `1.0`, or `null` without coverage data
- `getRiskChurn()` - Churn weighted by missing coverage, or `null` without coverage data
- `getRiskLevel()` - Risk level, or `null` without coverage data
- `hasCoverageData()` - Whether coverage data is available
- `hasRiskData()` - Whether risk churn and risk level are available

The collection itself is iterable and countable, and it provides helpers to select and order the classes for your report.
The filter and sort helpers return a new collection and leave the original unchanged.

- `filter(Closure $callback)` - Classes for which the callback returns `true`
- `filterWithChurnGreaterThan(float $churn)` - Classes with a churn above the given value
- `filterWithScoreGreaterThan(float $score)` - Classes with a score above the given value
- `filterWithCoverage()` - Classes that have coverage data
- `filterWithRiskData()` - Classes that have risk data
- `sortByChurnDescending()` - Classes ordered by churn, highest first
- `sortByScoreDescending()` - Classes ordered by score, highest first
- `getByClassName(string $className)` - The metrics of one class, or `null` if it is not in the collection
- `contains(string $className)` - Whether the collection has metrics for the class
- `getClassNames()` - All class names in the collection
- `isEmpty()` - Whether the collection has no classes

## Configuration

Add your custom reporter to your configuration file (for example `phpcca.yaml`) under the `customReporters` section:

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

use Phauthentic\CognitiveCodeAnalysis\Business\Churn\ChurnMetricsCollection;
use Phauthentic\CognitiveCodeAnalysis\Business\Churn\Report\ReportGeneratorInterface;
use Phauthentic\CognitiveCodeAnalysis\CognitiveAnalysisException;

class ChurnReporter implements ReportGeneratorInterface
{
    public function export(ChurnMetricsCollection $metrics, string $filename): void
    {
        // Ensure directory exists
        $directory = dirname($filename);
        if (!is_dir($directory)) {
            throw new CognitiveAnalysisException("Directory {$directory} does not exist");
        }

        // Generate churn content
        $churnContent = $this->generateChurnContent($metrics);

        // Write to file
        if (file_put_contents($filename, $churnContent) === false) {
            throw new CognitiveAnalysisException("Could not write to file: {$filename}");
        }
    }

    private function generateChurnContent(ChurnMetricsCollection $metrics): string
    {
        $content = "Churn Report\n";
        $content .= "============\n\n";

        foreach ($metrics as $metric) {
            $content .= "Class: {$metric->getClassName()}\n";
            $content .= "File: {$metric->getFile()}\n";
            $content .= "Score: {$metric->getScore()}\n";
            $content .= "Churn: {$metric->getChurn()}\n";
            $content .= "Times Changed: {$metric->getTimesChanged()}\n";
            $content .= "---\n";
        }

        return $content;
    }
}
```

## Using Your Custom Reporter

Once configured, use the reporter name as the `--report-type` and pass a `--report-file`:

```bash
# For cognitive metrics
bin/phpcca analyse src --config=phpcca.yaml --report-type=pdf --report-file=report.pdf

# For churn metrics
bin/phpcca churn src --config=phpcca.yaml --report-type=churn --report-file=churn.txt
```

Built-in report types take precedence, so do not give a custom reporter the name of a built-in report type.
The reporter is checked before the analysis starts, and the command fails if its `class` or `file` cannot be loaded.

## Best Practices

1. **Error Handling**: Always throw `CognitiveAnalysisException` for errors
2. **File Validation**: Check that directories exist and files are writable
3. **Data Access**: Use the provided methods to access metric data
4. **Configuration**: Use `CognitiveConfig` if you need access to settings
5. **Testing**: Test your reporter with real data to ensure proper formatting

## Built-in reporter Reference

For inspiration, examine the built-in reporter:

**Cognitive reporter:**

- `JsonReport` - JSON format
- `CsvReport` - CSV format
- `HtmlReport` - HTML with Bootstrap styling
- `MarkdownReport` - Markdown tables
- `CheckstyleReport` - Checkstyle XML
- `JUnitReport` - JUnit XML
- `SarifReport` - SARIF
- `GitLabCodeQualityReport` - GitLab Code Quality JSON
- `GitHubActionsReport` - GitHub Actions workflow commands

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

- Check the configuration syntax in your configuration file
- Verify file paths are absolute or relative to the directory you run `bin/phpcca` from
- Test with simple reporter first before complex implementations
- Use the built-in reporter as templates for your custom ones
