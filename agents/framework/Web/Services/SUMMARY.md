# SUMMARY.md

Application services handling different response types; registered in `application.xml` and activated by `TApplication` based on incoming request.

## Classes

- **`TPageService`** — Primary service serving `.page` template-based web pages; discovers page classes from `BasePath`; runs full `TPage` lifecycle; raises `onPreRunPage`.

- **`TJsonService`** — Returns JSON-formatted responses; configures named JSON response handlers.

- **`TRpcService`** — Generic RPC framework supporting multiple protocols (JSON-RPC, XML-RPC); architecture: `TRpcProtocol`, `TRpcServer`, `TRpcApiProvider`.

- **`TSoapService`** — SOAP/WSDL service; auto-generates WSDL from provider classes using `@soapmethod` PHPDoc annotations.

- **`TFeedService`** — RSS/Atom feed generation; delegates to content provider classes.

- **`TPageConfiguration`** — Parses per-directory `config.xml`/`config.php` files for page-level module loading, authorization rules.
