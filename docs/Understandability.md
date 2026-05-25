# Understandability (Sonar Cognitive Complexity)

Understandability measures how hard it is for a human to follow a method’s control flow. It implements **Sonar Cognitive Complexity** as described in SonarSource’s 2023 white paper (*Cognitive Complexity: a new way of measuring understandability*, v1.7).

This metric is **separate** from this tool’s weighted **Cognitive Complexity** score, which sums logarithmic weights over structural metrics (lines, arguments, `if` count, and so on). Understandability follows Sonar’s rule-based control-flow model instead.

## Why use it?

Cyclomatic complexity counts paths through code but treats structures like `switch` and nested loops similarly even when one is much harder to read. Sonar Cognitive Complexity is designed to better match maintainer intuition: it penalizes nested flow breaks, treats `switch` as a single decision, and ignores method calls that shorthand logic.

## How it is calculated

Per method, the score follows three rules from the Sonar spec:

1. **Ignore shorthand** — method calls and null-coalescing are not counted.
2. **Increment for flow breaks** — loops, `if`, ternary, `catch`, `switch`/`match`, logical-operator sequences, recursion, and multi-level `break`/`continue`/`goto`.
3. **Increment for nesting** — each nested flow-breaking structure adds its current nesting depth to the score.

Increments fall into four categories (each adds to the total, but categories clarify nesting behavior):

| Category    | Examples                                      |
|-------------|-----------------------------------------------|
| Structural  | `if`, loops, ternary, `catch`, `switch`       |
| Hybrid      | `elseif`, `else` (no nesting penalty, but increase nesting level) |
| Fundamental | Logical-operator sequences, recursion, jumps  |
| Nesting     | Extra points when structures are nested       |

Structural increments use `1 + nestingLevel`; hybrid increments add `1` only.

## Risk levels

| Score | Risk        |
|-------|-------------|
| 0–5   | low         |
| 6–10  | medium      |
| 11–15 | high        |
| 16+   | very high   |

Console output shows `score (risk)`, for example `7 (medium)`.

## Configuration

Understandability is **off by default**. Enable it in `phpcca.yaml`:

```yaml
cognitive:
  showUnderstandability: true
```

When enabled, an **Understandability** column appears in console output. It does not affect baselines, file reports, or sorting unless those features are extended separately.

## Interpretation

- **Low (≤5)** — easy to follow; usually fine as-is.
- **Medium (6–10)** — worth a closer look during review.
- **High / very high (≥11)** — nested or branching logic is taxing; consider extracting methods or simplifying control flow.

Use as an indicator, not an absolute rule. Domain logic, parsers, and constructors may legitimately score higher.

## References

- [Sonar Cognitive Complexity white paper (2023, v1.7)](https://www.sonarsource.com/resources/cognitive-complexity/)
- [An Empirical Validation of Cognitive Complexity as a Measure of Source Code Understandability](https://arxiv.org/pdf/2007.12520) — Muñoz Barón, Wyrich, Wagner
