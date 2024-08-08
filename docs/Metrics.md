# Metrics Collected

- **`line_count`**: 
  - **Description**: The total number of lines of code within the method.
  - **Purpose**: Measures the size of the method in terms of lines of code, which can give an indication of its complexity and readability.

- **`arg_count`**: 
  - **Description**: The number of arguments or parameters that the method takes.
  - **Purpose**: High argument counts can indicate that a method is doing too much or is highly dependent on external inputs, which may be a sign that the method needs refactoring.

- **`return_count`**: 
  - **Description**: The number of `return` statements within the method.
  - **Purpose**: Multiple return points can make a method harder to follow and maintain. This metric helps identify methods that may benefit from being refactored for clarity.

- **`variable_count`**: 
  - **Description**: The number of variables used within the method, including both local variables and parameters.
  - **Purpose**: A high variable count can indicate a method that is doing too much or is overly complex. Monitoring this metric helps to ensure methods remain simple and focused.

- **`property_call_count`**: 
  - **Description**: The number of times a property of a class is accessed within the method.
  - **Purpose**: Frequent property accesses may indicate a method that is highly coupled to the internal state of the object, which can make the code harder to test and maintain.

- **`if_nesting_level`**: 
  - **Description**: The maximum depth of nested `if` statements within the method.
  - **Purpose**: Deeply nested `if` statements can be difficult to follow and may indicate a need for refactoring into smaller, more manageable pieces.

- **`else_count`**: 
  - **Description**: The total number of `else` statements within the method.
  - **Purpose**: Helps measure the complexity of branching logic. A high `else_count` might indicate complex or tightly coupled conditional logic.

- **`if_count`**: 
  - **Description**: The total number of `if` statements within the method.
  - **Purpose**: Like `else_count`, this measures the complexity of branching logic. A high `if_count` can make code harder to understand and maintain.
