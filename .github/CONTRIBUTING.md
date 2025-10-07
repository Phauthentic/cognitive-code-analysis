# Contributing to Cognitive Code Analysis

Thank you for your interest in contributing to this project! 💙
We welcome all improvements, whether they're bug fixes, new features, documentation updates, or test enhancements.

---

## 🧭 Contribution Guidelines

### 1. Fork and Branch

Fork the repository and create a new branch for your work:

```bash
git checkout -b feature/your-feature-name
```

### 2. Write Clear Commits

- Use clear, atomic commits that explain **what** and **why**, not just how
- Follow [Conventional Commits](https://www.conventionalcommits.org/) when possible
- Example: `feat: add code coverage display to cognitive metrics command`

### 3. Include Tests

**All Pull Requests must include tests.**

- **New features**: Must include unit or integration tests
- **Bug fixes**: Must include regression tests reproducing the original issue
- **Refactoring**: Ensure existing tests pass and add new tests if coverage gaps exist

If you're fixing an open issue, reference it in your PR description and ensure the issue scenario is covered by a test.

### 4. Code Quality Standards

Run these checks before submitting your PR:

```bash
# PHPStan - Static Analysis (must pass with no errors)
./bin/phpstan analyse

# PHPMD - Code Quality Analysis
composer phpmd

# PHPUnit - All tests must pass
./bin/phpunit

# Code Coverage (optional but encouraged)
XDEBUG_MODE=coverage ./bin/phpunit --coverage-clover=coverage-clover.xml
```

**Code Style:**
- Follow PSR-12 coding standards
- Use strict types: `declare(strict_types=1);`
- Add type hints to all parameters and return types
- Use PHPDoc blocks for complex array types and additional context

### 5. Documentation

Update documentation when relevant:

- Update `README.md` for user-facing feature changes
- Add or update PHPDoc blocks for new classes and methods
- Include inline comments when logic isn't immediately obvious
- Update configuration examples if you add new options

---

## ✅ Pull Request Checklist

Before submitting your PR, please verify:

- [ ] Code follows PSR-12 and project conventions
- [ ] All tests pass (`./bin/phpunit`)
- [ ] PHPStan analysis passes with no errors (`./bin/phpstan analyse`)
- [ ] PHPMD violations are addressed or justified
- [ ] New features include tests
- [ ] Bug fixes include regression tests
- [ ] Documentation is updated (if applicable)
- [ ] PR description clearly explains the motivation and context
- [ ] Related issues are referenced (e.g., "Fixes #123")

---

## 🐛 Reporting Issues

When reporting bugs, please include:

1. **PHP version** and environment details
2. **Steps to reproduce** the issue
3. **Expected behavior** vs actual behavior
4. **Code samples** or configuration files (if relevant)
5. **Error messages** or stack traces

---

## 💬 Communication

For larger changes or new features:

1. **Open an issue first** to discuss the approach
2. Wait for maintainer feedback before investing significant time
3. We're happy to review proposals and provide guidance early!

---

## 🧪 Testing Philosophy

This project values:

- **High test coverage** - Aim for >80% code coverage
- **Fast tests** - Unit tests should run in milliseconds
- **Reliable tests** - Tests must be deterministic and not flaky
- **Clear test names** - Use descriptive method names that explain what's being tested

---

## 📜 Code of Conduct

- Be respectful and constructive in all interactions
- Focus on the code and ideas, not the person
- Welcome newcomers and help them learn
- Assume good intentions

---

## ❤️ Recognition

We deeply appreciate everyone who contributes to this project.
All contributors are recognized in our release notes and commit history.

**Note:** PRs without accompanying tests will not be merged unless explicitly discussed and justified with maintainers.

---

## 📞 Need Help?

- Open a [GitHub Discussion](../../discussions) for questions
- Check existing [Issues](../../issues) for similar problems
- Tag maintainers in your PR if you need guidance

Thank you for making this project better! 🚀
