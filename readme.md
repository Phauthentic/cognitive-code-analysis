# Cognitive Complexity Analysis

Cognitive Code Analysis is an approach to understanding and improving code by focusing on how human cognition interacts with code. It emphasizes making code more readable, understandable, and maintainable by considering the cognitive processes of the developers who write and work with the code.

> "Human short-term or working memory was estimated to be limited to 7 ± 2 variables in the 1950s. A more current estimate is 4 ± 1 constructs. Decision quality generally becomes degraded once this limit of four constructs is exceeded."

[Source: Human Cognitive Limitations. Broad, Consistent, Clinical Application of Physiological Principles Will Require Decision Support](https://www.ncbi.nlm.nih.gov/pmc/articles/PMC5822395/)

## Running it

Cognitive Complexity Analysis

```bash
bin/phpcca analyse <path-to-folder>
```

Generate a report, supported types are `json`, `csv`, `html`.

```bash
bin/phpcca analyse <path-to-folder> --report-type json --report-file cognitive.json
```

You can also pass a baseline file to compare the results to. The JSON report is used as baseline. The output will now show a delta if a value was changed.

```bash
bin/phpcca analyse <path-to-folder> --baseline cognitive.json
```

### Finding Hotspots

Churn is a measure of how much code has changed over time. It helps to find the most changed and complex areas in your codebase, which are often the most error-prone and difficult to maintain.

```bash
bin/phpcca churn <path-to-folder>
```

## Documentation

* [Cognitive Complexity Analysis](./docs/Cognitive-Complexity-Analysis.md#cognitive-complexity-analysis)
  * [Why bother?](./docs/Cognitive-Complexity-Analysis.md#why-bother)
  * [What is the difference to Cyclomatic Complexity?](./docs/Cognitive-Complexity-Analysis.md#what-is-the-difference-to-cyclomatic-complexity)
  * [How is Cognitive Complexity calculated?](./docs/Cognitive-Complexity-Analysis.md#how-is-cognitive-complexity-calculated)
  * [Metrics Collected](./docs/Cognitive-Complexity-Analysis.md#metrics-collected)
  * [Result Interpretation](./docs/Cognitive-Complexity-Analysis.md#result-interpretation)
  * [Churn - Finding Hotspots](./docs/Churn-Finding-Hotspots.md)
  * [Configuration](./docs/Configuration.md#configuration)
    * [Tuning the calculation](./docs/Configuration.md#tuning-the-calculation) 
  * [Examples](#examples)
    * [Wordpress WP_Debug_Data](#wordpress-wp_debug_data)
    * [Doctrine Paginator](#doctrine-paginator)

## Resources

These pages and papers provide more information on cognitive limitations and readability and the impact on the business.

* **Cognitive Complexity**
  * [Cognitive Complexity Wikipedia](https://en.wikipedia.org/wiki/Cognitive_complexity)
  * [Cognitive Complexity and Its Effect on the Code](https://www.baeldung.com/java-cognitive-complexity) by Emanuel Trandafir.
  * [Human Cognitive Limitations. Broad, Consistent, Clinical Application of Physiological Principles Will Require Decision Support](https://www.ncbi.nlm.nih.gov/pmc/articles/PMC5822395/) by Alan H. Morris.
  * [The Magical Number 4 in Short-Term Memory: A Reconsideration of Mental Storage Capacity](https://www.researchgate.net/publication/11830840_The_Magical_Number_4_in_Short-Term_Memory_A_Reconsideration_of_Mental_Storage_Capacity) by Nelson Cowan
  * [Neural substrates of cognitive capacity limitations](https://www.ncbi.nlm.nih.gov/pmc/articles/PMC3131328/) by Timothy J. Buschman,a,1 Markus Siegel,a,b Jefferson E. Roy, and Earl K. Millera.
  * [Code Readability Testing, an Empirical Study](https://www.researchgate.net/publication/299412540_Code_Readability_Testing_an_Empirical_Study) by Todd Sedano.
  * [An Empirical Validation of Cognitive Complexity as a Measure of Source Code Understandability](https://arxiv.org/pdf/2007.12520) by Marvin Muñoz Barón, Marvin Wyrich, and Stefan Wagner.
* **Halstead Complexity**
  * [Halstead Complexity Measures](https://en.wikipedia.org/wiki/Halstead_complexity_measures)

## Examples

### Cognitive Metrics

#### Wordpress WP_Debug_Data

```txt
Class: \WP_Debug_Data
┌───────────────────┬──────────────┬───────────┬─────────┬─────────────┬────────────┬────────────┬────────────┬────────────┬────────────┐
│ Method Name       │ Lines        │ Arguments │ Returns │ Variables   │ Property   │ If         │ If Nesting │ Else       │ Cognitive  │
│                   │              │           │         │             │ Accesses   │            │ Level      │            │ Complexity │
├───────────────────┼──────────────┼───────────┼─────────┼─────────────┼────────────┼────────────┼────────────┼────────────┼────────────┤
│ check_for_updates │ 6 (0)        │ 0 (0)     │ 0 (0)   │ 0 (0)       │ 0 (0)      │ 0 (0)      │ 0 (0)      │ 0 (0)      │ 0          │
│ debug_data        │ 1230 (6.373) │ 0 (0)     │ 1 (0)   │ 105 (3.073) │ 20 (0.788) │ 58 (4.025) │ 3 (1.099)  │ 33 (3.497) │ 18.855     │
│ get_wp_constants  │ 144 (3.761)  │ 0 (0)     │ 1 (0)   │ 9 (0.875)   │ 0 (0)      │ 5 (1.099)  │ 1 (0)      │ 5 (1.609)  │ 7.345      │
│ get_wp_filesystem │ 60 (0)       │ 0 (0)     │ 1 (0)   │ 9 (0.875)   │ 0 (0)      │ 1 (0)      │ 1 (0)      │ 0 (0)      │ 0.875      │
│ get_mysql_var     │ 15 (0)       │ 1 (0)     │ 2 (0)   │ 2 (0)       │ 0 (0)      │ 1 (0)      │ 1 (0)      │ 0 (0)      │ 0          │
│ format            │ 60 (0)       │ 2 (0)     │ 1 (0)   │ 11 (1.03)   │ 0 (0)      │ 5 (1.099)  │ 1 (0)      │ 5 (1.609)  │ 3.738      │
│ get_database_size │ 14 (0)       │ 0 (0)     │ 1 (0)   │ 4 (0.336)   │ 1 (0)      │ 1 (0)      │ 1 (0)      │ 0 (0)      │ 0.336      │
│ get_sizes         │ 125 (3.512)  │ 0 (0)     │ 1 (0)   │ 14 (1.224)  │ 0 (0)      │ 9 (1.946)  │ 2 (0.693)  │ 5 (1.609)  │ 8.984      │
└───────────────────┴──────────────┴───────────┴─────────┴─────────────┴────────────┴────────────┴────────────┴────────────┴────────────┘
```

#### Doctrine Paginator

```txt
Class: Doctrine\ORM\Tools\Pagination\Paginator
┌───────────────────────────────────────────┬────────┬───────────┬─────────┬───────────┬──────────┬───────┬────────────┬───────────┬────────────┐
│ Method Name                               │ Lines  │ Arguments │ Returns │ Variables │ Property │ If    │ If Nesting │ Else      │ Cognitive  │
│                                           │        │           │         │           │ Accesses │       │ Level      │           │ Complexity │
├───────────────────────────────────────────┼────────┼───────────┼─────────┼───────────┼──────────┼───────┼────────────┼───────────┼────────────┤
│ __construct                               │ 10 (0) │ 2 (0)     │ 0 (0)   │ 1 (0)     │ 1 (0)    │ 1 (0) │ 1 (0)      │ 0 (0)     │ 0          │
│ getQuery                                  │ 4 (0)  │ 0 (0)     │ 1 (0)   │ 1 (0)     │ 1 (0)    │ 0 (0) │ 0 (0)      │ 0 (0)     │ 0          │
│ getFetchJoinCollection                    │ 4 (0)  │ 0 (0)     │ 1 (0)   │ 1 (0)     │ 1 (0)    │ 0 (0) │ 0 (0)      │ 0 (0)     │ 0          │
│ getUseOutputWalkers                       │ 4 (0)  │ 0 (0)     │ 1 (0)   │ 1 (0)     │ 1 (0)    │ 0 (0) │ 0 (0)      │ 0 (0)     │ 0          │
│ setUseOutputWalkers                       │ 6 (0)  │ 1 (0)     │ 1 (0)   │ 1 (0)     │ 1 (0)    │ 0 (0) │ 0 (0)      │ 0 (0)     │ 0          │
│ count                                     │ 12 (0) │ 0 (0)     │ 1 (0)   │ 1 (0)     │ 1 (0)    │ 1 (0) │ 1 (0)      │ 0 (0)     │ 0          │
│ getIterator                               │ 46 (0) │ 0 (0)     │ 2 (0)   │ 9 (0.875) │ 2 (0)    │ 3 (0) │ 2 (0.693)  │ 2 (0.693) │ 2.262      │
│ cloneQuery                                │ 13 (0) │ 1 (0)     │ 1 (0)   │ 3 (0.182) │ 0 (0)    │ 0 (0) │ 0 (0)      │ 0 (0)     │ 0.182      │
│ useOutputWalker                           │ 8 (0)  │ 1 (0)     │ 2 (0)   │ 1 (0)     │ 1 (0)    │ 1 (0) │ 1 (0)      │ 0 (0)     │ 0          │
│ appendTreeWalker                          │ 11 (0) │ 2 (0)     │ 0 (0)   │ 1 (0)     │ 0 (0)    │ 1 (0) │ 1 (0)      │ 0 (0)     │ 0          │
│ getCountQuery                             │ 25 (0) │ 0 (0)     │ 1 (0)   │ 4 (0.336) │ 1 (0)    │ 2 (0) │ 1 (0)      │ 1 (0)     │ 0.336      │
│ unbindUnusedQueryParams                   │ 17 (0) │ 1 (0)     │ 0 (0)   │ 6 (0.588) │ 0 (0)    │ 1 (0) │ 1 (0)      │ 0 (0)     │ 0.588      │
│ convertWhereInIdentifiersToDatabaseValues │ 11 (0) │ 1 (0)     │ 1 (0)   │ 5 (0.47)  │ 1 (0)    │ 0 (0) │ 0 (0)      │ 0 (0)     │ 0.47       │
└───────────────────────────────────────────┴────────┴───────────┴─────────┴───────────┴──────────┴───────┴────────────┴───────────┴────────────┘
```

## License

Copyright Florian Krämer

Licensed under the [GPL3 license](LICENSE).
