# Cognitive Complexity Analysis

Cognitive Code Analysis is an approach to understanding and improving code by focusing on how human cognition interacts with code. It emphasizes making code more readable, understandable, and maintainable by considering the cognitive processes of the developers who write and work with the code.

> Human short-term or working memory was estimated to be limited to 7 ± 2 variables in the 1950s. A more current estimate is 4 ± 1 constructs (18). Decision quality generally becomes degraded once this limit of four constructs is exceeded.

## Running it

```bash
php analyse.php metrics:parse <path-to-folder>
```

## Why bother?

Easy to read and understand code has a huge impact on the business. It can reduce the time it takes to onboard new developers, make it easier to maintain and extend the codebase, and reduce the likelihood of bugs and errors.

* **Affordability**: Developers spend **8 to 10 times** more time reading code than writing it. This means that the majority of a developer's time is spent trying to understand and reason about code. By focusing on cognitive complexity, we can make this process easier and more efficient.
* **Security**: Code that is hard to understand is more likely to contain bugs and errors. By reducing cognitive complexity, we can make code more reliable and easier to test.
* **Learnability**: New developers often struggle to understand complex codebases. By reducing cognitive complexity, we can make it easier for new developers to get up to speed and start contributing quickly.

## What is the difference to Cyclomatic Complexity?

**Cyclomatic complexity**, introduced by Thomas McCabe in 1976, measures the number of linearly independent paths through a program. It's based on the control flow graph of the code, where each decision point (like if, while, for, etc.) increases the complexity.

**Cognitive complexity**, is a more recent metric designed to better align with how human brains process code. It penalizes structures that are difficult for humans to understand, focusing on the mental effort required to read and understand code.

**Cognitive complexity measures how hard it is for a human to understand the code, while cyclomatic complexity measures how hard your code is to test.**

## How is Cognitive Complexity calculated?

A score is calculated that increases logarithmically as the input value exceeds a specified threshold. The result is 0 when the value is less than or equal to the threshold. When the value is greater than the threshold, the weight is calculated using a logarithmic function that controls the rate of increase.

Given a value of 75 that is greater than the threshold of 50, the function calculates the logarithmic weight as log(1 + (75 - 50) / 10, M_E) which results in approximately 2.302585.

This calculation is done for each metric and the results are summed to get the final cognitive complexity score for a method.

## Result Interpretation

Interpreting the results of code complexity metrics like cognitive complexity, cyclomatic complexity, and other traditional measures requires understanding what each metric tells you about the code and how to use that information effectively.

Also consider that something that you perceive as easy or understandable might not be the same for someone else.

## Resources

These pages and papers provide more information on cognitive limitations.

