# TShellDbCronAction

### Directories

[Util](../) > [Cron](Cron/) > TShellDbCronAction

**Location:** `framework/Util/Cron/TShellDbCronAction.php`
**Namespace:** `Prado\Util\Cron`

## Overview

TShellDbCronAction extends TShellCronAction to add database-backed cron management commands. In addition to `run`, `tasks`, and `index`, it provides `add`, `update`, and `remove` commands for managing cron tasks in the database via TDbCronModule.

## Key Properties/Methods

- `getModuleClass()` - Returns TDbCronModule::class (overrides parent)
- `actionAdd($args)` - Adds a task to the database with name, task ID, schedule, and properties
- `actionUpdate($args)` - Updates a task's schedule, username, moduleid, and other properties
- `actionRemove($args)` - Removes a task from the database by name

## See Also

- [TShellCronAction](TShellCronAction.md)
- [TDbCronModule](TDbCronModule.md)
