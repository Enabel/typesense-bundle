# Testing Skill

Use this skill when writing or modifying PHPUnit tests.

## Test Naming Convention — TestDox Style

Name every test method so it reads as a plain English sentence. PHPUnit's TestDox strips the `test` prefix and splits camelCase into words, so the method name *is* the documentation.

### Pattern

`test` + `It` + verb phrase describing what happens and under what condition.

```php
testItReturnsNullWhenUserNotFound
testItThrowsAnExceptionIfTheAmountIsNegative
testItAppliesTheTenPercentDiscountForOrdersOverFifty
testItSendsAWelcomeEmailAfterRegistration
```

### Rules

1. **Start with `testIt`** so the generated sentence begins with "It …" and reads naturally.
2. **State both the outcome and the condition.** Format: "It [does X] [when/if/for Y]."
3. **One behavior per method.** If tempted to use "and" in the name, split into two tests.
4. **Be specific.** Avoid "correctly", "properly", "works". Say *what* the correct behavior is.
5. **If the auto-generated sentence is awkward**, override with `#[TestDox('Your custom sentence')]` on the method.

### Assertion Messages

Only add assertion messages when:
- A test contains multiple assertions and you need to distinguish which one failed.
- The business rule isn't obvious from the assertion alone.

When you do write one, describe the *why*, not the *what*:

```php
// Good — explains the business rule
$this->assertSame(100, $total, 'Total should include shipping for orders under $50');

// Bad — just restates the assertion
$this->assertSame(100, $total, 'Expected 100');
```

## Running Tests

Tests run inside Docker: `docker compose run --rm app vendor/bin/phpunit`
