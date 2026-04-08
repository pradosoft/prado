# Shell/Actions/INDEX.md - SHELL_ACTIONS_INDEX.md

This file provides guidance to Agents when working with code in this repository.

## Purpose

Built-in CLI action handlers for the Prado shell (`php protected/index.php`). Each class provides one or more subcommands accessible from the command line.

## Classes

- **`TActiveRecordAction`** — Action `activerecord`. Generates Active Record PHP class skeletons from database tables.
  - `generate <table> <output>` — generate AR class for a single table with optional `--soap` and `--overwrite` flags.
  - `generate-all <output>` — generate AR classes for all tables with optional `--prefix`, `--suffix`, `--soap`, `--overwrite`.

- **`TDbParameterAction`** — Action `param`. Manages `TDbParameterModule` parameters (key/value store backed by a database).
  - Subcommands for listing, getting, setting, and deleting application parameters.

- **`TFlushCachesAction`** — Action `cache`. Clears application cache modules implementing `ICache`.
  - `index` — list available cache modules.
  - `flush <module>` — flush a named cache module.
  - `flush-all` — flush all registered cache modules.

- **`THelpAction`** — Action `help`. Prints usage information for all registered shell actions. Always available; lists action names, parameters, optional arguments, and descriptions.

- **`TPhpShellAction`** — Action `shell`. Launches an interactive PHP REPL with the Prado application bootstrapped. Useful for ad-hoc debugging and scripting against live application state.

- **`TWebServerAction`** — Action `serve`. Starts the built-in PHP development web server for the application.
  - `--address=host:port` — network address (default `127.0.0.1:8080`).
  - Wraps `php -S` with the application entry point as the router script.

## Adding a New Shell Action

1. Create a class extending `TShellAction` in this directory.
2. Set `$action` (command name), `$methods` (subcommand list), `$parameters`, `$optional`, and `$description`.
3. Implement each method corresponding to an entry in `$methods`.
4. Register the class in `framework/Shell/TShellApplication.php` (or via module config).
5. Add the class to `framework/classes.php`.

## Conventions

- Use `$this->getWriter()` (`TShellWriter`) for all output — it handles colour, indentation, and verbosity levels.
- Actions receive parsed CLI arguments as method parameters; optional arguments default to `null`.
- Keep actions idempotent where possible (safe to run multiple times).
