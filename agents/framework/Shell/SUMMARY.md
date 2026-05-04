# Shell/SUMMARY.md

CLI application support via `prado-cli` executable allowing command-line scripts to access application configuration, modules, and services.

## Classes

- **`TShellApplication`** — Extends `TApplication` for CLI contexts; properties: `QuietMode`, `OutputWriter`; methods: `run()`, `renderException()`, `processRequest()`.

- **`TShellAction`** — Abstract base for CLI commands; subclass and implement `run(array $args)`; properties: `Action`, `Parameters`, `Optional`, `Methods`.

- **`TShellWriter`** — Formatted console output; methods: `write($text, $format)`, `writeLine($text, $format)`; format constants: `NORMAL`, `INFO`, `SUCCESS`, `WARNING`, `ERROR`, `DEBUG`.

- **`TShellLoginBehavior`** — Behavior that prompts for user credentials at CLI startup.
