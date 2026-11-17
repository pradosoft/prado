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
$string = TVarDumper::dump($value);
$string = TVarDumper::dump($value, $depth, $highlight);

// Parameters:
// $value    — any value
// $depth    — recursion depth limit (default: 10)
// $highlight — bool: apply HTML syntax highlighting (default: false)
```

## Example

```php
$record = PostRecord::finder()->findByPk(1);
echo TVarDumper::dump($record);
// PostRecord#1
// (
//     [id] => 1
//     [title] => Hello World
//     [author_id] => 42
//     [author] => not loaded
//     ...
// )
```

## Gotchas

- **Circular references** — an object already dumped prints as `ClassName#N(...)`, `N` its number in the dump; past `$depth`, an object prints as `ClassName(...)` and an array as `array(...)`.
- **Object members** — the members of `__debugInfo()` when the class has it, which must return an array, else the object's properties; a protected property shows as `*:name` and a private one as `Class:name`.
- **Not for logging** — use `[TLogger](TLogger.md)` / `[Prado](../Prado.md)::log()` for structured logging. `TVarDumper` is for interactive debugging only.
- **HTML output** — pass `$highlight = true` for color-coded HTML output in browser debug panels (e.g., `TBrowserLogRoute`). The output leaves out the `<?php` tag that `highlight_string()` needs.
- **Resources** — an open resource dumps as `{resource}`, a closed one as `{resource (closed)}`.
