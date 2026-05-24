# CI Integration

Cognitive Code Analysis is designed to run in continuous integration pipelines. You can analyse only the PHP files changed in a pull or merge request, publish the results as a comment, and upload reports as build artifacts.

This guide includes workflow examples originally shared in [GitHub issue #29](https://github.com/Phauthentic/cognitive-code-analysis/issues/29).

## Overview

A typical CI integration follows these steps:

1. Check out the repository with full git history (`fetch-depth: 0`)
2. Determine which PHP files changed compared to the target branch
3. Run `bin/phpcca analyse` on those files only
4. Publish the report (Markdown comment, SARIF upload, or quality gate)

When `cca.yaml` exists in the project root, `analyse` loads it automatically. Use `--config=path/to/config.yaml` to override.

## Report formats for CI

| Format | `--report-type` | Use case |
|--------|-----------------|----------|
| Markdown | `markdown` | Human-readable PR/MR comments |
| GitHub Actions | `github-actions` | Inline annotations in Actions logs |
| SARIF | `sarif` | GitHub Code Scanning |
| GitLab Code Quality | `gitlab-codequality` | GitLab merge request widget |
| Checkstyle XML | `checkstyle` | Jenkins, Maven Checkstyle Plugin |
| JUnit XML | `junit` | Jenkins JUnit plugin, build failure gates |

Example:

```bash
bin/phpcca analyse src/ChangedFile.php --report-type=sarif --report-file=results.sarif
```

See [Baseline Analysis](./Baseline-Analysis.md) to compare metrics against a previous run using `--baseline` or `--generate-baseline`.

## GitHub Actions

The workflow below runs on pull requests, analyses changed PHP files, posts a Markdown report as a PR comment, and uploads the report as an artifact.

Add `--config=cca.yaml` if your config file is not named `cca.yaml` or not in the working directory.

```yaml
name: Code Metrics

on:
  pull_request:
    paths:
      - '**/*.php'
    branches:
      - '*'

permissions:
  pull-requests: write
  contents: read

jobs:
  code-metrics:
    name: Cognitive Code Analysis
    runs-on: ubuntu-24.04
    if: github.event_name == 'pull_request'

    steps:
      - name: Checkout code
        uses: actions/checkout@v4
        with:
          fetch-depth: 0

      - name: Fetch base branch
        run: |
          git fetch origin ${{ github.base_ref }}:${{ github.base_ref }}

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          extensions: json, fileinfo
          tools: composer

      - name: Install dependencies
        run: composer install --prefer-dist --no-ansi --no-interaction --no-progress --no-scripts

      - name: Analyze changed PHP files
        id: analyze
        run: |
          BASE_SHA="${{ github.event.pull_request.base.sha }}"
          HEAD_SHA="${{ github.sha }}"

          CHANGED_FILES=$(git diff --name-only --diff-filter=ACMR $BASE_SHA...$HEAD_SHA | grep '\.php$' | tr '\n' ' ' || echo "")

          if [ -n "$CHANGED_FILES" ]; then
            ANALYSE_PATH=$(echo "$CHANGED_FILES" | tr ' ' ',')
            echo "Analyzing files: $CHANGED_FILES"
            bin/phpcca analyse "$ANALYSE_PATH" --report-type=markdown --report-file=cca-report.md || true

            if [ -f "cca-report.md" ] && [ -s "cca-report.md" ]; then
              echo "has_report=true" >> $GITHUB_OUTPUT
              echo "Report generated successfully"
            else
              echo "has_report=false" >> $GITHUB_OUTPUT
              echo "No report generated"
            fi
          else
            echo "has_report=false" >> $GITHUB_OUTPUT
            echo "No PHP files changed, skipping analysis"
          fi

      - name: Post comment to PR
        if: steps.analyze.outputs.has_report == 'true'
        uses: actions/github-script@v7
        with:
          script: |
            const fs = require('fs');
            const report = fs.readFileSync('cca-report.md', 'utf8');

            await github.rest.issues.createComment({
              owner: context.repo.owner,
              repo: context.repo.repo,
              issue_number: context.issue.number,
              body: report
            });

      - name: Upload report artifact
        if: always()
        uses: actions/upload-artifact@v4
        with:
          name: cca-report
          path: cca-report.md
          if-no-files-found: ignore
```

### GitHub Code Scanning with SARIF

Replace the Markdown report step with:

```bash
ANALYSE_PATH=$(echo "$CHANGED_FILES" | tr ' ' ',')
bin/phpcca analyse "$ANALYSE_PATH" --report-type=sarif --report-file=results.sarif
```

Then upload `results.sarif` using the [GitHub Code Scanning upload action](https://github.com/github/codeql-action).

## GitLab CI

The job below runs on merge requests, analyses changed PHP files, posts a Markdown report as an MR note, and stores the report as an artifact.

Use `--config=cca.yaml` when your config file is not auto-discovered from the project root.

```yaml
Code-Metrics:
  interruptible: true
  extends: .setup_composer_install
  image: registry.gitlab.com/clipmyhorsetv/infrastructure/php_docker_images:8.4-nginx-php-dev
  stage: tests
  dependencies:
    - PHP-Unit
  variables:
    GIT_DEPTH: 0
  before_script:
    - git config --global --add safe.directory $CI_PROJECT_DIR
    - git fetch origin $CI_MERGE_REQUEST_TARGET_BRANCH_NAME:$CI_MERGE_REQUEST_TARGET_BRANCH_NAME
  script:
    - composer install --prefer-dist --no-ansi --no-interaction --no-progress --no-scripts
    - |
      if [ "$CI_PIPELINE_SOURCE" = "merge_request_event" ] && [ -n "$CI_MERGE_REQUEST_DIFF_BASE_SHA" ]; then
        CHANGED_FILES=$(git diff --name-only --diff-filter=ACMR $CI_MERGE_REQUEST_DIFF_BASE_SHA...$CI_COMMIT_SHA | grep '\.php$' | tr '\n' ' ')
      else
        CHANGED_FILES=$(find src/ -name "*.php" | tr '\n' ' ')
      fi
      if [ -n "$CHANGED_FILES" ]; then
        ANALYSE_PATH=$(echo "$CHANGED_FILES" | tr ' ' ',')
        bin/phpcca analyse "$ANALYSE_PATH" --report-type=markdown --report-file=cca-report.md --config=cca.yaml
        if [ -f "cca-report.md" ] && [ -s "cca-report.md" ]; then
          # Try with CI_JOB_TOKEN first, fallback to CI/CD variables
          if [ -n "$VALIDATOR" ]; then
            TOKEN="$VALIDATOR"
          else
            TOKEN="$CI_JOB_TOKEN"
          fi

          echo "Posting comment to merge request $CI_MERGE_REQUEST_IID..."
          echo "Report content preview:"
          head -5 cca-report.md

          # Use POST with body as query parameter as per GitLab API documentation
          RESPONSE=$(curl -s -w "\n%{http_code}" --request POST --header "PRIVATE-TOKEN: $TOKEN" \
            --data-urlencode "body=$(cat cca-report.md)" \
            "https://gitlab.com/api/v4/projects/$CI_PROJECT_ID/merge_requests/$CI_MERGE_REQUEST_IID/notes")

          HTTP_CODE=$(echo "$RESPONSE" | tail -1)
          RESPONSE_BODY=$(echo "$RESPONSE" | head -n -1)

          echo "HTTP Response Code: $HTTP_CODE"
          echo "Response Body: $RESPONSE_BODY"

          if [ "$HTTP_CODE" = "201" ]; then
            echo "Comment posted successfully"
          else
            echo "Failed to post comment. HTTP Code: $HTTP_CODE"
          fi
        fi
      else
        echo "No PHP files changed, skipping analysis"
        echo "[]" > cca-report.md
      fi
  rules:
    - if: $CI_PIPELINE_SOURCE == "merge_request_event"
      changes:
        compare_to: "refs/heads/$CI_DEFAULT_BRANCH"
        paths:
          - '**/*.php'
    - when: never
  artifacts:
    when: always
    paths:
      - cca-report.md
```

### GitLab Code Quality widget

Replace the Markdown report with:

```bash
ANALYSE_PATH=$(echo "$CHANGED_FILES" | tr ' ' ',')
bin/phpcca analyse "$ANALYSE_PATH" --report-type=gitlab-codequality --report-file=gl-code-quality.json
```

GitLab picks up the Code Quality report automatically when configured in your pipeline.

## Tips

- **Analyse only changed files** in PR/MR pipelines to keep feedback fast and relevant.
- **Use baselines** to show deltas between runs; see [Baseline Analysis](./Baseline-Analysis.md).
- **Disable cache in CI** if you want a fresh analysis every run:
  ```yaml
  cognitive:
    cache:
      enabled: false
  ```
- **Fail the build on complexity regressions** using `--report-type=junit` or `--report-type=checkstyle` with your CI platform's quality gate support.

## Related

- [Configuration](./Configuration.md) — project setup with `bin/phpcca init` and `cca.yaml`
- [Baseline Analysis](./Baseline-Analysis.md) — track complexity changes over time
- [Creating Custom Reporters](./Creating-Custom-Reporters.md) — extend report output for custom CI integrations
- [Issue #29](https://github.com/Phauthentic/cognitive-code-analysis/issues/29) — original feature discussion for GitHub Actions and branch comparison
