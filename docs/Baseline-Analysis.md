# Baseline Analysis

The baseline analysis feature allows you to compare current cognitive complexity metrics against previously recorded metrics to track changes over time. This is particularly useful for monitoring code quality improvements or regressions during development.

## Overview

The baseline system works by:

1. **Loading baseline data** from a JSON file containing previous metrics
2. **Calculating deltas** between current and baseline metrics for each method
3. **Displaying changes** in the output with visual indicators (Δ symbols)
4. **Tracking improvements and regressions** across all cognitive complexity metrics
5. **Validating configuration** to ensure accurate comparisons
6. **Auto-generating baseline files** with metadata and timestamps

## Usage

### Command Line Options

#### Compare Against Baseline
Use the `--baseline` (or `-b`) option to specify a baseline file:

```bash
bin/phpcca analyse <path-to-folder> --baseline=<baseline-file.json>
```

#### Automatic Baseline Detection
If no baseline file is specified, the system automatically searches for the latest baseline file:

```bash
# Automatically uses the latest baseline file from ./.phpcca/baseline/
bin/phpcca analyse <path-to-folder>
```

The system will:
1. Look for baseline files in `./.phpcca/baseline/` directory
2. Find files matching pattern `baseline-*.json`
3. Select the most recently modified file
4. Display a message indicating which baseline was auto-detected

#### Generate New Baseline
Use the `--generate-baseline` (or `-g`) option to create a new baseline file:

```bash
# Generate with auto-generated timestamped filename
bin/phpcca analyse <path-to-folder> --generate-baseline

# Generate with custom filename
bin/phpcca analyse <path-to-folder> --generate-baseline=my-baseline.json
```

### Examples

```bash
# Automatic baseline detection (uses latest from ./.phpcca/baseline/)
bin/phpcca analyse src/

# Generate initial baseline
bin/phpcca analyse src/ --generate-baseline

# Compare against specific baseline
bin/phpcca analyse src/ --baseline=./.phpcca/baseline/baseline-2025-01-18_14-30-45.json

# Generate baseline and compare in one command
bin/phpcca analyse src/ --generate-baseline --baseline=previous-baseline.json
```

## Baseline File Formats

### New Format (Version 2.0)

The new baseline format includes metadata for better validation and tracking:

```json
{
  "version": "2.0",
  "createdAt": "2025-01-18 14:30:45",
  "configHash": "abc123def456...",
  "metrics": {
    "ClassName": {
      "methods": {
        "methodName": {
          "class": "ClassName",
          "method": "methodName",
          "file": "/path/to/file.php",
          "line": 42,
          "lineCount": 15,
          "argCount": 3,
          "returnCount": 1,
          "variableCount": 5,
          "propertyCallCount": 2,
          "ifCount": 2,
          "ifNestingLevel": 1,
          "elseCount": 1,
          "lineCountWeight": 0.0,
          "argCountWeight": 0.0,
          "returnCountWeight": 0.0,
          "variableCountWeight": 0.0,
          "propertyCallCountWeight": 0.0,
          "ifCountWeight": 0.0,
          "ifNestingLevelWeight": 0.0,
          "elseCountWeight": 0.0,
          "score": 2.5
        }
      }
    }
  }
}
```

### Legacy Format (Backward Compatible)

The old format is still supported for backward compatibility:

```json
{
  "ClassName": {
    "methods": {
      "methodName": {
        "class": "ClassName",
        "method": "methodName",
        "file": "/path/to/file.php",
        "line": 42,
        "lineCount": 15,
        "argCount": 3,
        "returnCount": 1,
        "variableCount": 5,
        "propertyCallCount": 2,
        "ifCount": 2,
        "ifNestingLevel": 1,
        "elseCount": 1,
        "lineCountWeight": 0.0,
        "argCountWeight": 0.0,
        "returnCountWeight": 0.0,
        "variableCountWeight": 0.0,
        "propertyCallCountWeight": 0.0,
        "ifCountWeight": 0.0,
        "ifNestingLevelWeight": 0.0,
        "elseCountWeight": 0.0,
        "score": 2.5
      }
    }
  }
}
```

### Metadata Fields

#### Version
- **Purpose**: Identifies the baseline file format version
- **Values**: `"2.0"` for new format, absent for legacy format

#### Created At
- **Purpose**: Records when the baseline was generated
- **Format**: `YYYY-MM-DD HH:MM:SS` (ISO-like format)
- **Example**: `"2025-01-18 14:30:45"`

#### Config Hash
- **Purpose**: Ensures baseline was generated with same configuration
- **Scope**: Only metrics configuration (thresholds, scales)
- **Format**: MD5 hash of serialized metrics config
- **Example**: `"abc123def456789..."`

## JSON Schema Validation

All baseline files are automatically validated against a JSON Schema to ensure data integrity and format compliance.

