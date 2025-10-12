<?php

declare(strict_types=1);

namespace Phauthentic\CognitiveCodeAnalysis\Business\Cognitive\Exporter;

/**
 * Factory class that builds refactoring suggestions based on metrics.
 *
 * @SuppressWarnings("PHPMD")
 */
class RefactoringSuggestionBuilder
{
    /**
     * Build suggestions for a specific metric.
     *
     * @param string $metric The metric name
     * @param float $value The current metric value
     * @param float $threshold The configured threshold
     * @return array<RefactoringSuggestion>
     */
    public function buildSuggestionsForMetric(string $metric, float $value, float $threshold): array
    {
        if ($value <= $threshold) {
            return [];
        }

        $suggestions = match ($metric) {
            'lineCount' => $this->buildLineCountSuggestions($value, $threshold),
            'argCount' => $this->buildArgCountSuggestions($value, $threshold),
            'returnCount' => $this->buildReturnCountSuggestions($value, $threshold),
            'variableCount' => $this->buildVariableCountSuggestions($value, $threshold),
            'propertyCallCount' => $this->buildPropertyCallCountSuggestions($value, $threshold),
            'ifCount' => $this->buildIfCountSuggestions($value, $threshold),
            'ifNestingLevel' => $this->buildIfNestingLevelSuggestions($value, $threshold),
            'elseCount' => $this->buildElseCountSuggestions($value, $threshold),
            default => [],
        };

        // Sort by priority (highest first)
        usort($suggestions, fn(RefactoringSuggestion $a, RefactoringSuggestion $b) => $b->priority <=> $a->priority);

        return $suggestions;
    }

    /**
     * Build suggestions for cyclomatic complexity.
     *
     * @param int $complexity The cyclomatic complexity value
     * @return array<RefactoringSuggestion>
     */
    public function buildCyclomaticComplexitySuggestions(int $complexity): array
    {
        $suggestions = [];

        if ($complexity >= 15) {
            $suggestions[] = new RefactoringSuggestion(
                'cyclomaticComplexity',
                'Extract Method',
                'Break down complex logic into smaller, focused methods. Each method should have a single responsibility.',
                $this->getExtractMethodExample(),
                5,
                $complexity,
                15
            );

            $suggestions[] = new RefactoringSuggestion(
                'cyclomaticComplexity',
                'Replace Conditional with Polymorphism',
                'Use inheritance and polymorphism to eliminate complex conditional logic.',
                $this->getReplaceConditionalWithPolymorphismExample(),
                5,
                $complexity,
                15
            );
        } elseif ($complexity >= 11) {
            $suggestions[] = new RefactoringSuggestion(
                'cyclomaticComplexity',
                'Decompose Conditional',
                'Break complex boolean expressions into smaller, named methods.',
                $this->getDecomposeConditionalExample(),
                4,
                $complexity,
                10
            );

            $suggestions[] = new RefactoringSuggestion(
                'cyclomaticComplexity',
                'Simplify Boolean Expressions',
                'Use De Morgan\'s laws and extract methods to simplify complex conditions.',
                $this->getSimplifyBooleanExpressionsExample(),
                4,
                $complexity,
                10
            );
        }

        return $suggestions;
    }

    /**
     * Build suggestions for Halstead effort.
     *
     * @param float $effort The Halstead effort value
     * @return array<RefactoringSuggestion>
     */
    public function buildHalsteadEffortSuggestions(float $effort): array
    {
        $suggestions = [];

        if ($effort >= 50000) {
            $suggestions[] = new RefactoringSuggestion(
                'halsteadEffort',
                'Extract Method',
                'Break down the method into smaller, more manageable pieces.',
                $this->getExtractMethodExample(),
                5,
                $effort,
                50000
            );

            $suggestions[] = new RefactoringSuggestion(
                'halsteadEffort',
                'Introduce Explaining Variable',
                'Use intermediate variables with meaningful names to clarify complex expressions.',
                $this->getIntroduceExplainingVariableExample(),
                5,
                $effort,
                50000
            );
        } elseif ($effort >= 10000) {
            $suggestions[] = new RefactoringSuggestion(
                'halsteadEffort',
                'Replace Magic Numbers with Named Constants',
                'Replace numeric literals with named constants to improve readability.',
                $this->getReplaceMagicNumbersExample(),
                4,
                $effort,
                10000
            );

            $suggestions[] = new RefactoringSuggestion(
                'halsteadEffort',
                'Simplify Expressions',
                'Break complex expressions into smaller, more understandable parts.',
                $this->getSimplifyExpressionsExample(),
                4,
                $effort,
                10000
            );
        }

        return $suggestions;
    }

