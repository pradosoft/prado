# Util/Cron/TCronWakeFile

### Directories
[framework](../../../INDEX.md) / [Util](../../INDEX.md) / [Cron](../INDEX.md) / [Wake](./INDEX.md) / **`TCronWakeFile`**

## Class Info
**Location:** `framework/Util/Cron/Wake/TCronWakeFile.php`
**Namespace:** `Prado\Util\Cron\Wake`
**Extends:** [`TCronWakePoll`](TCronWakePoll.md)
**Since:** 4.4.0

## Overview
`TCronWakeFile` signals through a file: a wake writes the current microtime into `WakeFile`, and the waiting cron process polls the file's content every `PollInterval`. The content is the stamp rather than the modification time, which some file systems keep to the second. On a shared file system the wake reaches the cron process of another host.

```xml
<module id="cronwake" class="Prado\Util\Cron\TCronWakeFile" WakeFile="Application.Runtime.cronwake" PollInterval="0.5" />
<module id="cron" class="Prado\Util\Cron\TDbCronManager" WakeTransport="cronwake" ... />
```

## Properties

| Property | Default | Description |
|---|---|---|
| `WakeFile` | `<runtime>/cronwake` | The stamp file, as a path or a namespace. The transport is unavailable when its directory does not exist. |
| `PollInterval` | `1.0` | Seconds between polls. |

## See Also

- [TCronWakePoll](TCronWakePoll.md)
- [ICronWakeTransport](ICronWakeTransport.md)
