.DEFAULT_GOAL := help

help:
	@echo "Available commands:"
	@echo "  - run-tests:          Run tests"
	@echo "  - run-infection:      Runs Infection mutation testing"
	@echo "  - coverage-text:      Runs coverage text"
	@echo "  - coverage-html:      Runs coverage html"
	@echo "  - all:                Runs CS-Fixer, CS-Checker, Static Analyser and Tests"
	@echo "  - shell:              Run shell"
	@echo "  - self-test:          Run cognitive analyses self-test"
	@echo "  - self-test-halstead: Run cognitive analyses self-test"
	@echo "  - build-phar:         Build phar"
	@echo "  - benchmark-memory:   Measure time and peak memory of analyse (BENCHMARK_PATH=vendor)"

run-tests:
	@echo "Running tests"
	docker compose run php composer test

run-infection:
	@echo "Running infection mutation testing"
	docker compose run php composer infection

coverage-text:
	@echo "Running coverage text"
	docker compose run php composer test-coverage

coverage-html:
	@echo "Running coverage text"
	docker compose run php composer test-coverage-html

all:
	@echo "Running CS-Checker, Static Analyser and Tests"
	docker compose run -T php composer all

shell:
	@echo "Running shell"
	docker compose run --service-ports --entrypoint /bin/bash php

self-test:
	@echo "Running cognitive analyses self-test"
	docker compose run php bin/phpcca analyse src

build-phar:
	@echo "Building phar"
	docker compose run php composer build-phar

BENCHMARK_PATH ?= vendor
BENCHMARK_CONFIG ?= benchmarks/phpcca-no-cache.yaml

benchmark-memory:
	@echo "Measuring time and peak memory of analyse on $(BENCHMARK_PATH)"
	docker compose run --rm -T -e XDEBUG_MODE=off php php -d memory_limit=-1 benchmarks/analyse-memory.php $(BENCHMARK_PATH) --config=$(BENCHMARK_CONFIG) --report-type=json --report-file=tmp/benchmark-report.json