    /**
     * Calculate priority based on how much the threshold is exceeded.
     */
    private function calculatePriority(float $value, float $threshold): int
    {
        if ($threshold <= 0) {
            return 1;
        }

        $ratio = ($value - $threshold) / $threshold;

        return match (true) {
            $ratio > 2.0 => 5,
            $ratio > 1.0 => 4,
            $ratio > 0.5 => 3,
            $ratio > 0.2 => 2,
            default => 1,
        };
    }

    /**
     * @return array<RefactoringSuggestion>
     */
    private function buildLineCountSuggestions(float $value, float $threshold): array
    {
        $priority = $this->calculatePriority($value, $threshold);

        return [
            new RefactoringSuggestion(
                'lineCount',
                'Extract Method',
                'Break the long method into smaller, focused methods. Each method should have a single responsibility.',
                $this->getExtractMethodExample(),
                min($priority, 5),
                $value,
                $threshold
            ),
            new RefactoringSuggestion(
                'lineCount',
                'Decompose Conditional',
                'Extract complex conditional logic into separate methods with descriptive names.',
                $this->getDecomposeConditionalExample(),
                min($priority, 4),
                $value,
                $threshold
            ),
            new RefactoringSuggestion(
                'lineCount',
                'Replace Temp with Query',
                'Replace temporary variables with method calls to reduce method length.',
                $this->getReplaceTempWithQueryExample(),
                min($priority, 3),
                $value,
                $threshold
            ),
        ];
    }

    /**
     * @return array<RefactoringSuggestion>
     */
    private function buildArgCountSuggestions(float $value, float $threshold): array
    {
        $priority = $this->calculatePriority($value, $threshold);

        return [
            new RefactoringSuggestion(
                'argCount',
                'Introduce Parameter Object',
                'Group related parameters into a single data object to reduce parameter count.',
                $this->getIntroduceParameterObjectExample(),
                min($priority, 5),
                $value,
                $threshold
            ),
            new RefactoringSuggestion(
                'argCount',
                'Preserve Whole Object',
                'Pass the entire object instead of extracting individual fields.',
                $this->getPreserveWholeObjectExample(),
                min($priority, 4),
                $value,
                $threshold
            ),
            new RefactoringSuggestion(
                'argCount',
                'Builder Pattern',
                'Use the Builder pattern for objects with many optional parameters.',
                $this->getBuilderPatternExample(),
                min($priority, 3),
                $value,
                $threshold
            ),
        ];
    }

    /**
     * @return array<RefactoringSuggestion>
     */
    private function buildReturnCountSuggestions(float $value, float $threshold): array
    {
        $priority = $this->calculatePriority($value, $threshold);

        return [
            new RefactoringSuggestion(
                'returnCount',
                'Replace Nested Conditionals with Guard Clauses',
                'Use early returns to handle edge cases and reduce nesting.',
                $this->getGuardClausesExample(),
                min($priority, 5),
                $value,
                $threshold
            ),
            new RefactoringSuggestion(
                'returnCount',
                'Consolidate Conditional Expression',
                'Combine related conditional expressions into a single, clearer condition.',
                $this->getConsolidateConditionalExpressionExample(),
                min($priority, 4),
                $value,
                $threshold
            ),
            new RefactoringSuggestion(
                'returnCount',
                'Replace Conditional with Polymorphism',
                'Use inheritance and polymorphism to eliminate conditional logic.',
                $this->getReplaceConditionalWithPolymorphismExample(),
                min($priority, 3),
                $value,
                $threshold
            ),
        ];
    }

