# Cyclomatic Complexity

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

## Best Practices

1. **Keep methods simple**: Aim for complexity ≤ 10
2. **Refactor complex methods**: Break down methods with complexity > 15
3. **Use early returns**: Reduce nesting and complexity
4. **Extract conditions**: Move complex conditions to separate methods
5. **Use strategy pattern**: Replace complex switch statements
6. **Limit logical operators**: Avoid deeply nested AND/OR conditions

## How Are Cyclomatic Metrics Used in This Tool?

Cyclomatic complexity is calculated for each class and method in your codebase. The results are shown alongside other metrics to help you identify complex, hard-to-test, or risky code.

To enable cyclomatic complexity in the output, set the following in your configuration file:

```yaml
cognitive:
  showCyclomaticComplexity: true
```

When enabled, the tool will display cyclomatic complexity scores in the analysis report, allowing you to spot methods and classes that may need refactoring or additional testing.
