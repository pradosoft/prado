# Web/Services/SUMMARY.md

Application services handling different response types; registered in `application.xml` and activated by `TApplication` based on incoming request.

## Classes

- **`TPageService`** — Primary service serving `.page` template-based web pages; discovers page classes from `BasePath`; runs full `TPage` lifecycle; raises `onPreRunPage`.

- **`TJsonService`** — Returns JSON-formatted responses; configures named JSON response handlers.

- **`TSoapService`** — SOAP/WSDL service; auto-generates WSDL from provider classes using `@soapmethod` PHPDoc annotations.

- **`TFeedService`** — RSS/Atom feed generation; delegates to content provider classes.

- **`TPageConfiguration`** — Parses per-directory `config.xml`/`config.php` files for page-level module loading, authorization rules.

RPC (`TRpcService` and the protocols) is the `pradosoft/prado-rpc` package since 4.4.0 (#1210); see [INDEX.md](INDEX.md).
