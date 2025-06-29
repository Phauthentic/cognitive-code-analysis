# Churn - Finding Hotspots

Churn is a measure of how much code has changed over time. The `churn` command helps you find the most changed and complex areas in your codebase—these are often the most error-prone and difficult to maintain.

## Usage

```bash
bin/phpcca churn <path-to-folder> [--config=<file>] [--git=<vcs>] [--debug]
```

- `<path-to-folder>`: **Required.** Path to the PHP file or directory to analyze.
- `--config, -c`: Path to a configuration file (optional).
- `--vcs, -s`: Version control system to use for change detection (default: `git`).
- `--report-type, -r`: Type of report to generate (`json`, `csv`, `html`).
- `--report-file, -f`: File to save the report (default: `phpcca-churn-report.html`).
- `--debug`: Enables debug output.

## What it does

1. **Collects cognitive metrics** for each class in the given path.
2. **Counts how many times each file/class has changed** using your VCS (default: Git).
3. **Calculates a churn score**:
   `churn = timesChanged * score`
4. **Ranks classes by churn score** so you can focus on the most critical hotspots.

⚠️ **For the time being only Git is supported as the VCS backend!** ⚠️

## Example Output

```
+----------------------+--------------+--------+-------+
| Class                | TimesChanged | Score  | Churn |
+----------------------+--------------+--------+-------+
| App\Service\Foo      | 12           | 8      | 96    |
| App\Controller\Bar   | 7            | 10     | 70    |
+----------------------+--------------+--------+-------+
```

## Exporting Reports

You can export the churn report in various formats. The command supports three report types: `html`, `json`, and `csv`.

You must use the `--report-type` option to specify the format and the `--report-file` option to specify the output file name together to generate a report.

```bash
bin/phpcca churn <path-to-folder> --report-type=<report-type> --report-file=<filename>
```

Supported report types:

- `html`: Generates an HTML report.
- `json`: Generates a JSON report.
- `csv`: Generates a CSV report.

## When to use

- To prioritize refactoring or testing efforts.
- To identify risky or unstable parts of your codebase.
- To monitor the impact of frequent changes on complex code.

## Notes

- Only classes with a valid class name are included in the results.
- The command supports extensible VCS backends (default is Git).
  - For now only Git is supported.
