# Benchmarks

## Time and peak memory of a full analysis

`analyse-memory.php` runs the `analyse` command in-process and prints the wall time and the peak memory at the end.
Run it in the Docker container with Xdebug off and no memory limit:

```bash
make benchmark-memory
```

This analyses `vendor/` (pinned by `composer.lock`) with the cache disabled and writes a JSON report to `tmp/benchmark-report.json`.
Use `BENCHMARK_PATH` to analyse another directory and `BENCHMARK_CONFIG` to use another config file:

```bash
make benchmark-memory BENCHMARK_PATH=src
make benchmark-memory BENCHMARK_CONFIG=benchmarks/phpcca-cache.yaml
```

`phpcca-cache.yaml` enables the cache in `tmp/benchmark-cache`.
Run it twice to compare a cold run with a warm run, and delete `tmp/benchmark-cache` to start cold again.

You can also call the script directly and pass any `analyse` options:

```bash
docker compose run --rm -T -e XDEBUG_MODE=off php \
    php -d memory_limit=-1 benchmarks/analyse-memory.php vendor \
    --config=benchmarks/phpcca-no-cache.yaml --report-type=html --report-file=tmp/report.html
```

To check that a change does not alter the results, write the same report before and after the change and compare the files with `cmp`.

## Parser micro benchmarks

`ParserBench.php` uses [phpbench](https://phpbench.readthedocs.io/) and the configuration in `phpbench.json`.
