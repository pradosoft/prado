# Web/Behaviors/INDEX.md

### Directories
[framework](./INDEX.md) / [Web](./Web/INDEX.md) / **`Behaviors/INDEX.md`**

## Purpose

Behaviors that modify or extend objects in the `framework/Web/` layer.

## Classes

- **[TRequestConnectionUpgrade](Web/Behaviors/TRequestConnectionUpgrade.md)** — Handles HTTP `Connection: Upgrade` requests (e.g., WebSocket handshake). Responds with HTTP 101 Switching Protocols. Attach to `THttpRequest` or `TApplication` to intercept upgrade requests before normal request processing.