    /**
     * @return array<RefactoringSuggestion>
     */
    private function buildVariableCountSuggestions(float $value, float $threshold): array
    {
        $priority = $this->calculatePriority($value, $threshold);

        return [
            new RefactoringSuggestion(
                'variableCount',
                'Extract Method',
                'Move variable-heavy logic to separate methods.',
                $this->getExtractMethodExample(),
                min($priority, 4),
                $value,
                $threshold
            ),
            new RefactoringSuggestion(
                'variableCount',
                'Replace Temp with Query',
                'Calculate values on-demand instead of storing in temporary variables.',
                $this->getReplaceTempWithQueryExample(),
                min($priority, 3),
                $value,
                $threshold
            ),
            new RefactoringSuggestion(
                'variableCount',
                'Inline Temp',
                'Remove unnecessary intermediate variables.',
                $this->getInlineTempExample(),
                min($priority, 2),
                $value,
                $threshold
            ),
        ];
    }

    /**
     * @return array<RefactoringSuggestion>
     */
    private function buildPropertyCallCountSuggestions(float $value, float $threshold): array
    {
        $priority = $this->calculatePriority($value, $threshold);

        return [
            new RefactoringSuggestion(
                'propertyCallCount',
                'Move Method (Feature Envy)',
                'Move the method to the class it accesses most frequently.',
                $this->getMoveMethodExample(),
                min($priority, 5),
                $value,
                $threshold
            ),
            new RefactoringSuggestion(
                'propertyCallCount',
                'Extract Method',
                'Create helper methods in the accessed class.',
                $this->getExtractMethodExample(),
                min($priority, 4),
                $value,
                $threshold
            ),
            new RefactoringSuggestion(
                'propertyCallCount',
                'Hide Delegate',
                'Encapsulate property access chains in methods.',
                $this->getHideDelegateExample(),
                min($priority, 3),
                $value,
                $threshold
            ),
        ];
    }

    /**
     * @return array<RefactoringSuggestion>
     */
    private function buildIfCountSuggestions(float $value, float $threshold): array
    {
        $priority = $this->calculatePriority($value, $threshold);

        return [
            new RefactoringSuggestion(
                'ifCount',
                'Replace Conditional with Polymorphism',
                'Use strategy or state pattern to eliminate conditional logic.',
                $this->getReplaceConditionalWithPolymorphismExample(),
                min($priority, 5),
                $value,
                $threshold
            ),
            new RefactoringSuggestion(
                'ifCount',
                'Strategy Pattern',
                'Encapsulate algorithms in separate strategy classes.',
                $this->getStrategyPatternExample(),
                min($priority, 4),
                $value,
                $threshold
            ),
            new RefactoringSuggestion(
                'ifCount',
                'Introduce Null Object',
                'Replace null checks with null object pattern.',
                $this->getIntroduceNullObjectExample(),
                min($priority, 3),
                $value,
                $threshold
            ),
        ];
    }

    /**
     * @return array<RefactoringSuggestion>
     */
    private function buildIfNestingLevelSuggestions(float $value, float $threshold): array
    {
        $priority = $this->calculatePriority($value, $threshold);

        return [
            new RefactoringSuggestion(
                'ifNestingLevel',
                'Replace Nested Conditionals with Guard Clauses',
                'Use early returns to flatten nested logic.',
                $this->getGuardClausesExample(),
                min($priority, 5),
                $value,
                $threshold
            ),
            new RefactoringSuggestion(
                'ifNestingLevel',
                'Extract Method',
                'Move nested blocks to separate methods.',
                $this->getExtractMethodExample(),
                min($priority, 4),
                $value,
                $threshold
            ),
            new RefactoringSuggestion(
                'ifNestingLevel',
                'Decompose Conditional',
                'Simplify complex conditions by breaking them into smaller parts.',
                $this->getDecomposeConditionalExample(),
                min($priority, 3),
                $value,
                $threshold
            ),
        ];
    }

