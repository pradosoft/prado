# Web/Services/Rest/TRestRoutePattern

### Directories
[framework](../../../INDEX.md) / [Web](../../INDEX.md) / [Services](../INDEX.md) / [Rest](INDEX.md) / **`TRestRoutePattern`**

## Class Info
**Location:** `framework/Web/Services/Rest/TRestRoutePattern.php`
**Namespace:** `Prado\Web\Services\Rest`
**Extends:** [`TUrlMappingPattern`](../../TUrlMappingPattern.md)
**Since:** 4.4.0

## Overview
One route of `TRestService`: the pattern (already under `BasePath`), the handler `Resource` class and `Method`, a `Name`, the resource properties of its declaration, and its authorization rules. The pattern syntax, `Parameters`, `Query`, `Verbs`, `CaseSensitive` and `Constants` are inherited from the URL manager pattern. The constructor takes an optional `TUrlManager`, since a service route has none.

## Differences from TUrlMappingPattern
- `init()` compiles placeholders: `{name:regex}` moves the regex into `Parameters` and the pattern becomes `{name}`; a placeholder without a constraint gets `DEFAULT_CONSTRAINT` (`[^/]+`); a repeated name throws `restroute_parameter_duplicate`; a malformed one throws `restroute_pattern_invalid`. `ServiceParameter` defaults to `Name`.
- `substituteParameters()` wraps each constraint in `(?:…)`, so `\d+|new` stays inside its placeholder.
- `matchPath($request): ?array` — the path match (and `Query` constraints) apart from the verb; returns the placeholder values plus constants, or `null`.
- `acceptsVerb($verb): bool` — the inherited `Verbs` rule.
- `constructPath($parameters, $getItems, $encodeAmpersand): string` — the path with values substituted (raw-URL-encoded) and a query string from `$getItems`; a missing value throws `restroute_parameter_missing`. `constructUrl()` is overridden to split GET items into placeholders and query.
- `getParameterNames()` — placeholder names in pattern order.

## Handler properties
`Resource`, `Method`, `Name` (defaults to the pattern), `ResourceProperties`, `getAuthorizationRules()` / `addAuthorizationRule()`. `setVerbs()` uppercases a string value.

## Gotchas
- The match subject is `trim(PathInfo, '/') . '/'`, so a trailing slash never matters.
- `UrlFormat` stays `Get`; the `Path` format's `urlparams` suffix is not used for REST routes.
