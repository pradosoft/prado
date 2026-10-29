# Util/TInputValidator

### Directories
[framework](../INDEX.md) / [Util](./INDEX.md) / **`TInputValidator`**

## Class Info
**Location:** `framework/Util/TInputValidator.php`
**Namespace:** `Prado\Util`
**Extends:** `[TComponent](../TComponent.md)`
**Since:** 4.4.0

## Overview
Rule-based validation of an input array (a parsed request body, a form, a job payload). `validate($data, $rules)` returns the declared fields with type coercions applied, or throws `TInputValidationException` carrying every field's errors. `isValid($data, $rules)` runs the same check without throwing and stores the outcome in `getErrors()` / `getValidated()`. `TRestResource::validate()` delegates to this class.

Rules are keyed by field name. Each field takes a pipe-delimited string or a list of rule strings; a rule takes an optional parameter after a colon.

```php
$validator = new TInputValidator();
$data = $validator->validate($body, [
    'name'   => 'required|string|max:10',
    'age'    => 'required|integer|min:0|max:150',
    'email'  => 'required|email',
    'status' => ['nullable', 'in:active,inactive'],
]);
```

## Rules

| Rule | Meaning | Coercion |
|---|---|---|
| `required` | present and not null | |
| `nullable` | a present null is kept as null; the other rules are skipped | |
| `string` | `is_string()` | |
| `integer`, `int` | int, or a string/float that passes `FILTER_VALIDATE_INT` (`'--5'`, `'1.5'`, `'5e2'`, `' 5'` fail) | to `int` |
| `float`, `numeric` | `is_numeric()` | to `float` |
| `boolean`, `bool` | one of `true`, `false`, `1`, `0`, `'1'`, `'0'`, `'true'`, `'false'` (strict) | to `bool` |
| `array` | `is_array()` | |
| `email` | `FILTER_VALIDATE_EMAIL` | |
| `url` | `FILTER_VALIDATE_URL` (a bare hostname fails) | |
| `min:N` | string: `mb_strlen() >= N`; numeric: value `>= N` | |
| `max:N` | string: `mb_strlen() <= N`; numeric: value `<= N` | |
| `in:a,b,c` | scalar whose string form equals one of the trimmed options; a non-scalar fails | |

Presence and null are decided before the rule loop:

| Field state | `required` | `nullable` | Result |
|---|---|---|---|
| absent | yes | any | error "The {field} field is required." |
| present, null | yes | any | error (required wins over nullable) |
| present, null | no | yes | `validated[field] = null` |
| present, null | no | no | field omitted |
| absent | no | any | field omitted |

Fields in `$data` without a rule are dropped. A field with one failing rule is left out of the validated data; every failing rule of the field adds a message.

## Key Methods

```php
$validator->validate(array $data, array $rules): array;   // throws TInputValidationException
$validator->isValid(array $data, array $rules): bool;     // stores the outcome
$validator->getErrors(): array;      // ['field' => ['message', ...]] of the last run
$validator->getValidated(): array;   // coerced declared fields of the last run
$validator->addRule(string $name, callable $rule): void;  // registers or replaces a rule
$validator->hasRule(string $name): bool;
```

## Extending

A rule is a callable `function (mixed &$value, ?string $parameter, string $field): ?string` that returns `null` when valid or the error message otherwise. It may coerce `$value` by reference; the coerced value is what later rules see and what `validate()` returns.

```php
$validator->addRule('slug', function (mixed &$value, ?string $parameter, string $field): ?string {
    if (!is_string($value) || !preg_match('/^[a-z0-9-]+$/', $value)) {
        return "The {$field} field must be a slug.";
    }
    return null;
});
```

The built-in rules are protected `ruleXxx()` methods collected by `getDefaultRules()`; a subclass overrides a method or the map. `addRule()` is per instance. An unknown rule name throws `TConfigurationException('inputvalidator_rule_unknown', $ruleName, $field)`.

## Messages

Each message is built by protected `getMessage(string $text, array $parameters)`, which calls `Prado::localize($text, $parameters)`. With no globalization module the English text with `{field}` / `{parameter}` / `{list}` substituted is returned; with one configured, the English text is the catalogue key, so an application translates the messages in its own catalogue.

## TInputValidationException

`Prado\Util\TInputValidationException` extends `TInvalidDataValueException`, error code `inputvalidator_invalid` (parameter `{0}` is the comma-separated failing field names). `getErrors()` returns the same `['field' => ['message', ...]]` array as `TInputValidator::getErrors()`.

## Patterns & Gotchas

- **Reusable instance** — `isValid()` / `validate()` replace the stored errors and validated data on every call; a failed `validate()` leaves the partial validated data available through `getValidated()`.
- **`min`/`max` on a non-string, non-numeric value** (an array, a bool) pass silently; pair them with a type rule.
- **`in` compares string forms** — `in:1,2` accepts int `1` and string `'1'`; put `in` after a coercing rule only when the coerced string form matches the options (`true` casts to `'1'`).
- **Rule order matters** — `integer|max:150` compares the coerced int; `max:150|integer` compares the raw value (a numeric string still compares numerically).
- **Required/nullable in the map** — both are registered no-op rules so `hasRule('required')` is true; the presence check runs before the loop.