    /**
     * @return array<RefactoringSuggestion>
     */
    private function buildElseCountSuggestions(float $value, float $threshold): array
    {
        $priority = $this->calculatePriority($value, $threshold);

        return [
            new RefactoringSuggestion(
                'elseCount',
                'Use Guard Clauses',
                'Eliminate else branches with early returns.',
                $this->getGuardClausesExample(),
                min($priority, 5),
                $value,
                $threshold
            ),
            new RefactoringSuggestion(
                'elseCount',
                'Replace Conditional with Polymorphism',
                'Use object-oriented design to eliminate branching logic.',
                $this->getReplaceConditionalWithPolymorphismExample(),
                min($priority, 4),
                $value,
                $threshold
            ),
            new RefactoringSuggestion(
                'elseCount',
                'Consolidate Duplicate Conditional Fragments',
                'Extract common code from conditional branches.',
                $this->getConsolidateDuplicateConditionalFragmentsExample(),
                min($priority, 3),
                $value,
                $threshold
            ),
        ];
    }

    // Code examples for each refactoring technique

    private function getExtractMethodExample(): string
    {
        return <<<'EXAMPLE'
**Before:**
```php
public function processOrder($order) {
    // Validate order
    if (empty($order['items'])) {
        throw new InvalidArgumentException('Order must have items');
    }
    if ($order['total'] <= 0) {
        throw new InvalidArgumentException('Order total must be positive');
    }
    
    // Calculate tax
    $taxRate = 0.08;
    $tax = $order['total'] * $taxRate;
    
    // Apply discount
    if ($order['total'] > 100) {
        $discount = $order['total'] * 0.1;
    } else {
        $discount = 0;
    }
    
    return $order['total'] + $tax - $discount;
}
```

**After:**
```php
public function processOrder($order) {
    $this->validateOrder($order);
    $tax = $this->calculateTax($order['total']);
    $discount = $this->calculateDiscount($order['total']);
    
    return $order['total'] + $tax - $discount;
}

private function validateOrder($order): void {
    if (empty($order['items'])) {
        throw new InvalidArgumentException('Order must have items');
    }
    if ($order['total'] <= 0) {
        throw new InvalidArgumentException('Order total must be positive');
    }
}

private function calculateTax(float $total): float {
    return $total * 0.08;
}

private function calculateDiscount(float $total): float {
    return $total > 100 ? $total * 0.1 : 0;
}
```
EXAMPLE;
    }

    private function getIntroduceParameterObjectExample(): string
    {
        return <<<'EXAMPLE'
**Before:**
```php
public function createUser($firstName, $lastName, $email, $phone, $address, $city, $zipCode) {
    // Method with too many parameters
    return new User($firstName, $lastName, $email, $phone, $address, $city, $zipCode);
}
```

**After:**
```php
public function createUser(UserData $userData) {
    return new User(
        $userData->firstName,
        $userData->lastName,
        $userData->email,
        $userData->phone,
        $userData->address,
        $userData->city,
        $userData->zipCode
    );
}

class UserData {
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $phone,
        public string $address,
        public string $city,
        public string $zipCode
    ) {}
}
```
EXAMPLE;
    }

    private function getGuardClausesExample(): string
    {
        return <<<'EXAMPLE'
**Before:**
```php
public function calculatePrice($order) {
    if ($order !== null) {
        if ($order['items'] !== null) {
            if (count($order['items']) > 0) {
                $total = 0;
                foreach ($order['items'] as $item) {
                    $total += $item['price'];
                }
                return $total;
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    } else {
        return 0;
    }
}
```

**After:**
```php
public function calculatePrice($order) {
    if ($order === null) return 0;
    if ($order['items'] === null) return 0;
    if (count($order['items']) === 0) return 0;
    
    $total = 0;
    foreach ($order['items'] as $item) {
        $total += $item['price'];
    }
    return $total;
}
```
EXAMPLE;
    }

