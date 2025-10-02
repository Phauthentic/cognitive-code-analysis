# Churn - Finding Hotspots

Churn is a measure of how much code has changed over time. The `churn` command helps you find the most changed and complex areas in your codebase—these are often the most error-prone and difficult to maintain.

## Usage

```bash
bin/phpcca churn <path-to-folder> [options]
```

**Options:**
- `<path-to-folder>`: **Required.** Path to the PHP file or directory to analyze.
- `--config, -c`: Path to a configuration file (optional).
- `--vcs, -s`: Version control system to use for change detection (default: `git`).
- `--since`: Start date for counting changes (default: `2000-01-01`).
- `--report-type, -r`: Type of report to generate (`json`, `csv`, `html`).
- `--report-file, -f`: File to save the report (default: `phpcca-churn-report.html`).
- `--coverage-cobertura`: Path to Cobertura XML coverage file to include coverage analysis.
- `--debug`: Enables debug output.

## What it does

1. **Collects cognitive metrics** for each class in the given path.
2. **Counts how many times each file/class has changed** using your VCS (default: Git).
3. **Calculates churn scores**:
   - **Standard Churn**: `churn = timesChanged × cognitiveScore`
   - **Risk Churn** (with coverage): `riskChurn = timesChanged × cognitiveScore × (1 - coverage)`
4. **Assigns risk levels** based on churn and coverage (when coverage data is provided).
5. **Ranks classes by churn score** so you can focus on the most critical hotspots.

⚠️ **For the time being only Git is supported as the VCS backend!** ⚠️

## Basic Example Output

```
+-------------------------+-------+--------+---------------+
| Class                   | Score | Churn  | Times Changed |
+-------------------------+-------+--------+---------------+
| App\Service\Foo         | 8.0   | 96.0   | 12            |
| App\Controller\Bar      | 10.0  | 70.0   | 7             |
+-------------------------+-------+--------+---------------+
```

## Coverage-Weighted Churn Analysis

### Generating Coverage Data

The tool supports both **Cobertura** and **Clover** XML coverage formats.

**Generate Cobertura coverage:**
```bash
XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-cobertura=coverage.xml
```

**Generate Clover coverage:**
```bash
XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-clover=coverage.xml
```

### Running Churn with Coverage

**Using Cobertura format:**
```bash
bin/phpcca churn src --coverage-cobertura=coverage.xml
```

**Using Clover format:**
```bash
bin/phpcca churn src --coverage-clover=coverage.xml
```

**Note:** The tool will auto-detect the format if you use a standard filename, but explicit format options are recommended for clarity.

### Enhanced Output with Coverage

When coverage data is provided, the output includes additional risk analysis columns:

```
+-------------------------+-------+--------+------------+---------------+----------+------------+
| Class                   | Score | Churn  | Risk Churn | Times Changed | Coverage | Risk Level |
+-------------------------+-------+--------+------------+---------------+----------+------------+
| App\Service\Foo         | 8.0   | 96.0   | 86.4       | 12            | 10.00%   | CRITICAL   |
| App\Controller\Bar      | 10.0  | 70.0   | 3.5        | 7             | 95.00%   | LOW        |
| App\Model\Baz           | 6.5   | 26.0   | 13.0       | 4             | 50.00%   | MEDIUM     |
+-------------------------+-------+--------+------------+---------------+----------+------------+
```

### How Risk Churn is Calculated

**Risk Churn** multiplies standard churn by the coverage gap:

```
Risk Churn = Times Changed × Cognitive Score × (1 - Coverage)
```

**Example:**
- Class with 12 changes, score 8.0, and 10% coverage:
  - Standard Churn: `12 × 8.0 = 96.0`
  - Risk Churn: `12 × 8.0 × (1 - 0.10) = 86.4`

This formula prioritizes classes that are:
- Frequently changed (high `timesChanged`)
- Complex (high `cognitiveScore`)
- Poorly tested (low `coverage`)

### Risk Level Thresholds

Risk levels help you prioritize which classes need immediate attention:

| Risk Level | Criteria | Action Required |
|-----------|----------|-----------------|
| **CRITICAL** | Churn > 30 AND Coverage < 50% | Urgent: Add tests and refactor immediately |
| **HIGH** | Churn > 20 AND Coverage < 70% | High Priority: Increase test coverage |
| **MEDIUM** | Churn > 10 AND Coverage < 80% | Medium Priority: Consider adding tests |
| **LOW** | Everything else | Low Priority: Monitor |

### Interpreting the Results

**High Risk Churn** indicates:
- Code that changes frequently without adequate test protection
- Areas where bugs are most likely to be introduced
- Prime candidates for refactoring and test coverage improvement

**Low Risk Churn** indicates:
- Well-tested code that changes frequently (good!)
- Stable code with high coverage (safe to modify)

### Use Cases

1. **Prioritize Testing Efforts**: Focus on CRITICAL and HIGH risk classes first
2. **Refactoring Planning**: Target high churn + low coverage areas
3. **Code Review Focus**: Extra scrutiny for changes in high-risk classes
4. **Technical Debt Tracking**: Monitor risk levels over time

## Exporting Reports

You can export the churn report in various formats. The command supports four report types: `html`, `json`, `csv`, and `svg-treemap`.

You must use the `--report-type` option to specify the format and the `--report-file` option to specify the output file name together to generate a report.

```bash
bin/phpcca churn <path-to-folder> --report-type=<report-type> --report-file=<filename>
```

### Supported Report Types

| Format | Description | Use Case |
|--------|-------------|----------|
| `html` | Interactive HTML report | Easy sharing and viewing in browsers |
| `json` | Structured JSON data | Integration with other tools, APIs |
| `csv` | Comma-separated values | Import into spreadsheets, data analysis |
| `svg-treemap` | Visual treemap representation | Visual overview of churn distribution |

### Examples

```bash
# Generate HTML report
bin/phpcca churn src --report-type=html --report-file=churn-report.html

# Generate JSON report for CI/CD integration
bin/phpcca churn src --report-type=json --report-file=churn-report.json

# Generate CSV for spreadsheet analysis
bin/phpcca churn src --report-type=csv --report-file=churn-report.csv

# Generate SVG treemap for visual analysis
bin/phpcca churn src --report-type=svg-treemap --report-file=churn-treemap.svg
```

**Note:** Coverage data is currently only available in console output, not in exported reports.

## Notes

- Only classes with a valid class name are included in the results.
- The command supports extensible VCS backends (default is Git).
  - For now only Git is supported.
- Coverage data is optional; the command works with or without it.
- When coverage is not found for a class, it assumes 0% coverage for risk calculation.
