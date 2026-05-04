# Util/TVarDumper

### Directories
[framework](../INDEX.md) / [Util](./INDEX.md) / **`TVarDumper`**

## Class Info
**Location:** `framework/Util/TVarDumper.php`
**Namespace:** `Prado\Util`

## Overview
Human-readable variable dump for Prado `[TComponent](../TComponent.md)` objects and complex data structures. Use instead of PHP's `var_dump()` or `print_r()` — it understands [TComponent](../TComponent.md)'s property/event system and avoids circular reference issues.

## Usage

```php
// Static methods (preferred):
$string = TVarDumper::dumpAsString($value);
TVarDumper::dump($value);          // echoes directly
$string = TVarDumper::dumpAsString($value, $depth, $highlight);

// Parameters:
// $value    — any value
// $depth    — recursion depth limit (default: 10)
// $highlight — bool: apply HTML syntax highlighting (default: false)
```

## Example

```php
$record = PostRecord::finder()->findByPk(1);
echo TVarDumper::dumpAsString($record);
// PostRecord
// (
//     [id] => 1
//     [title] => Hello World
//     [author_id] => 42
//     [author] => not loaded
//     ...
// )
```

## Gotchas

- **Circular references** — detected and replaced with `"*RECURSION*"` rather than causing infinite loops.
- **TComponent properties** — uses `getXxx()` methods rather than raw PHP property access, so it shows the logical property values.
- **Not for logging** — use `[TLogger](TLogger.md)` / `[Prado](../Prado.md)::log()` for structured logging. `TVarDumper` is for interactive debugging only.
- **HTML output** — pass `$highlight = true` for color-coded HTML output in browser debug panels (e.g., `TBrowserLogRoute`).