    private function getReplaceConditionalWithPolymorphismExample(): string
    {
        return <<<'EXAMPLE'
**Before:**
```php
public function calculateShipping($order) {
    switch ($order['shippingType']) {
        case 'standard':
            return $order['weight'] * 0.5;
        case 'express':
            return $order['weight'] * 1.0;
        case 'overnight':
            return $order['weight'] * 2.0;
        default:
            return 0;
    }
}
```

**After:**
```php
public function calculateShipping($order) {
    $shippingCalculator = ShippingCalculatorFactory::create($order['shippingType']);
    return $shippingCalculator->calculate($order['weight']);
}

abstract class ShippingCalculator {
    abstract public function calculate(float $weight): float;
}

class StandardShippingCalculator extends ShippingCalculator {
    public function calculate(float $weight): float {
        return $weight * 0.5;
    }
}

class ExpressShippingCalculator extends ShippingCalculator {
    public function calculate(float $weight): float {
        return $weight * 1.0;
    }
}
```
EXAMPLE;
    }

    private function getMoveMethodExample(): string
    {
        return <<<'EXAMPLE'
**Before:**
```php
class OrderProcessor {
    public function processOrder($order) {
        // This method accesses Order properties frequently
        $order->validate();
        $order->calculateTotal();
        $order->applyDiscount();
        $order->calculateTax();
        return $order->getFinalAmount();
    }
}
```

**After:**
```php
class Order {
    public function process() {
        $this->validate();
        $this->calculateTotal();
        $this->applyDiscount();
        $this->calculateTax();
        return $this->getFinalAmount();
    }
}

class OrderProcessor {
    public function processOrder(Order $order) {
        return $order->process();
    }
}
```
EXAMPLE;
    }

    private function getDecomposeConditionalExample(): string
    {
        return <<<'EXAMPLE'
**Before:**
```php
public function calculatePrice($customer, $order) {
    if ($customer['type'] === 'premium' && $order['total'] > 100 && $customer['loyaltyYears'] > 2) {
        return $order['total'] * 0.8; // 20% discount
    }
    return $order['total'];
}
```

**After:**
```php
public function calculatePrice($customer, $order) {
    if ($this->isEligibleForPremiumDiscount($customer, $order)) {
        return $order['total'] * 0.8; // 20% discount
    }
    return $order['total'];
}

private function isEligibleForPremiumDiscount($customer, $order): bool {
    return $this->isPremiumCustomer($customer) 
        && $this->hasHighOrderValue($order) 
        && $this->hasLongLoyalty($customer);
}

private function isPremiumCustomer($customer): bool {
    return $customer['type'] === 'premium';
}

private function hasHighOrderValue($order): bool {
    return $order['total'] > 100;
}

private function hasLongLoyalty($customer): bool {
    return $customer['loyaltyYears'] > 2;
}
```
EXAMPLE;
    }

    private function getReplaceTempWithQueryExample(): string
    {
        return <<<'EXAMPLE'
**Before:**
```php
public function calculateTotal($items) {
    $total = 0;
    foreach ($items as $item) {
        $total += $item['price'];
    }
    
    $tax = $total * 0.08;
    $discount = $this->calculateDiscount($total);
    
    return $total + $tax - $discount;
}
```

**After:**
```php
public function calculateTotal($items) {
    $subtotal = $this->calculateSubtotal($items);
    $tax = $this->calculateTax($subtotal);
    $discount = $this->calculateDiscount($subtotal);
    
    return $subtotal + $tax - $discount;
}

private function calculateSubtotal($items): float {
    $total = 0;
    foreach ($items as $item) {
        $total += $item['price'];
    }
    return $total;
}

private function calculateTax(float $amount): float {
    return $amount * 0.08;
}
```
EXAMPLE;
    }

    private function getPreserveWholeObjectExample(): string
    {
        return <<<'EXAMPLE'
**Before:**
```php
public function calculateShipping($customerAddress, $customerCity, $customerZip) {
    // Method receives individual address fields
    return $this->shippingService->calculateRate($customerAddress, $customerCity, $customerZip);
}
```

**After:**
```php
public function calculateShipping(Address $customerAddress) {
    // Method receives the whole address object
    return $this->shippingService->calculateRate($customerAddress);
}
```
EXAMPLE;
    }

