# Shell/Actions/SUMMARY.md

Built-in CLI action handlers for the Prado shell (`php protected/index.php`); each class provides one or more subcommands.

## Classes

- **`TActiveRecordAction`** — Action `activerecord`; generates AR class skeletons from database tables with `generate` and `generate-all` subcommands.

- **`TDbParameterAction`** — Action `param`; manages `TDbParameterModule` parameters (list, get, set, delete).

- **`TFlushCachesAction`** — Action `cache`; clears application cache modules implementing `ICache`; subcommands: `index`, `flush`, `flush-all`.

- **`THelpAction`** — Action `help`; prints usage information for all registered shell actions.

- **`TPhpShellAction`** — Action `shell`; launches interactive PHP REPL with Prado application bootstrapped.

- **`TWebServerAction`** — Action `serve`; starts PHP built-in development web server with `--address=host:port`.

- **`TShellCronAction`** / **`TShellDbCronAction`** — Manually trigger cron tasks via CLI.

- **`TShellCronLogBehavior`** — Behavior that adds execution logging to cron tasks.
