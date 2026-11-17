# Web/Behaviors/INDEX.md

### Directories
[framework](../../INDEX.md) / [Web](../INDEX.md) / **`Behaviors`**

## Purpose

Behaviors that modify or extend objects in the `framework/Web/` layer.

## Classes

- **[TRequestConnectionUpgrade](TRequestConnectionUpgrade.md)** — Adds each protocol of the `Upgrade` header of a `Connection: Upgrade` request (e.g., `websocket`) to the URL parameters on `onResolveRequest`, so a service can be selected by the protocol. Attach to `THttpRequest`. It sends no response; the selected service answers the handshake.
