# Coesion/Core Framework Overview

This repository provides a lightweight PHP 8.5+ application core with routing, HTTP handling, persistence, configuration, and utilities.
It is designed for small-to-medium services and web apps that need a clear request lifecycle without a full-stack framework.

Usage overview:
- Autoloading: `Loader` registers `classes/` and resolves class names to files.
- Namespaces: optional `Core\` aliases are registered automatically (e.g., `Core\Route`).
- Configuration: `Options` loads configuration from PHP, INI, JSON, arrays, or `.env` files.
- Requests: `Request` reads headers, input, and body; `Negotiation` handles Accept headers.
- Routing: `Route` defines URL patterns and middleware, and `Route::dispatch()` resolves requests.
- Responses: `Response` builds and sends headers, body, and content types.
- Persistence: `SQL`, `Persistence`, and `Model` provide a minimal ORM.
- Events and filters: `Events`, `Event`, `Filters`, and `Filter` enable hooks and overrides.
- API: `API`, `Resource`, `Collection`, and `REST` provide RESTful data exposure.
- Utilities: `Text`, `Hash`, `Token`, `CSV`, `ZIP`, `Shell`, `Work` and others support common tasks.

Typical use cases:
- HTTP APIs and web apps with simple routing and response handling.
- Internal tools or admin panels using `View` for template rendering.
- Jobs and background processing via `Job` and `Work`.
- Data import/export pipelines using `CSV` and `ZIP`.

Performance:
- Autoload optimization: `composer dump-autoload -o`
- Build single-file artifact: `php tools/build-core.php`
- OPcache preload (web/FPM): set `opcache.enable=1`, `opcache.preload=/path/to/dist/core.php`, and `opcache.preload_user=www-data` (or your PHP-FPM user). Optional for partial control: `opcache.file_cache=/path/to/opcache`.

Single-file deploy (optional):
- Build: `php tools/build-core.php`
- Include: `require __DIR__ . '/dist/core.php';`
- Note: `dist/core.php` is generated from framework classes and optimized for preload/distribution.

FrankenPHP:
- Classic mode: works like standard PHP-FPM; no special changes required.
- Worker mode: use `tools/frankenphp-worker.php` and ensure `Response::reset()` is called per request.
- Docker + Caddyfile:
  - Use the official FrankenPHP image and mount your app.
  - Add a Caddyfile route to your public entrypoint.
- Static binary guide: see `docs/guides/FrankenPHP.md`.

Class reference:
- [API](../classes/API.md)
- [REST](../classes/REST.md)
- [Collection](../classes/Collection.md)
- [Resource](../classes/Resource.md)
- [Core](../classes/Core.md)
- [Cache](../classes/Cache.md)
- [Auth](../classes/Auth.md)
- [Check](../classes/Check.md)
- [CLI](../classes/CLI.md)
- [CSV](../classes/CSV.md)
- [CSRF](../classes/CSRF.md)
- [Deferred](../classes/Deferred.md)
- [Dictionary (abstract)](../classes/Dictionary.md)
- [Email](../classes/Email.md)
- [Error (alias)](../classes/Error.md)
- [Errors](../classes/Errors.md)
- [Event](../classes/Event.md)
- [Events (trait)](../classes/Events.md)
- [File](../classes/File.md)
- [Filter](../classes/Filter.md)
- [Filters (trait)](../classes/Filters.md)
- [Hash](../classes/Hash.md)
- [HTTP](../classes/HTTP.md)
- [HTTP_Request](../classes/HTTP_Request.md)
- [HTTP_Response](../classes/HTTP_Response.md)
- [Gate](../classes/Gate.md)
- [Job](../classes/Job.md)
- [Loader](../classes/Loader.md)
- [Map](../classes/Map.md)
- [Message](../classes/Message.md)
- [MessageReadOnly](../classes/MessageReadOnly.md)
- [Migration](../classes/Migration.md)
- [Model (abstract)](../classes/Model.md)
- [Module (trait)](../classes/Module.md)
- [Negotiation](../classes/Negotiation.md)
- [Options](../classes/Options.md)
- [Password](../classes/Password.md)
- [Persistence (trait)](../classes/Persistence.md)
- [Redirect](../classes/Redirect.md)
- [Relation (trait)](../classes/Relation.md)
- [Request](../classes/Request.md)
- [Response](../classes/Response.md)
- [RateLimiter](../classes/RateLimiter.md)
- [Route](../classes/Route.md)
- [RouteGroup](../classes/RouteGroup.md)
- [SecurityHeaders](../classes/SecurityHeaders.md)
- [Service](../classes/Service.md)
- [Session](../classes/Session.md)
- [SessionReadOnly](../classes/SessionReadOnly.md)
- [Shell](../classes/Shell.md)
- [SQL](../classes/SQL.md)
- [SQLConnection](../classes/SQLConnection.md)
- [Structure](../classes/Structure.md)
- [TaskCoroutine](../classes/TaskCoroutine.md)
- [Text](../classes/Text.md)
- [Token](../classes/Token.md)
- [URL](../classes/URL.md)
- [View](../classes/View.md)
- [Work](../classes/Work.md)
- [ZIP](../classes/ZIP.md)

Guides:
- [Installation](Installation.md)
- [Packagist Artifact Publishing](Packagist-Artifact.md)
- [NPM Artifact Publishing](Npm-Artifact.md)
- [Releasing](Releasing.md)
- [Examples](../examples/Examples.md)
- [FrankenPHP](FrankenPHP.md)
- [REST API With Auth](REST-Auth.md)
- [Agentic Audit](Agentic-Audit.md)
- [Agent Snapshot](Agent-Snapshot.md)
- [Agents Quickstart](Agents-Quickstart.md)
- [Agent Use Cases](Agent-Use-Cases.md)
- [Why Core for Agents](Why-Core-for-Agents.md)
- [Interop](Interop.md)
- [Migrations](Migrations.md)
- [Agent KPIs](Agent-KPIs.md)
- [Agent KPI Log](Agent-KPI-Log.md)
- [Proof Weekly Template](Proof-Weekly-Template.md)
- [Distribution Playbook](Distribution-Playbook.md)
- [Router Benchmarks](Router-Benchmarks.md)
- [API](../classes/API.md)
- [REST](../classes/REST.md)
- [Route Cache Extension](../classes/Route-Cache-Extension.md)
- [Classes](Classes.md)


