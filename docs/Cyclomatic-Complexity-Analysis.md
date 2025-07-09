# Cyclomatic Complexity Calculator

This tool calculates cyclomatic complexity for PHP classes and methods. Cyclomatic complexity is a software metric that measures the complexity of a program by counting the number of linearly independent paths through the source code.

## What is Cyclomatic Complexity?

Cyclomatic complexity is calculated as:
- **Base complexity**: 1 (for the entry point)
- **+1 for each decision point**: if statements, loops, switch cases, catch blocks, etc.
- **+1 for each logical operator**: &&, ||, and, or, xor, ternary operators

## Risk Levels

- **Low (1-5)**: Simple, easy to understand and maintain
- **Medium (6-10)**: Moderately complex, may need some refactoring
- **High (11-15)**: Complex, should be refactored
- **Very High (16+)**: Very complex, difficult to maintain and test

## Usage

### Command Line Interface

```bash
# Basic usage
bin/phpcca complexity src/

# With threshold (only show methods with complexity >= 5)
bin/phpcca complexity src/ --threshold=5

# Detailed breakdown
bin/phpcca complexity src/ --detailed

# Output to JSON file
bin/phpcca complexity src/ --format=json --output=complexity.json

# Output to CSV file
bin/phpcca complexity src/ --format=csv --output=complexity.csv
```

### Command Options

- `--format, -f`: Output format (text, json, csv) - default: text
- `--output, -o`: Output file path
- `--threshold, -t`: Minimum complexity threshold to report (default: 1)
- `--detailed, -d`: Show detailed breakdown of complexity factors

### Programmatic Usage

```php
<?php

use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use Phauthentic\CognitiveCodeAnalysis\PhpParser\CyclomaticComplexityVisitor;

// Create parser and traverser
$parser = (new ParserFactory())->createForNewestSupportedVersion();
$traverser = new NodeTraverser();
$visitor = new CyclomaticComplexityVisitor();
$traverser->addVisitor($visitor);

// Parse your PHP code
$code = file_get_contents('your-file.php');
$ast = $parser->parse($code);
$traverser->traverse($ast);

// Get results
$classComplexity = $visitor->getClassComplexity();
$methodComplexity = $visitor->getMethodComplexity();
$methodBreakdown = $visitor->getMethodComplexityBreakdown();
$summary = $visitor->getComplexitySummary();
```

## Complexity Factors

The calculator counts the following complexity factors:

### Control Structures
- `if` statements
- `elseif` statements
- `switch` statements
- `case` statements
- `while` loops
- `do-while` loops
- `for` loops
- `foreach` loops

### Exception Handling
- `catch` blocks

### Logical Operators
- `&&` (logical AND)
- `||` (logical OR)
- `and` (logical AND)
- `or` (logical OR)
- `xor` (logical XOR)
- Ternary operators (`? :`)

## Example Output

### Text Format
```
Cyclomatic Complexity Analysis
Files analyzed: 15

Class Complexity:
  Test\ComplexityTest: 45 (high)
  Test\AnotherComplexityTest: 6 (medium)

Method Complexity:
  Test\ComplexityTest::simpleMethod: 1 (low)
  Test\ComplexityTest::methodWithIf: 2 (low)
  Test\ComplexityTest::highComplexityMethod: 12 (high)
  Test\ComplexityTest::veryHighComplexityMethod: 18 (very_high)

High Risk Methods (≥10):
  Test\ComplexityTest::highComplexityMethod: 12
  Test\ComplexityTest::veryHighComplexityMethod: 18

Summary Statistics:
  Average complexity: 4.2
  Maximum complexity: 18
  Minimum complexity: 1
  Total methods: 10
```

### JSON Format
```json
{
  "summary": {
    "classes": {
      "Test\\ComplexityTest": {
        "complexity": 45,
        "risk_level": "high"
      }
    },
    "methods": {
      "Test\\ComplexityTest::highComplexityMethod": {
        "complexity": 12,
        "risk_level": "high",
        "breakdown": {
          "total": 12,
          "base": 1,
          "if": 3,
          "switch": 1,
          "case": 3,
          "foreach": 1,
          "logical_and": 1
        }
      }
    },
    "high_risk_methods": {
      "Test\\ComplexityTest::highComplexityMethod": 12,
      "Test\\ComplexityTest::veryHighComplexityMethod": 18
    }
  },
  "files_analyzed": 15
}
```

## Best Practices

1. **Keep methods simple**: Aim for complexity ≤ 10
2. **Refactor complex methods**: Break down methods with complexity > 15
3. **Use early returns**: Reduce nesting and complexity
4. **Extract conditions**: Move complex conditions to separate methods
5. **Use strategy pattern**: Replace complex switch statements
6. **Limit logical operators**: Avoid deeply nested AND/OR conditions

## Integration with CI/CD

Add complexity checks to your CI pipeline:

```bash
# Fail if any method has complexity > 15
bin/phpcca complexity src/ --threshold=15 --format=json | jq '.summary.very_high_risk_methods | length == 0'

# Generate complexity report
bin/phpcca complexity src/ --format=json --output=complexity-report.json
```

## Testing

Run the example to see the complexity calculator in action:

```bash
php example_complexity_usage.php
```

This will analyze the `test_complexity.php` file and show detailed complexity metrics for all classes and methods. 