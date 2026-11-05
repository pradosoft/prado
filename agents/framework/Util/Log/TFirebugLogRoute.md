# Util/Log/TFirebugLogRoute

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Log](./INDEX.md) / **`TFirebugLogRoute`**

## Class Info
**Location:** `framework/Util/Log/TFirebugLogRoute.php`
**Namespace:** `Prado\Util\Log`
**Extends:** [`TBrowserLogRoute`](./TBrowserLogRoute.md)
**Implements:** `IOutputLogRoute`

## Overview
`TFirebugLogRoute` outputs log messages to the Firebug browser console. For normal page requests it emits a `<script>` block that calls `console.info`, `console.warn`, or `console.error`. For callback (AJAX) requests it writes a structured JSON block using the callback debug header boundary, which the client-side PRADO runtime relays to Firebug.

## Configuration

Configured as a `<route>` sub-element inside a `TLogRouter` module, not as a standalone module.

**application.xml:**
```xml
<modules>
  <module id="log" class="Prado\Util\Log\TLogRouter">
    <route class="Prado\Util\Log\TFirebugLogRoute" Levels="Debug|Info|Warning|Error" />
  </module>
</modules>
```

**PHP equivalent:**
```php
return [
    'modules' => [
        'log' => [
            'class' => 'Prado\Util\Log\TLogRouter',
            'routes' => [
                ['class' => 'Prado\Util\Log\TFirebugLogRoute', 'properties' => ['Levels' => 'Debug|Info|Warning|Error']],
            ],
        ],
    ],
];
```

## See Also

- [`TBrowserLogRoute`](./TBrowserLogRoute.md) — parent class for inline HTML log output
- [`TFirePhpLogRoute`](./TFirePhpLogRoute.md) — alternative console route using HTTP headers
- [`TLogRoute`](./TLogRoute.md) — abstract base class