### Schema Location
- **Schema File**: `schemas/baseline.json`
- **Schema ID**: `https://github.com/phauthentic/cognitive-code-checker/schemas/baseline.json`
- **Draft Version**: JSON Schema Draft 7

### Validation Features
- **Format Detection**: Automatically detects new (v2.0) vs legacy format
- **Field Validation**: Validates all required and optional fields
- **Type Checking**: Ensures correct data types for all fields
- **Range Validation**: Validates numeric ranges (e.g., non-negative integers)
- **Pattern Matching**: Validates date format and string patterns
- **Comprehensive Errors**: Provides detailed error messages for validation failures

### Validation Errors
When a baseline file fails validation, you'll see detailed error messages:

```
Invalid baseline file format: Invalid createdAt format. Expected: YYYY-MM-DD HH:MM:SS, 
Invalid configHash. Must be a non-empty string, 
Field 'lineCount' in method 'TestClass::testMethod' must be a non-negative integer
```

### Supported Formats
The schema validates both:
- **New Format (v2.0)**: With metadata fields (`version`, `createdAt`, `configHash`, `metrics`)
- **Legacy Format**: Direct class structure (backward compatible)

## Configuration Validation

The system automatically validates that baseline files were generated with compatible configuration:

### Config Hash Validation
- **Automatic**: Compares baseline's config hash with current config
- **Scope**: Only metrics configuration (excludes display settings)
- **Behavior**: Shows warning if hashes don't match, continues with comparison

### Warning Messages
When config hashes don't match, you'll see:
```
Warning: Baseline config hash (abc123...) does not match current config hash (def456...). 
Metrics comparison may not be accurate.
```

## Auto-Generated Baseline Files

When using `--generate-baseline` without specifying a filename, the system automatically creates timestamped files:

### Default Location
```
./.phpcca/baseline/baseline-YYYY-MM-DD_HH-MM-SS.json
```

### Examples
- `./.phpcca/baseline/baseline-2025-01-18_14-30-45.json`
- `./.phpcca/baseline/baseline-2025-01-18_09-15-22.json`

### Directory Creation
The system automatically creates the `./.phpcca/baseline/` directory if it doesn't exist.

## Automatic Baseline Detection

When no baseline file is explicitly provided, the system automatically searches for and uses the latest baseline file:

### Detection Process
1. **Directory Scan**: Searches `./.phpcca/baseline/` directory
2. **Pattern Matching**: Finds files matching `baseline-*.json` pattern
3. **Validation**: Verifies files are valid baseline format (old or new)
4. **Selection**: Chooses the most recently modified file
5. **Notification**: Displays which baseline was auto-detected

### Example Output
When automatic detection is used, you'll see:
```
Auto-detected latest baseline file: baseline-2025-01-18_14-30-45.json
```

### Behavior
- **No baseline found**: Analysis runs without delta comparison
- **Multiple baselines**: Uses the most recently modified file
- **Invalid files**: Skips corrupted or invalid baseline files
- **Explicit baseline**: Always uses the specified file (overrides auto-detection)

### Use Cases
- **Daily development**: Run analysis without specifying baseline each time
- **CI/CD pipelines**: Automatic baseline comparison without configuration
- **Team workflows**: Consistent baseline usage across team members

## Creating Baseline Files

### Method 1: Auto-Generation (Recommended)
Generate a baseline file with metadata:

```bash
# Auto-generated timestamped filename
bin/phpcca analyse src/ --generate-baseline

# Custom filename
bin/phpcca analyse src/ --generate-baseline=my-baseline.json
```

### Method 2: Export Current Analysis (Legacy)
Generate a baseline file by exporting your current analysis:

```bash
# Run analysis and export to JSON
bin/phpcca analyse src/ --report-type=json --report-file=baseline.json

# Use the exported file as baseline for future comparisons
bin/phpcca analyse src/ --baseline=baseline.json
```

### Method 3: Manual Creation
Create a baseline file manually by copying the structure from a previous analysis export and modifying the values as needed.

## Migration from Legacy Format

### Automatic Detection
The system automatically detects baseline file format:
- **New format**: Contains `version` field
- **Legacy format**: Missing `version` field

### Upgrading Legacy Baselines
To upgrade legacy baseline files to the new format:

```bash
# Generate new baseline with current analysis
bin/phpcca analyse src/ --generate-baseline=upgraded-baseline.json

# Use the new baseline for future comparisons
bin/phpcca analyse src/ --baseline=upgraded-baseline.json
```

### Backward Compatibility
- **Legacy baselines**: Continue to work without modification
- **New baselines**: Include metadata for better validation
- **Mixed usage**: Can use both formats in the same project

## Delta Calculation

The system calculates deltas for each weighted metric by comparing:

- **Baseline value** (from the baseline file)
- **Current value** (from the current analysis)

### Delta Display

Deltas are displayed in the output with visual indicators:

- **`Δ +X.XXX`** (red): Metric has increased (worse)
- **`Δ -X.XXX`** (green): Metric has decreased (better)
- **No delta shown**: Metric has not changed