* [Cognitive complexity Wikipedia](https://en.wikipedia.org/wiki/Cognitive_complexity)
* [Cognitive Complexity and Its Effect on the Code](https://www.baeldung.com/java-cognitive-complexity) by Emanuel Trandafir.
* [Human Cognitive Limitations. Broad, Consistent, Clinical Application of Physiological Principles Will Require Decision Support](https://www.ncbi.nlm.nih.gov/pmc/articles/PMC5822395/) by Alan H. Morris.
* [Neural substrates of cognitive capacity limitations](https://www.ncbi.nlm.nih.gov/pmc/articles/PMC3131328/) by Timothy J. Buschman,a,1 Markus Siegel,a,b Jefferson E. Roy,a and Earl K. Millera.

## Work in progress

* The calculation of the cognitive complexity can be influenced by editing the WeightCalculator class until a proper configuration is implemented.

## Examples

### Wordpress WP_Debug_Data

```txt
Class: \WP_Debug_Data
┌───────────────────┬──────────────┬─────────────┬───────────┬─────────────┬─────────────────────┬────────────┬──────────────────┬────────────┬──────────────────────┐
│ Method Name       │ # Lines      │ # Arguments │ # Returns │ # Variables │ # Property Accesses │ # If       │ If Nesting Level │ # Else     │ Cognitive Complexity │
├───────────────────┼──────────────┼─────────────┼───────────┼─────────────┼─────────────────────┼────────────┼──────────────────┼────────────┼──────────────────────┤
│ check_for_updates │ 5 (0)        │ 0 (0)       │ 0 (0)     │ 0 (0)       │ 0 (0)               │ 0 (0)      │ 0 (0)            │ 0 (0)      │ 0                    │
│ debug_data        │ 1261 (6.399) │ 0 (0)       │ 1 (0)     │ 105 (3.073) │ 20 (1.03)           │ 58 (4.025) │ 3 (1.099)        │ 33 (3.497) │ 19.122               │
│ get_wp_constants  │ 143 (3.75)   │ 0 (0)       │ 1 (0)     │ 9 (0.875)   │ 0 (0)               │ 5 (1.099)  │ 1 (0)            │ 5 (1.609)  │ 7.333                │
│ get_wp_filesystem │ 59 (0)       │ 0 (0)       │ 1 (0)     │ 9 (0.875)   │ 0 (0)               │ 1 (0)      │ 1 (0)            │ 0 (0)      │ 0.875                │
│ get_mysql_var     │ 14 (0)       │ 1 (0)       │ 2 (0)     │ 2 (0)       │ 0 (0)               │ 1 (0)      │ 1 (0)            │ 0 (0)      │ 0                    │
│ format            │ 59 (0)       │ 2 (0)       │ 1 (0)     │ 11 (1.03)   │ 0 (0)               │ 5 (1.099)  │ 1 (0)            │ 5 (1.609)  │ 3.738                │
│ get_database_size │ 13 (0)       │ 0 (0)       │ 1 (0)     │ 4 (0.336)   │ 1 (0)               │ 1 (0)      │ 1 (0)            │ 0 (0)      │ 0.336                │
│ get_sizes         │ 124 (3.497)  │ 0 (0)       │ 1 (0)     │ 14 (1.224)  │ 0 (0)               │ 9 (1.946)  │ 2 (0.693)        │ 5 (1.609)  │ 8.969                │
└───────────────────┴──────────────┴─────────────┴───────────┴─────────────┴─────────────────────┴────────────┴──────────────────┴────────────┴──────────────────────┘
```

### Doctrine Paginator

```txt
Class: Doctrine\ORM\Tools\Pagination\Paginator
┌───────────────────────────────────────────┬─────────┬─────────────┬───────────┬─────────────┬─────────────────────┬───────┬──────────────────┬───────────┬──────────────────────┐
│ Method Name                               │ # Lines │ # Arguments │ # Returns │ # Variables │ # Property Accesses │ # If  │ If Nesting Level │ # Else    │ Cognitive Complexity │
├───────────────────────────────────────────┼─────────┼─────────────┼───────────┼─────────────┼─────────────────────┼───────┼──────────────────┼───────────┼──────────────────────┤
│ __construct                               │ 10 (0)  │ 2 (0)       │ 0 (0)     │ 1 (0)       │ 1 (0)               │ 1 (0) │ 1 (0)            │ 0 (0)     │ 0                    │
│ getQuery                                  │ 4 (0)   │ 0 (0)       │ 1 (0)     │ 1 (0)       │ 1 (0)               │ 0 (0) │ 0 (0)            │ 0 (0)     │ 0                    │
│ getFetchJoinCollection                    │ 4 (0)   │ 0 (0)       │ 1 (0)     │ 1 (0)       │ 1 (0)               │ 0 (0) │ 0 (0)            │ 0 (0)     │ 0                    │
│ getUseOutputWalkers                       │ 4 (0)   │ 0 (0)       │ 1 (0)     │ 1 (0)       │ 1 (0)               │ 0 (0) │ 0 (0)            │ 0 (0)     │ 0                    │
│ setUseOutputWalkers                       │ 6 (0)   │ 1 (0)       │ 1 (0)     │ 1 (0)       │ 1 (0)               │ 0 (0) │ 0 (0)            │ 0 (0)     │ 0                    │
│ count                                     │ 12 (0)  │ 0 (0)       │ 1 (0)     │ 1 (0)       │ 1 (0)               │ 1 (0) │ 1 (0)            │ 0 (0)     │ 0                    │
│ getIterator                               │ 46 (0)  │ 0 (0)       │ 2 (0)     │ 9 (0.875)   │ 2 (0)               │ 3 (0) │ 2 (0.693)        │ 2 (0.693) │ 2.262                │
│ cloneQuery                                │ 13 (0)  │ 1 (0)       │ 1 (0)     │ 3 (0.182)   │ 0 (0)               │ 0 (0) │ 0 (0)            │ 0 (0)     │ 0.182                │
│ useOutputWalker                           │ 8 (0)   │ 1 (0)       │ 2 (0)     │ 1 (0)       │ 1 (0)               │ 1 (0) │ 1 (0)            │ 0 (0)     │ 0                    │
│ appendTreeWalker                          │ 11 (0)  │ 2 (0)       │ 0 (0)     │ 1 (0)       │ 0 (0)               │ 1 (0) │ 1 (0)            │ 0 (0)     │ 0                    │
│ getCountQuery                             │ 25 (0)  │ 0 (0)       │ 1 (0)     │ 4 (0.336)   │ 1 (0)               │ 2 (0) │ 1 (0)            │ 1 (0)     │ 0.336                │
│ unbindUnusedQueryParams                   │ 17 (0)  │ 1 (0)       │ 0 (0)     │ 6 (0.588)   │ 0 (0)               │ 1 (0) │ 1 (0)            │ 0 (0)     │ 0.588                │
│ convertWhereInIdentifiersToDatabaseValues │ 11 (0)  │ 1 (0)       │ 1 (0)     │ 5 (0.47)    │ 1 (0)               │ 0 (0) │ 0 (0)            │ 0 (0)     │ 0.47                 │
└───────────────────────────────────────────┴─────────┴─────────────┴───────────┴─────────────┴─────────────────────┴───────┴──────────────────┴───────────┴──────────────────────┘
```

## License

Copyright Florian Krämer

Licensed under the [GPL3 license](LICENSE).
