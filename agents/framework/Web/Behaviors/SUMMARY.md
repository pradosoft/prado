# Web/Behaviors/SUMMARY.md

Behaviors that modify or extend objects in the `framework/Web/` layer.

## Classes

- **`TRequestConnectionUpgrade`** — Handles HTTP `Connection: Upgrade` requests (e.g., WebSocket handshake); responds with HTTP 101 Switching Protocols; attach to `THttpRequest` or `TApplication` to intercept upgrade requests.
