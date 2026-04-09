# SUMMARY.md

Cron-style scheduled task engine supporting both file-configured and database-backed task scheduling.

## Classes

- **`TCronModule`** — Main cron scheduler `TModule`; parses cron schedule expressions (standard Linux `minute hour day month dayOfWeek` format).

- **`TDbCronModule`** — Extends `TCronModule` with database-backed task storage, execution logging, and failed-task retry logic.

- **`TCronTask`** — Abstract base for cron tasks; implement `execute(TCronTaskInfo)`; properties: `Name`, `Schedule`, `init()`, `run()`.

- **`TCronMethodTask`** — Concrete task that calls a method on a module or class.

- **`TCronTaskInfo`** — Task metadata: name, schedule, task class, last run time.

- **`TTimeScheduler`** — Cron expression parser supporting multi-language expressions and special tokens (`@daily`, `@hourly`, etc.); method: `getNextTriggerTime($lastRun)`.

- **`TDbCronCleanLogTask`** — Pre-built task that auto-purges old cron log entries from the database.

- **`TShellCronAction`** — CLI action to manually trigger cron evaluation (`prado-cli cron`).

- **`TShellDbCronAction`** — CLI action for managing database-backed cron tasks.

- **`TShellCronLogBehavior`** — Behavior that adds execution logging to cron tasks.
