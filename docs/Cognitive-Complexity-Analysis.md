# Cognitive Complexity Analysis

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

For each metric, the weight is:

```
weight = 0                                       if value <= threshold
weight = ln(1 + (value - threshold) / scale)     if value >  threshold
```

`ln` is the natural logarithm.
`threshold` and `scale` are configured per metric, see [Tuning the calculation](./Configuration.md#tuning-the-calculation).

Given a value of 75, a threshold of 50, and a scale of 10, the weight is ln(1 + (75 - 50) / 10) = ln(3.5), which is approximately 1.252763.

The weights of all metrics are summed to get the final cognitive complexity score for a method.
The score is rounded to three decimal places.

## Result Interpretation

Interpreting the results of **any** code metrics like cognitive complexity, cyclomatic complexity, efferent and afferent dependencies and other traditional measures requires understanding what each metric tells you about the code and how to use that information effectively. Each of those metrics are *indicators* but not *absolute truths*.

There are cases in which the code simply ends up with a lot of things that might result in a bad score but are necessary and acceptable under certain circumstances. For example a complex calculation or a complex algorithm might have plenty of variables and input arguments. If the problem **can't** be divided (if it can you really should!) into smaller logical units (methods), the score for that method will inevitably be high.

* **Bias** - When it comes to cognition, consider that something that you perceive as easy or understandable, might not be the same for someone else.
* **Constructors** -  Constructors are often more complex than other methods because they have to initialize the object's state. This can lead to higher cognitive complexity scores, which may be acceptable in some cases.
* **Data Structure Building** - Methods that build complex data structures or perform complex calculations may have higher cognitive complexity scores. This is often unavoidable and may be acceptable if the complexity is necessary for the task at hand.

## Metrics Collected

- **Line Count**: 
  - **Description**: The total number of lines of code within the method.
  - **Purpose**: Measures the size of the method in terms of lines of code, which can give an indication of its complexity and readability.

- **Argument Count**: 
  - **Description**: The number of arguments or parameters that the method takes.
  - **Purpose**: High argument counts can indicate that a method is doing too much or is highly dependent on external inputs, which may be a sign that the method needs refactoring.

- **Return Count**: 
  - **Description**: The number of return statements within the method.
  - **Purpose**: Multiple return points can make a method harder to follow and maintain. This metric helps identify methods that may benefit from being refactored for clarity.

- **Variable Count**: 
  - **Description**: The number of distinct variable names used within the method body, excluding the method's parameters. `$this` counts as a variable.
  - **Purpose**: A high variable count can indicate a method that is doing too much or is overly complex. Monitoring this metric helps to ensure methods remain simple and focused.

- **Property Call Count**: 
  - **Description**: The number of distinct property names accessed with `->` within the method. Repeated access to the same property name counts once. Static (`::$property`) and nullsafe (`?->`) property access is not counted.
  - **Purpose**: Frequent property accesses may indicate a method that is highly coupled to the internal state of the object, which can make the code harder to test and maintain.

- **If Nesting Level**: 
  - **Description**: The maximum depth of nested `if` statements within the method.
  - **Purpose**: Deeply nested `if` statements can be difficult to follow and may indicate a need for refactoring into smaller, more manageable pieces.

- **Else Count**: 
  - **Description**: The total number of `else` and `elseif` branches within the method.
  - **Purpose**: Helps measure the complexity of branching logic. A high else count might indicate complex or tightly coupled conditional logic.

- **If Count**: 
  - **Description**: The total number of `if` statements within the method.
  - **Purpose**: Like else count, this measures the complexity of branching logic. A high if count can make code harder to understand and maintain.
