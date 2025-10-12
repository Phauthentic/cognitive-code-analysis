# Specification Pattern Implementation for ChurnCommand

## Overview

The Specification pattern has been successfully implemented in the `ChurnCommand` to reduce conditional complexity and improve maintainability. This refactoring separates validation logic from business logic and makes the code more testable and extensible.

## Files Created

### Core Pattern Files
- `src/Command/ChurnSpecifications/ChurnCommandContext.php` - Context object holding command input data
- `src/Command/ChurnSpecifications/ChurnCommandValidationSpecification.php` - Base interface for specifications
- `src/Command/ChurnSpecifications/CompositeChurnValidationSpecification.php` - Composite pattern for combining specifications

### Individual Specifications
- `src/Command/ChurnSpecifications/CoverageFormatExclusivitySpecification.php` - Validates only one coverage format is specified
- `src/Command/ChurnSpecifications/CoverageFileExistsSpecification.php` - Validates coverage file exists
- `src/Command/ChurnSpecifications/CoverageFormatSupportedSpecification.php` - Validates coverage format is supported
- `src/Command/ChurnSpecifications/ReportOptionsCompleteSpecification.php` - Validates report options are complete

### Test File
- `tests/Command/ChurnSpecifications/ChurnSpecificationPatternTest.php` - Unit tests demonstrating the pattern

## Key Changes

### Before (Original ChurnCommand.execute method)
```php
protected function execute(InputInterface $input, OutputInterface $output): int
{
    // Load configuration if provided
    $configFile = $input->getOption(self::OPTION_CONFIG_FILE);
    if ($configFile !== null) {
        if (!$this->loadConfiguration($configFile, $output)) {
            return self::FAILURE;
        }
    }

    $coberturaFile = $input->getOption(self::OPTION_COVERAGE_COBERTURA);
    $cloverFile = $input->getOption(self::OPTION_COVERAGE_CLOVER);

    // Validate that only one coverage option is specified
    if ($coberturaFile !== null && $cloverFile !== null) {
        $output->writeln('<error>Only one coverage format can be specified at a time.</error>');
        return self::FAILURE;
    }

    $coverageFile = $coberturaFile ?? $cloverFile;
    $coverageFormat = $coberturaFile !== null ? 'cobertura' : ($cloverFile !== null ? 'clover' : null);

    if (!$this->coverageFileExists($coverageFile, $output)) {
        return self::FAILURE;
    }

    $coverageReader = $this->loadCoverageReader($coverageFile, $coverageFormat, $output);
    if ($coverageReader === false) {
        return self::FAILURE;
    }

    // ... rest of method
}
```

### After (Refactored with Specifications)
```php
protected function execute(InputInterface $input, OutputInterface $output): int
{
    $context = new CommandContext($input);

    // Validate all specifications
    if (!$this->validationSpecification->isSatisfiedBy($context)) {
        $failedSpec = $this->validationSpecification->getFirstFailedSpecification($context);
        $output->writeln('<error>' . $failedSpec->getErrorMessage() . '</error>');
        return self::FAILURE;
    }

    // Load configuration if provided
    if ($context->hasConfigFile()) {
        if (!$this->loadConfiguration($context->getConfigFile(), $output)) {
            return self::FAILURE;
        }
    }

    // Load coverage reader
    $coverageReader = $this->loadCoverageReader($context, $output);
    if ($coverageReader === false) {
        return self::FAILURE;
    }

    // ... rest of method
}
```

## Benefits Achieved

### 1. **Reduced Conditional Complexity**
- Eliminated multiple nested if statements
- Single validation point with clear error handling
- Cleaner, more readable execute method

### 2. **Separation of Concerns**
- Validation logic separated from business logic
- Each validation rule is isolated and focused
- Easy to understand what each specification validates

### 3. **Improved Testability**
- Each specification can be unit tested independently
- Mock CommandContext for testing different scenarios
- Clear test cases for each validation rule

### 4. **Enhanced Maintainability**
- Adding new validation rules requires only creating a new specification
- No need to modify existing code
- Easy to reorder or remove validation rules

### 5. **Better Reusability**
- Specifications can be reused across different commands
- Composite specifications can be combined in different ways
- Easy to create command-specific validation sets

### 6. **Consistent Error Handling**
- Standardized error message format
- First failure stops validation chain (fail-fast)
- Clear, specific error messages for each validation failure

## Usage Example

```php
// Adding a new validation rule is now trivial
class ConfigFileExistsSpecification implements CommandValidationSpecification
{
    public function isSatisfiedBy(CommandContext $context): bool
    {
        $configFile = $context->getConfigFile();
        return $configFile === null || file_exists($configFile);
    }

    public function getErrorMessage(): string
    {
        return sprintf('Configuration file not found: %s', $context->getConfigFile());
    }
}

// Just add it to the composite specification
$this->validationSpecification = new CompositeValidationSpecification([
    new CoverageFormatExclusivitySpecification(),
    new CoverageFileExistsSpecification(),
    new CoverageFormatSupportedSpecification(),
    new ReportOptionsCompleteSpecification(),
    new ConfigFileExistsSpecification(), // <- New validation rule
]);
```

## Testing

The implementation includes comprehensive unit tests demonstrating:
- Individual specification validation
- Composite specification behavior
- Error message generation
- Context object functionality

Run tests with:
```bash
phpunit tests/Command/ChurnSpecifications/ChurnSpecificationPatternTest.php
```

## Conclusion

The Specification pattern implementation successfully reduces conditional complexity while improving code maintainability, testability, and extensibility. The refactored code is cleaner, more focused, and easier to extend with new validation rules.
