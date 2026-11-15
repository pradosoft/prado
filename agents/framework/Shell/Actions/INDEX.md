# Shell/Actions/INDEX.md

### Directories
[framework](../../INDEX.md) / [Shell](../INDEX.md) / **`Actions`**

## Purpose

Built-in CLI action handlers for the Prado shell (`prado-cli`). Each class provides one or more subcommands accessible from the command line.

## Classes

- **[`TDbParameterAction`](TDbParameterAction.md)** — Action `param`. Lists, gets, and sets [`TDbParameterModule`](../../Util/TDbParameterModule.md) parameters (a key/value store backed by a database). [`TDbParameterModule`](../../Util/TDbParameterModule.md) registers it.
  - `index` — list the parameters stored in the database; `--all` (`-a`) lists every application parameter.
  - `get <param-key>` — show one parameter.
  - `set <param-key> <param-value>` — store a parameter.

- **[`TFlushCachesAction`](TFlushCachesAction.md)** — Action `cache`. Clears application cache modules implementing `ICache`. Registered when the application has an `ICache` module.
  - `index` — list available cache modules.
  - `flush <module>` — flush the named cache modules.
  - `flush-all` — flush all cache modules and the Performance-mode fx-event cache file.

- **[`THelpAction`](THelpAction.md)** — Action `help`. Prints the general help, or with a command name the subcommands, parameters, and descriptions of that command. Always registered.

- **[`TPhpShellAction`](TPhpShellAction.md)** — Action `shell`. Launches the PsySH interactive PHP REPL bound to the Prado application. Always registered.

- **[`TWebServerAction`](TWebServerAction.md)** — Action `http`, method `serve`. Starts the built-in PHP development web server for the application. Registered in `Debug` mode or with the `Prado:PhpWebServer` parameter.
  - `--address=host:port` — network address (default `127.0.0.1`, port `8080`).
  - Serves the directory of the entry script with `php -S`; an optional argument names a router script.

## Adding a New Shell Action

1. Create a class extending [`TShellAction`](../TShellAction.md).
2. Set `$action` (command name), `$methods` (subcommand list), `$parameters`, `$optional`, and `$description`.
3. Implement `action<Method>($args)` for each entry in `$methods`; a `-` in a method name is dropped (`flush-all` → `actionFlushAll`).
4. Register the class with `TShellApplication::addShellActionClass()`. A built-in action is added in `TShellApplication::installShellActions()`. A module or extension package adds its action from `init()`, as `TDbParameterModule` adds `param` and prado-activerecord's `TActiveRecordConfig` adds `activerecord`.
5. Add the class to `framework/classes.php`.

## Conventions

- Use `$this->getWriter()` ([`TShellWriter`](../TShellWriter.md)) for all output.
- An `action<Method>()` receives the remaining command-line arguments as one array: `$args[0]` is `<action>/<method>`, and the positional arguments follow. Options declared by `options()` are removed first and set as properties.
- Return `true` when the command was handled.
- Keep actions idempotent where possible (safe to run multiple times).
