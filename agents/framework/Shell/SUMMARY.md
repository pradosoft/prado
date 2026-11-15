# Shell/SUMMARY.md

CLI application support via the `prado-cli` executable, which gives command-line actions the application configuration, modules, and services.

## Classes

- **`TShellApplication`** — Extends `TApplication` for CLI contexts; properties: `QuietMode`, `Writer`; methods: `run()`, `runService()`, `printHelp()`, `addShellActionClass()`, `registerOption()`.

- **`TShellAction`** — Abstract base for CLI commands; a subclass sets `$action`, `$methods`, `$parameters`, `$optional`, and `$description` and implements `action<Method>($args)`.

- **`TShellWriter`** — ANSI console output; methods: `write($str, $attr)`, `writeLine($str, $attr)`, `writeError($text)`, `tableWidget()`, `wrapText()`; attribute constants are ANSI styles and colors (`BOLD`, `RED`, `BG_RED`, ...).

- **`TShellLoginBehavior`** — `TAuthManager` behavior that logs in a user from `--user`/`--password` or a prompt before actions run.
