# Halstead Complexity Analysis

Halstead metrics are a set of software metrics introduced by Maurice Halstead to measure the complexity of code based on operators and operands.
These metrics help estimate code maintainability and understandability.

## What are Halstead Metrics?

Halstead metrics are calculated using the following quantities:

- **n₁**: Number of distinct operators
- **n₂**: Number of distinct operands
- **N₁**: Total number of operators
- **N₂**: Total number of operands

From these, several derived metrics are calculated:

| Metric         | Formula                                              | Description                                      |
|----------------|-----------------------------------------------------|--------------------------------------------------|
| Vocabulary     | n = n₁ + n₂                                         | Number of unique operators and operands          |
| Length         | N = N₁ + N₂                                         | Total number of operators and operands           |
| Volume         | V = N × log₂(n)                                     | Size of the implementation                       |
| Difficulty     | D = (n₁ / 2) × (N₂ / n₂)                            | Effort required to understand the code           |
| Effort         | E = D × V                                           | Mental effort to develop or maintain the code    |

Volume is `0` when the vocabulary is `0`, and difficulty is `0` when there are no operands (`n₂ = 0`).

This tool does not calculate the derived Halstead estimates for delivered bugs (`B`) and implementation time (`T`).

## What Counts as an Operator or Operand?

This tool counts a deliberately small subset of PHP syntax:

- **Operators**: binary operations (arithmetic, comparison, logical, string concatenation, and so on), assignments (`=`), function calls, and method calls.
  Operators are distinct by their kind, so `+` and `-` are two distinct operators, and all function calls are one distinct operator.
- **Operands**: variables, string literals, integer literals, and float literals.
  Operands are distinct by their value, so `$a` and `$b` are two distinct operands, and two uses of `$a` are one.

Other syntax, such as control structures, property access, static calls, or compound assignments like `+=`, is not counted.
Because of this, the values are useful to compare methods within your code base, but they are not directly comparable with values from other Halstead tools.

## Why Use Halstead Metrics?

- **Maintainability**: High Halstead volume or effort may indicate code that is hard to maintain.
- **Understandability**: Difficulty and effort metrics help identify code that may be hard to understand.

## How Are Halstead Metrics Used in This Tool?

When enabled in the configuration, the console table shows the Halstead volume, difficulty, and effort for each method.
The results are displayed alongside other complexity metrics to give a more complete picture of code quality.

To enable Halstead metrics in the output, set the following in your configuration file:

```yaml
cognitive:
  showHalsteadComplexity: true
```

## Interpretation

- **Low Volume/Effort**: Code is likely simple and easy to maintain.
- **High Volume/Effort**: Consider refactoring; code may be hard to understand or error-prone.

## References

- [Wikipedia: Halstead complexity measures](https://en.wikipedia.org/wiki/Halstead_complexity_measures)


