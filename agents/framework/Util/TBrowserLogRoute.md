# Util/TBrowserLogRoute

### Directories
[framework](../INDEX.md) / [Util](./INDEX.md) / **`TBrowserLogRoute`**

## Class Info
**Location:** `framework/Util/TBrowserLogRoute.php`
**Namespace:** `Prado\Util`
**Extends:** [`TLogRoute`](./TLogRoute.md)
**Implements:** `IOutputLogRoute`

## Overview
`TBrowserLogRoute` appends an HTML log table directly into the HTTP response body. Each row shows the timestamp, time delta, log level, category, and message. It supports optional CSS-class–based styling (otherwise inline styles are used), heat-map colorization of the time-delta cell, and alternating row shading. It is suppressed in Performance mode and when the application runs as a shell.

## Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `CssClass` | `?string` | `null` | CSS class applied to the rendered table; when absent, inline styles are used. |
| `ColorizeDelta` | `bool` | `true` | Colors the time-delta cell using a red-to-green heat-map scale. |
| `AddPrefix` | `bool` | `false` | Prepends the IP/user/session prefix string to each message cell. |

## Configuration

Configured as a `<route>` sub-element inside a `TLogRouter` module, not as a standalone module.

**application.xml:**
```xml
<modules>
  <module id="log" class="Prado\Util\TLogRouter">
    <route class="Prado\Util\TBrowserLogRoute" Levels="Debug|Info|Warning|Error" />
  </module>
</modules>
```

**PHP equivalent:**
```php
return [
    'modules' => [
        'log' => [
            'class' => 'Prado\Util\TLogRouter',
            'routes' => [
                ['class' => 'Prado\Util\TBrowserLogRoute', 'properties' => ['Levels' => 'Debug|Info|Warning|Error']],
            ],
        ],
    ],
];
```

## See Also

- [`TLogRoute`](./TLogRoute.md) — abstract base class
- [`TFirebugLogRoute`](./TFirebugLogRoute.md) — extends this to output to the Firebug console
- [`TLogRouter`](./TLogRouter.md) — module that manages all log routes
