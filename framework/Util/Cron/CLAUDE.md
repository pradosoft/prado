# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Purpose

Cron-style scheduled task engine for the Prado framework. Supports both file-configured and database-backed task scheduling.

## Classes

- **`TCronModule`** — Main cron scheduler `TModule`. Parses cron schedule expressions (standard Linux `minute hour day month dayOfWeek` format plus special expressions like `@daily`, `@hourly`). Registers tasks and triggers execution. Designed to be called periodically by the system crontab (run the `prado-cli cron` shell action). Supports user context switching per task.

- **`TDbCronModule`** — Extends `TCronModule` with database-backed task storage. Supports dynamic task registration, execution logging, and failed-task retry logic.

- **`TCronTask`** — Abstract base for cron tasks. Implement `execute(TCronTaskInfo)`. Properties: `Name`, `Schedule`. Methods: `init()`, `run()`.

- **`TCronMethodTask`** — Concrete task that calls a method on a module or class. Method and parameters are configured externally.

- **`TCronTaskInfo`** — Task metadata: name, schedule, task class, last run time.

- **`TTimeScheduler`** — Cron expression parser. Supports multi-language expressions (8 languages) and special tokens (`@daily`, `@weekly`, `@monthly`, `@yearly`, `@annually`, `@hourly`, `@midnight`, `@{time()}`). `getNextTriggerTime($lastRun)` calculates the next execution timestamp.

- **`TDbCronCleanLogTask`** — Pre-built task that auto-purges old cron log entries from the database.

- **`TShellCronAction`** — CLI action to manually trigger cron evaluation (`prado-cli cron`).

- **`TShellDbCronAction`** — CLI action for managing database-backed cron tasks.

- **`TShellCronLogBehavior`** — Behavior that adds execution logging to cron tasks.

## Cron Schedule Format

```
minute  hour  day  month  dayOfWeek
  *       *     *    *        *
```

- Each field accepts: `*` (any), specific values, ranges (`1-5`), step values (`*/5`), lists (`1,3,5`).
- Special expressions: `@yearly`, `@annually`, `@monthly`, `@weekly`, `@daily`, `@hourly`, `@midnight`, `@{time()}`.
- Expressions are case-insensitive and support 8 language name sets for month/day names.

## Conventions

- Register tasks in `application.xml` under the cron module, or dynamically via `TCronModule::addTask()`.
- The system crontab should call `php prado-cli.php <appdir> cron` on a regular interval (e.g., every minute).
- `TCronModule` tracks last-run times; it will not execute a task more frequently than its schedule allows.
- For database-backed tasks, `TDbCronModule` must have a `TDbConnection` configured.
