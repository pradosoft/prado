# Web/Behaviors/INDEX.md - WEB_BEHAVIORS_INDEX.md

This file provides guidance to Agents when working with code in this repository.

## Purpose

Behaviors that modify or extend objects in the `framework/Web/` layer.

## Classes

- **`TRequestConnectionUpgrade`** — Handles HTTP `Connection: Upgrade` requests (e.g., WebSocket handshake). Responds with HTTP 101 Switching Protocols. Attach to `THttpRequest` or `TApplication` to intercept upgrade requests before normal request processing.
