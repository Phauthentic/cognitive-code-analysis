# Configuration

## Passing a configuration file

You can specify another configuration file by passing it to the config options:

```bash
php analyse.php metrics:cognitive <path-to-folder> --config=<path-to-config-file>
```

## Tuning the calculation

The configuration file can contain the following settings for the calculation of cognitive complexity.

Feel free to adjust the values to your match your opinion on what makes code complex.

```
metrics:
  lineCount:
    threshold: 60
    scale: 2.0
  argCount:
    threshold: 4
    scale: 1.0
  returnCount:
    threshold: 2
    scale: 5.0
  variableCount:
    threshold: 2
    scale: 5.0
  propertyCallCount:
    threshold: 2
    scale: 15.0
  ifCount:
    threshold: 3
    scale: 1.0
  ifNestingLevel:
    threshold: 1
    scale: 1.0
  elseCount:
    threshold: 1
    scale: 1.0
```

It is recommended to play with the values until you get weights that you are comfortable with. The default values are a good starting point.
