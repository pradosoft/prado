# Web/Services/ServiceWorker/SUMMARY.md

Browser service workers of the application, configured by one module; since 4.4.0.

## Classes

- **`TServiceWorkerModule`** — Configuration point: links or registers the service, defines the `default` and named workers, collects contributors, selects a page's worker by scope, adds `TServiceWorker` and `TWebManifest` to pages, `ClearSiteDataOnLogout`.
- **`TServiceWorkerService`** — Serves worker scripts, manifests, and contributor requests at `?sw=`.
- **`TServiceWorkerDefinition`** — One worker's name, scope, contributors, fetch strategies, and manifest.
- **`TServiceWorkerScript`** — Builds a worker script from the runtime and contributor fragments; versioned by hash.
- **`TWebAppManifest`** — Web app manifest members.
- **`IServiceWorkerContributor`** — Adds fragments to worker scripts.
- **`IServiceWorkerRequestHandler`** — Answers `?sw=<name>` requests.
