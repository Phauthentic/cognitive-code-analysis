# Halstead Complexity Analysis

Halstead metrics are a set of software metrics introduced by Maurice Halstead to measure the complexity of code based on operators and operands. These metrics help estimate code maintainability, understandability, and potential error rates.

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
| Bugs           | B = V / 3000                                        | Estimated number of errors                       |
| Time           | T = E / 18                                          | Estimated time to implement (seconds)            |

## Why Use Halstead Metrics?

- **Maintainability**: High Halstead volume or effort may indicate code that is hard to maintain.
- **Understandability**: Difficulty and effort metrics help identify code that may be hard to understand.
- **Error Prediction**: The bugs metric provides a rough estimate of potential defects.

## How Are Halstead Metrics Used in This Tool?

When enabled in the configuration, Halstead metrics are calculated for each class and method. The results can be displayed alongside other complexity metrics to give a more complete picture of code quality.

To enable Halstead metrics in the output, set the following in your configuration file:

```yaml
cognitive:
  showHalsteadComplexity: true
```

## Interpretation

- **Low Volume/Effort**: Code is likely simple and easy to maintain.
- **High Volume/Effort**: Consider refactoring; code may be hard to understand or error-prone.
- **Bugs**: Use as a rough indicator, not an absolute prediction.

## References

- [Wikipedia: Halstead complexity measures](https://en.wikipedia.org/wiki/Halstead_complexity_measures)


