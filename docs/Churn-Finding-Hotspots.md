# Churn: Finding Hotspots

The `churn` command helps you identify "hotspots" in your codebase—classes that change frequently and have high cognitive complexity. These hotspots are likely candidates for refactoring or closer review.

## What is Churn?

Churn is calculated as:

```
churn = timesChanged * cognitiveScore
```

Where:
- **timesChanged**: How often a class has changed (from version control or metrics).
- **cognitiveScore**: The cognitive complexity score of the class.

Classes with high churn are both complex and frequently modified, making them riskier and more costly to maintain.

## Usage

```bash
php bin/console churn <path> [--config=<file>] [--debug]
```

- `<path>`: Path to the PHP files or directories to analyze (required).
- `--config, -c`: Path to a configuration file (optional).
- `--debug`: Enables debug output (optional).

## Example

```bash
php bin/console churn src/
```

## Output

The command outputs a table listing classes sorted by their churn value in descending order. Each row contains:

- Class name
- Number of times changed
- Cognitive complexity score
- Churn value

## When to Use

- To identify risky, complex, and frequently changing classes.
- As part of code review or refactoring planning.
- To monitor hotspots over time.