    private function getBuilderPatternExample(): string
    {
        return <<<'EXAMPLE'
**Before:**
```php
public function createUser($name, $email, $phone = null, $address = null, $city = null, $zip = null, $country = null) {
    // Constructor with many optional parameters
    return new User($name, $email, $phone, $address, $city, $zip, $country);
}
```

**After:**
```php
public function createUser(): UserBuilder {
    return new UserBuilder();
}

class UserBuilder {
    private string $name;
    private string $email;
    private ?string $phone = null;
    private ?string $address = null;
    private ?string $city = null;
    private ?string $zip = null;
    private ?string $country = null;

    public function setName(string $name): self {
        $this->name = $name;
        return $this;
    }

    public function setEmail(string $email): self {
        $this->email = $email;
        return $this;
    }

    public function build(): User {
        return new User($this->name, $this->email, $this->phone, $this->address, $this->city, $this->zip, $this->country);
    }
}
```
EXAMPLE;
    }

    private function getConsolidateConditionalExpressionExample(): string
    {
        return <<<'EXAMPLE'
**Before:**
```php
public function isEligibleForDiscount($customer) {
    if ($customer['age'] >= 65) {
        return true;
    }
    if ($customer['age'] <= 18) {
        return true;
    }
    if ($customer['income'] < 30000) {
        return true;
    }
    return false;
}
```

**After:**
```php
public function isEligibleForDiscount($customer) {
    return $this->isSeniorCitizen($customer) 
        || $this->isStudent($customer) 
        || $this->hasLowIncome($customer);
}

private function isSeniorCitizen($customer): bool {
    return $customer['age'] >= 65;
}

private function isStudent($customer): bool {
    return $customer['age'] <= 18;
}

private function hasLowIncome($customer): bool {
    return $customer['income'] < 30000;
}
```
EXAMPLE;
    }

    private function getInlineTempExample(): string
    {
        return <<<'EXAMPLE'
**Before:**
```php
public function calculateTotal($items) {
    $subtotal = $this->calculateSubtotal($items);
    $tax = $subtotal * 0.08;
    $discount = $this->calculateDiscount($subtotal);
    
    return $subtotal + $tax - $discount;
}
```

**After:**
```php
public function calculateTotal($items) {
    $subtotal = $this->calculateSubtotal($items);
    
    return $subtotal + ($subtotal * 0.08) - $this->calculateDiscount($subtotal);
}
```
EXAMPLE;
    }

    private function getHideDelegateExample(): string
    {
        return <<<'EXAMPLE'
**Before:**
```php
public function getCustomerCity($order) {
    return $order->customer->address->city;
}
```

**After:**
```php
public function getCustomerCity($order) {
    return $order->getCustomerCity();
}

class Order {
    public function getCustomerCity(): string {
        return $this->customer->getCity();
    }
}

class Customer {
    public function getCity(): string {
        return $this->address->city;
    }
}
```
EXAMPLE;
    }

    private function getStrategyPatternExample(): string
    {
        return <<<'EXAMPLE'
**Before:**
```php
public function processPayment($order, $paymentType) {
    if ($paymentType === 'credit_card') {
        return $this->processCreditCard($order);
    } elseif ($paymentType === 'paypal') {
        return $this->processPayPal($order);
    } elseif ($paymentType === 'bank_transfer') {
        return $this->processBankTransfer($order);
    }
}
```

**After:**
```php
public function processPayment($order, PaymentProcessor $processor) {
    return $processor->process($order);
}

interface PaymentProcessor {
    public function process($order): bool;
}

class CreditCardProcessor implements PaymentProcessor {
    public function process($order): bool {
        // Credit card processing logic
        return true;
    }
}

class PayPalProcessor implements PaymentProcessor {
    public function process($order): bool {
        // PayPal processing logic
        return true;
    }
}
```
EXAMPLE;
    }

    private function getIntroduceNullObjectExample(): string
    {
        return <<<'EXAMPLE'
**Before:**
```php
public function calculateShipping($customer) {
    if ($customer->getShippingAddress() !== null) {
        return $this->shippingService->calculateRate($customer->getShippingAddress());
    } else {
        return 0; // Free shipping for customers without address
    }
}
```

**After:**
```php
public function calculateShipping($customer) {
    $shippingAddress = $customer->getShippingAddress();
    return $shippingAddress->calculateShipping($this->shippingService);
}

class NullShippingAddress extends ShippingAddress {
    public function calculateShipping($shippingService): float {
        return 0; // Free shipping
    }
}
```
EXAMPLE;
    }

