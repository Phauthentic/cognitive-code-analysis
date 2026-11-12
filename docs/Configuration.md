# Configuration

## Creating a configuration file

Run `init` to create a `phpcca.yaml` in your project:

```bash
bin/phpcca init
bin/phpcca init --silent
bin/phpcca init --path=/path/to/phpcca.yaml
```

When `phpcca.yaml` exists in the working directory, `analyse` and `churn` pick it up automatically. You only need `--config` for a custom path or filename.

## Passing a configuration file

You can specify another configuration file by passing it to the config options:

```bash
bin/phpcca analyse <path-to-folder> --config=<path-to-config-file>
```

When both `phpcca.yaml` and `--config` are available, the explicit `--config` path takes precedence.

## Excluding Classes and Methods

You can exclude classes and methods via a regex in the configuration.

The following configuration will exclude all constructors, `toArray` methods and all methods of classes that end with `Transformer`.

```yaml
cognitive:
  excludePatterns:
    - '(.*)::__construct'
    - '(.*)::toArray'
    - '(.*)Transformer::(.*)'
```

## Exclude Files

You can exclude files via a regex in the configuration.

```yaml
cognitive:
  excludeFilePatterns:
    - '.*Cognitive.*'
    - '(.*)Test.php'
```

## Tuning the calculation

The configuration file can contain the following settings for the calculation of cognitive complexity.

Feel free to adjust the values to your match **your opinion** on what makes code complex. It is recommended to play with the values until you get weights that you are comfortable with. The default values are a good starting point.

```yaml
cognitive:
    metrics:
      lineCount:
        threshold: 60
        scale: 2.0
        enabled: true
      argCount:
        threshold: 4
        scale: 1.0
        enabled: true
      returnCount:
        threshold: 2
        scale: 5.0
        enabled: true
      variableCount:
        threshold: 2
        scale: 5.0
        enabled: true
      propertyCallCount:
        threshold: 2
        scale: 15.0
        enabled: true
      ifCount:
        threshold: 3
        scale: 1.0
        enabled: true
      ifNestingLevel:
        threshold: 1
        scale: 1.0
        enabled: true
      elseCount:
        threshold: 1
        scale: 1.0
        enabled: true
```

### Disabling a metric

Setting `enabled: false` on a metric gives it a weight of `0`, so it does not count toward the combined cognitive complexity score.
The raw value of a disabled metric is still collected and reported; only its weight is zeroed.
`enabled` defaults to `true`.

## Showing additional metrics

You can show the Halstead Complexity and the Cyclomatic Complexity in the output by setting the following configuration options to `true`, the default is `false`.

This can be useful to get a better understanding of the complexity of your code. Keep in mind that each of them measures a different aspect of complexity, so it is recommended to use them in conjunction with cognitive complexity.

```yaml
cognitive:
  showHalsteadComplexity: false
  showCyclomaticComplexity: false
```

## Grouping Results by Class

You can control how the analysis results are displayed by setting the `groupByClass` option.

```yaml
cognitive:
  groupByClass: true
```

- **`true` (default)**: Results are grouped by class, showing all methods within each class together
- **`false`**: Results are displayed as a flat list without grouping

When `groupByClass` is enabled, the output will show separate tables for each class, making it easier to understand the complexity within specific classes. When disabled, all methods are shown in a single table sorted by their complexity score.

## CSV Report: If Count Columns

The CSV report does not contain the `If Count`, `If Count Weight` and `If Count Weight Delta` columns by default.
Set `csvAppendIfCountColumns` to `true` to append them at the very end of each row, after `Combined Cognitive Complexity`.

```yaml
cognitive:
  csvAppendIfCountColumns: false
```

This option only exists to keep the CSV column positions backwards compatible for consumers that read columns by index.
It will be removed in the next major version, when the If Count columns move to their logical position before `If Nesting Level`.
