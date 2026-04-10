# Util/SUMMARY.md

Cross-cutting utilities: behavior/mixin system, logging, scheduled tasks, RPC clients, POSIX signal handling, parameter modules, and helpers.

## Classes

- **`TBaseBehavior`** — Base for all behaviors; properties: `Name`, `Owner`; supports serialization.

- **`TBehavior`** — Per-instance behavior (one owner, stateful); override `attach($component)` and `detach($component)`.

- **`TClassBehavior`** — Class-wide behavior (stateless); registered via `TComponent::attachClassBehavior()`.

- **`TBehaviorsModule`** — `TModule` that loads behavior configuration from `application.xml`.

- **`TCallChain`** — AOP-style method call chain for `dy*` dynamic events.

- **`TLogger`** — Core logger; methods: `log($message, $level, $category)`; levels: `DEBUG`, `INFO`, `NOTICE`, `WARNING`, `ERROR`, `ALERT`, `FATAL`.

- **`TLogRouter`** — Module that routes log entries to multiple `TLogRoute` targets.

- **`TLogRoute`** — Abstract base for log outputs; subclass and implement `processLogs()`.

- **`TParameterModule`** — Stores named configuration parameters.

- **`TDbParameterModule`** — DB-backed parameter store; supports auto-capture of parameter changes.

- **`TPluginModule`** — Base class for Prado 4 composer-based extensions.

- **`TDbPluginModule`** — Plugin module with a database connection.

- **`TJsonRpcClient`** — JSON-RPC 2.0 client.

- **`TXmlRpcClient`** — XML-RPC client.

- **`TRpcClient`** — Base RPC implementation.

- **`TVarDumper`** — Human-readable variable dump for `TComponent` objects.

- **`TDataFieldAccessor`** — Dot-notation property path accessor (e.g., `"User.Profile.Name"`).

- **`TSignalsDispatcher`** — Singleton POSIX signal dispatcher; translates OS signals into `fx*` global events.

- **`TSignalParameter`** — Event parameter for signal events.

- **`TUtf8Converter`** — UTF-8 encoding conversions.

- **`TSimpleDateFormatter`** — Non-locale date formatting.

- **`TClassBehaviorEventParameter`** — Event parameter when a class behavior is attached/detached.
