# Sorting and Filtering

The cognitive code analysis tool provides powerful sorting and filtering capabilities to help you organize and focus on the most relevant results from your code analysis.

## Sorting Results

You can sort analysis results by various metrics to identify the most complex or problematic code areas.

### Command Line Options

```bash
bin/phpcca analyse <path-to-folder> --sort-by=<field> --sort-order=<order>
```

#### Available Options

- `--sort-by, -s`: Field to sort by (optional)
- `--sort-order`: Sort order - `asc` (ascending) or `desc` (descending), default: `asc`

### Sortable Fields

The following fields are available for sorting:

| Field | Description |
|-------|-------------|
| `score` | Cognitive complexity score |
| `halstead` | Halstead complexity metrics |
| `cyclomatic` | Cyclomatic complexity |
| `class` | Class name (alphabetical) |
| `method` | Method name (alphabetical) |
| `lineCount` | Number of lines of code |
| `argCount` | Number of method arguments |
| `returnCount` | Number of return statements |
| `variableCount` | Number of variables used |
| `propertyCallCount` | Number of property accesses |
| `ifCount` | Number of if statements |
| `ifNestingLevel` | Maximum nesting level of if statements |
| `elseCount` | Number of else statements |

### Examples

Sort by cognitive complexity score (highest first):
```bash
bin/phpcca analyse src/ --sort-by=score --sort-order=desc
```

Sort by method name alphabetically:
```bash
bin/phpcca analyse src/ --sort-by=method --sort-order=asc
```

Sort by cyclomatic complexity:
```bash
bin/phpcca analyse src/ --sort-by=cyclomatic --sort-order=desc
```

## Filtering and Grouping

### Grouping by Class

By default, results are grouped by class to make it easier to understand complexity within specific classes. This behavior can be controlled via configuration:

```yaml
cognitive:
  groupByClass: true  # Default: true
```

- **`true`**: Results are grouped by class, showing separate tables for each class
- **`false`**: Results are displayed as a flat list without grouping

### Excluding Classes and Methods

You can exclude specific classes and methods from analysis using regex patterns in your configuration file:

```yaml
cognitive:
  excludePatterns:
    - '(.*)::__construct'        # Exclude all constructors
    - '(.*)::toArray'            # Exclude all toArray methods
    - '(.*)Transformer::(.*)'    # Exclude all methods in Transformer classes
```

### Excluding Files

You can exclude entire files from analysis:

```yaml
cognitive:
  excludeFilePatterns:
    - '.*Cognitive.*'            # Exclude files with "Cognitive" in the name
    - '(.*)Test.php'             # Exclude all test files
```

## Error Handling

If you specify an invalid sort field, the tool will display an error message with the list of available fields:

```bash
bin/phpcca analyse src/ --sort-by=invalidField
# Output: Sorting error: Invalid sort field "invalidField". Available fields: score, halstead, cyclomatic, class, method, lineCount, argCount, returnCount, variableCount, propertyCallCount, ifCount, ifNestingLevel, elseCount
```
