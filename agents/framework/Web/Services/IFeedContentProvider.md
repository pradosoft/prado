# Web/Services/IFeedContentProvider

### Directories
[framework](./INDEX.md) / [Web](./Web/INDEX.md) / [Services](./Web/Services/INDEX.md) / **`IFeedContentProvider`**

**Location:** `framework/Web/Services/IFeedContentProvider.php`
**Namespace:** `Prado\Web\Services`

## Overview
IFeedContentProvider is an interface that must be implemented by feed content provider classes for TFeedService. Implementations provide RSS/Atom feed content with proper XML formatting and content type headers.

## Key Methods

- **`init($config)`** - Initializes the feed provider with configuration from the `<feed>` element in TFeedService configuration. Called before `getFeedContent()`.
- **`getFeedContent()`** - Returns the feed content as a string in proper XML format (RSS 1.0, RSS 2.0, or ATOM).
- **`getContentType()`** - Returns the MIME content type for the feed. Examples: `application/rdf+xml` (RSS 1.0), `application/rss+xml` or `text/xml` (RSS 2.0), `application/atom+xml` (ATOM).

## See Also

- [TFeedService](../TFeedService.md) - The feed service that uses implementations