    private function getConsolidateDuplicateConditionalFragmentsExample(): string
    {
        return <<<'EXAMPLE'
**Before:**
```php
public function calculatePrice($order) {
    if ($order['type'] === 'premium') {
        $basePrice = $order['amount'];
        $tax = $basePrice * 0.1;
        $shipping = 0; // Free shipping for premium
        return $basePrice + $tax + $shipping;
    } else {
        $basePrice = $order['amount'];
        $tax = $basePrice * 0.1;
        $shipping = 10; // Standard shipping
        return $basePrice + $tax + $shipping;
    }
}
```

**After:**
```php
public function calculatePrice($order) {
    $basePrice = $order['amount'];
    $tax = $basePrice * 0.1;
    $shipping = $order['type'] === 'premium' ? 0 : 10;
    
    return $basePrice + $tax + $shipping;
}
```
EXAMPLE;
    }

    private function getIntroduceExplainingVariableExample(): string
    {
        return <<<'EXAMPLE'
**Before:**
```php
public function calculatePrice($order) {
    return $order['basePrice'] * (1 + $order['taxRate']) * (1 - $order['discountRate']) + $order['shippingCost'];
}
```

**After:**
```php
public function calculatePrice($order) {
    $taxMultiplier = 1 + $order['taxRate'];
    $discountMultiplier = 1 - $order['discountRate'];
    $priceWithTax = $order['basePrice'] * $taxMultiplier;
    $priceWithDiscount = $priceWithTax * $discountMultiplier;
    
    return $priceWithDiscount + $order['shippingCost'];
}
```
EXAMPLE;
    }

    private function getReplaceMagicNumbersExample(): string
    {
        return <<<'EXAMPLE'
**Before:**
```php
public function calculateTax($amount) {
    return $amount * 0.08; // Magic number
}

public function isEligibleForDiscount($age) {
    return $age >= 65; // Magic number
}
```

**After:**
```php
private const TAX_RATE = 0.08;
private const SENIOR_CITIZEN_AGE = 65;

public function calculateTax($amount) {
    return $amount * self::TAX_RATE;
}

public function isEligibleForDiscount($age) {
    return $age >= self::SENIOR_CITIZEN_AGE;
}
```
EXAMPLE;
    }

    private function getSimplifyBooleanExpressionsExample(): string
    {
        return <<<'EXAMPLE'
**Before:**
```php
public function isEligible($customer) {
    return ($customer['age'] >= 18 && $customer['age'] <= 65) && 
           ($customer['income'] > 20000 || $customer['hasJob']) && 
           !$customer['hasCriminalRecord'];
}
```

**After:**
```php
public function isEligible($customer) {
    return $this->isWithinAgeRange($customer) 
        && $this->hasSufficientIncome($customer) 
        && $this->hasCleanRecord($customer);
}

private function isWithinAgeRange($customer): bool {
    return $customer['age'] >= 18 && $customer['age'] <= 65;
}

private function hasSufficientIncome($customer): bool {
    return $customer['income'] > 20000 || $customer['hasJob'];
}

private function hasCleanRecord($customer): bool {
    return !$customer['hasCriminalRecord'];
}
```
EXAMPLE;
    }

    private function getSimplifyExpressionsExample(): string
    {
        return <<<'EXAMPLE'
**Before:**
```php
public function calculatePrice($order) {
    return $order['basePrice'] * (1 + $order['taxRate']) * (1 - $order['discountRate']) + $order['shippingCost'];
}
```

**After:**
```php
public function calculatePrice($order) {
    $taxMultiplier = 1 + $order['taxRate'];
    $discountMultiplier = 1 - $order['discountRate'];
    $priceWithTax = $order['basePrice'] * $taxMultiplier;
    $priceWithDiscount = $priceWithTax * $discountMultiplier;
    
    return $priceWithDiscount + $order['shippingCost'];
}
```
EXAMPLE;
    }
}