### Example Output

```
+------------------+--------+----------+----------+----------+
| Class            | Method | Line Cnt | Arg Cnt  | Score    |
+------------------+--------+----------+----------+----------+
| App\Service\User | create | 15 (0.0) | 3 (0.0)  | 2.5      |
|                  |        | Δ +1.2   |          |          |
| App\Service\User | update | 12 (0.0) | 2 (0.0)  | 1.8      |
|                  |        | Δ -0.5   |          |          |
+------------------+--------+----------+----------+----------+
```

## Pipeline Integration

The baseline functionality is integrated into the command pipeline as a dedicated stage:

### Pipeline Order

1. **Validation Stage** - Validates command arguments
2. **Configuration Stage** - Loads configuration
3. **Coverage Stage** - Processes coverage data (if provided)
4. **Metrics Collection Stage** - Collects current metrics
5. **Baseline Stage** - **Applies baseline comparison** ←
6. **Sorting Stage** - Sorts results
7. **Report Generation Stage** - Generates reports
8. **Output Stage** - Displays results

### Baseline Stage Behavior

- **Skipped**: If no baseline file is provided
- **Executed**: If baseline file is provided and exists
- **Error**: If baseline file doesn't exist or is invalid JSON

## Error Handling

The baseline system handles several error conditions:

### File Not Found
```
Error: Baseline file does not exist.
```

### Invalid JSON
```
Error: Failed to process baseline: Syntax error
```

### Missing Metrics
If a method exists in the baseline but not in the current analysis, it's silently skipped.

If a method exists in the current analysis but not in the baseline, no delta is calculated.

## Use Cases

### 1. Code Quality Monitoring

Track cognitive complexity changes over time:

```bash
# Initial baseline
bin/phpcca analyse src/ --report-type=json --report-file=baseline-v1.0.json

# After refactoring
bin/phpcca analyse src/ --baseline=baseline-v1.0.json
```

### 2. CI/CD Integration

Include baseline comparison in your continuous integration:

```bash
# In your CI pipeline
bin/phpcca analyse src/ --baseline=baseline.json --report-type=json --report-file=analysis.json
```

### 3. Regression Detection

Identify when code changes increase complexity:

```bash
# Before feature development
bin/phpcca analyse src/ --report-type=json --report-file=pre-feature.json

# After feature development
bin/phpcca analyse src/ --baseline=pre-feature.json
```

### 4. Refactoring Validation

Verify that refactoring efforts reduce complexity:

```bash
# Before refactoring
bin/phpcca analyse src/ --report-type=json --report-file=pre-refactor.json

# After refactoring
bin/phpcca analyse src/ --baseline=pre-refactor.json
```

## Best Practices

### 1. Regular Baseline Updates

Update your baseline files regularly to maintain relevance:

```bash
# Weekly baseline update
bin/phpcca analyse src/ --report-type=json --report-file=baseline-$(date +%Y%m%d).json
```

### 2. Version Control

Store baseline files in version control to track changes over time:

```bash
git add baseline.json
git commit -m "Update cognitive complexity baseline"
```

### 3. Automated Baseline Generation

Create automated scripts to generate baselines:

```bash
#!/bin/bash
# generate-baseline.sh
bin/phpcca analyse src/ --report-type=json --report-file=baseline-$(date +%Y%m%d).json
echo "Baseline generated: baseline-$(date +%Y%m%d).json"
```

### 4. Multiple Baselines

Maintain different baselines for different purposes:

- `baseline-main.json` - Main branch baseline
- `baseline-feature.json` - Feature branch baseline
- `baseline-release.json` - Release baseline

## Configuration Integration

Baseline functionality works with all configuration options:

```bash
bin/phpcca analyse src/ \
  --baseline=baseline.json \
  --config=config.yml \
  --sort-by=score \
  --sort-order=desc \
  --report-type=html \
  --report-file=analysis-with-baseline.html
```

## Limitations

1. **Method Matching**: Deltas are only calculated for methods that exist in both baseline and current analysis
2. **Class Matching**: Methods must have the same class and method name to be matched
3. **File Changes**: If file paths change, methods won't be matched
4. **New Methods**: New methods won't have delta information
5. **Removed Methods**: Removed methods are silently ignored

## Troubleshooting

### Common Issues

**Baseline file not found:**
- Check file path is correct
- Ensure file exists and is readable

**Invalid JSON in baseline:**
- Validate JSON syntax
- Check for trailing commas or missing quotes

**No deltas shown:**
- Verify method names match exactly
- Check that baseline contains the expected methods
- Ensure detailed metrics are enabled in configuration

**Unexpected delta values:**
- Verify baseline file contains correct metric values
- Check that baseline was generated with same configuration

### Debug Mode

Use debug mode to see more information about baseline processing:

```bash
bin/phpcca analyse src/ --baseline=baseline.json --debug
```

This will show timing information and help identify issues with baseline processing.
