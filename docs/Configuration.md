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
        scale: 25.0
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
        threshold: 4
        scale: 5.0
        enabled: true
      propertyCallCount:
        threshold: 4
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

Each metric's weight is `ln(1 + (value - threshold) / scale)` when the value is above the threshold, and `0` otherwise.
A larger `scale` makes the weight grow more slowly.
See [How is Cognitive Complexity calculated?](./Cognitive-Complexity-Analysis.md#how-is-cognitive-complexity-calculated).

You only need to list the metrics and fields you want to change.
For example, `lineCount: { threshold: 80 }` keeps the default `scale` and `enabled` values of `lineCount`.

### Disabling a metric

Setting `enabled: false` on a metric gives it a weight of `0`, so it does not count toward the combined cognitive complexity score.
The raw value of a disabled metric is still collected and reported; only its weight is zeroed.
`enabled` defaults to `true`.

## Score threshold

```yaml
cognitive:
  scoreThreshold: 0.5
  showOnlyMethodsExceedingThreshold: true
```

- **`scoreThreshold`** (default `0.5`): Methods with a cognitive score greater than this value are highlighted in the console output.
  The `checkstyle`, `junit`, and `gitlab-codequality` reports only contain methods above this value.
- **`showOnlyMethodsExceedingThreshold`** (default `true`): When `true`, the `markdown` report only contains methods with a score greater than `scoreThreshold`.
  The console output and the other reports are not filtered by this option.

## Showing additional metrics

You can show the Halstead Complexity and the Cyclomatic Complexity in the output by setting the following configuration options to `true`, the default is `false`.

This can be useful to get a better understanding of the complexity of your code. Keep in mind that each of them measures a different aspect of complexity, so it is recommended to use them in conjunction with cognitive complexity.

```yaml
cognitive:
  showHalsteadComplexity: false
  showCyclomaticComplexity: false
  showUnderstandability: false
  showDetailedCognitiveMetrics: true
```

- **`showUnderstandability`** (default `false`): Shows the [Understandability](./Understandability.md) score.
- **`showDetailedCognitiveMetrics`** (default `true`): Shows the individual metric columns (line count, arguments, returns, and so on).
  When `false`, only the combined cognitive score is shown.

## Result cache

The analysis result of each file is cached, so later runs only analyse files that changed.
The cache is enabled by default.

```yaml
cognitive:
  cache:
    enabled: true
    directory: './.phpcca.cache'
```

- **`enabled`** (default `true`): Set to `false` to analyse every file on every run.
- **`directory`** (default `./.phpcca.cache`): Where cache files are stored, relative to the directory you run `bin/phpcca` from.
  Add it to your `.gitignore`.

A cached result is used only when the file path, the file modification time, and the configuration are unchanged.
Results for older file versions or configurations are not deleted automatically, so the directory grows over time.

### Clearing the cache

Use `--clear-cache` with `analyse` or `churn` to delete all cached results before the analysis starts:

```bash
bin/phpcca analyse src --clear-cache
bin/phpcca churn src --clear-cache
```

The command then analyses every file again and fills the cache with fresh results.
The console shows `Cache: enabled (cleared)`.
The cache directory of the loaded configuration is cleared, so combine `--clear-cache` with `--config` when you use a custom configuration file.

Only files that the cache itself wrote are deleted, and directories are removed only when they are empty afterwards.
If `directory` points to a directory with other files, those files are kept.
If a cache file cannot be deleted, the command fails with `Failed to clear the cache directory`.

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

## Deprecated options

- **`performance.batchSize`**: Never had an effect, because the analysis does not process or write reports in batches.
  The option is still accepted, but it triggers a deprecation notice and will be removed in 2.0.
  Remove the `performance` block from your configuration file.
