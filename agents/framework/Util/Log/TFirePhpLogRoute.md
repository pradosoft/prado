# Util/Log/TFirePhpLogRoute

### Directories
[framework](../../INDEX.md) / [Util](../INDEX.md) / [Log](./INDEX.md) / **`TFirePhpLogRoute`**

## Class Info
**Location:** `framework/Util/Log/TFirePhpLogRoute.php`
**Namespace:** `Prado\Util\Log`
**Extends:** [`TLogRoute`](./TLogRoute.md)
**Implements:** `IOutputLogRoute`

## Overview
`TFirePhpLogRoute` delivers log entries to the Firebug console via the FirePHP library, using HTTP response headers rather than inline HTML. Logs are grouped under a collapsible label. If headers have already been sent, it falls back gracefully to `TBrowserLogRoute` output with an error notice.

## Constants

| Constant | Value |
|----------|-------|
| `DEFAULT_LABEL` | `'Prado\Util\Log\TLogRouter(TFirePhpLogRoute)'` |

## Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `GroupLabel` | `string` | `DEFAULT_LABEL` | Label for the collapsed FirePHP log group shown in the Firebug console. |

## Level Mapping

| Prado Level | FirePHP Level |
|-------------|---------------|
| Debug / Notice / Profile | `LOG` |
| Warning | `WARN` |
| Error / Alert / Fatal | `ERROR` |

## Configuration

Configured as a `<route>` sub-element inside a `TLogRouter` module, not as a standalone module.

**application.xml:**
```xml
<modules>
  <module id="log" class="Prado\Util\Log\TLogRouter">
    <route class="Prado\Util\Log\TFirePhpLogRoute" Levels="Debug|Info|Warning|Error" />
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
                ['class' => 'Prado\Util\Log\TFirePhpLogRoute', 'properties' => ['Levels' => 'Debug|Info|Warning|Error']],
            ],
        ],
    ],
];
```

## See Also

- [`TLogRoute`](./TLogRoute.md) — abstract base class
- [`TFirebugLogRoute`](./TFirebugLogRoute.md) — alternative Firebug route using inline `<script>` tags
- [`TLogRouter`](./TLogRouter.md) — module that manages all log routes
