# Core Framework Audit: Strengths & Weaknesses vs Laravel & Symfony

> **Date:** 2026-02-14
> **Scope:** Coesion/Core PHP framework compared against Laravel and Symfony across all major framework categories.

---

## 1. Feature Capability Matrix

| Category | Core | Laravel | Symfony |
|---|---|---|---|
| Routing | **Strong** | Strong | Strong |
| HTTP Request/Response | **Strong** | Strong | Strong |
| Authentication | **Adequate** | Strong | Adequate |
| Authorization | **Adequate** | Strong | Strong |
| CSRF | **Strong** | Strong | Strong |
| Rate Limiting | **Strong** | Strong | Adequate |
| Security Headers | **Strong** | Basic | Basic |
| ORM / Database | **Basic** | Strong | Strong |
| Query Builder | **Missing** | Strong | Strong |
| Migrations | **Missing** | Strong | Strong |
| Caching | **Adequate** | Strong | Strong |
| Email | **Adequate** | Strong | Adequate |
| Template Engine | **Basic** | Strong | Strong |
| Validation | **Adequate** | Strong | Strong |
| File Storage | **Adequate** | Strong | Adequate |
| CLI / Console | **Adequate** | Strong | Strong |
| Queue / Jobs | **Adequate** | Strong | Strong |
| Events | **Strong** | Strong | Strong |
| i18n / Localization | **Missing** | Strong | Strong |
| Scheduling | **Missing** | Strong | Adequate |
| Testing Utilities | **Missing** | Strong | Strong |
| DI Container | **Basic** | Strong | Strong |
| Code Generation | **Missing** | Strong | Strong |
| WebSocket / Real-time | **Missing** | Adequate | Adequate |
| PSR Compliance | **Missing** | Adequate | Strong |
| Middleware Pipeline | **Basic** | Strong | Strong |
| Form Handling | **Missing** | Adequate | Strong |
| Encryption | **Missing** | Strong | Strong |
| API Resources | **Adequate** | Strong | Adequate |
| Content Negotiation | **Strong** | Basic | Adequate |
| Performance / Footprint | **Strong** | Adequate | Adequate |
| Zero Dependencies | **Strong** | Missing | Missing |

**Legend:**

- **Strong** — Feature-complete or exceeds competitors.
- **Adequate** — Functional but less polished than competitors.
- **Basic** — Minimal implementation, significant gaps vs competitors.
- **Missing** — Not present at all.

---

## 2. Strengths of Core

These are areas where Core wins or matches the competition.

| Strength | Detail |
|---|---|
| **Zero dependencies** | Core has no external runtime dependencies. Laravel pulls ~70 packages, Symfony ~30+ components. Core's single-file dist is unmatched. |
| **Single-file distribution** | `dist/core.php` preload file — no equivalent in Laravel/Symfony. Enables OPcache preloading of entire framework in one shot. |
| **Compiled route dispatcher** | Loop mode with static map + regex buckets gives O(1) static route matching. Comparable to Symfony's compiled URL matcher, ahead of Laravel's default router. Three dispatch strategies: linear scan, optimized trie, and compiled fast dispatcher. |
| **Lightweight ORM** | `Model` class with `Persistence`/`Relation` traits is simple and fast. No migration overhead, no schema abstractions. Convention-based table/key resolution. Direct SQL when needed via PDO convenience layer. |
| **Security out of the box** | `CSRF`, `RateLimiter`, `SecurityHeaders`, `Token` (JWT), `Password` hashing, session hardening — all built-in without pulling separate packages. Laravel has these but requires more config; Symfony requires installing bundles. |
| **Multi-driver architecture** | `Cache` (files/memory), `Email` (native/SMTP/SES), `FileSystem` (native/memory/ZIP), `View` (PHP adapter) — all use adapter pattern. Clean, extensible without bloat. |
| **Module extension system** | Runtime method injection via `Module` trait. Classes can be extended without inheritance at runtime. Unique compared to Laravel's macros (similar concept but different execution). |
| **Content negotiation** | RFC 7231 compliant negotiation via `Negotiation` class, integrated into `Request`. Supports type, language, encoding, charset with quality-value parsing. Laravel requires manual implementation; Symfony has it via HttpFoundation but less integrated. |
| **Minimal learning curve** | ~70 classes total. Laravel has 500+, Symfony 200+ components. Entire API surface is scannable in hours. |
| **Event-driven throughout** | `Event`/`Filter` system woven into routing, HTTP, email, SQL, errors. More pervasive than Laravel's event system for framework internals. Both pub-sub events and value-transforming filters. |
| **CSV handling** | Built-in `CSV` class with auto-delimiter detection, SQL integration, generator-based reading. Neither competitor includes this natively. |

---

## 3. Weaknesses of Core

These are areas where Laravel and/or Symfony clearly win.

| Weakness | Laravel | Symfony | Impact |
|---|---|---|---|
| **No query builder** | Eloquent + fluent query builder | Doctrine DBAL query builder | `SQL` class provides convenience methods (`insert`, `update`, `each`, `single`, `value`) but all WHERE clauses, JOINs, and complex queries require raw SQL strings. High friction for common CRUD. |
| **No migrations** | Artisan migrate system | Doctrine Migrations | No version-controlled schema changes. Manual DB management required. The `Job` class even documents its required table as a SQL comment. |
| **No full DI container** | Full IoC container with auto-injection | DependencyInjection component | `Service` class provides a service locator (singleton + factory registration) but no auto-injection, no constructor resolution, no interface binding. Classes rely on static access patterns. |
| **No template engine** | Blade (compiled, directives, components) | Twig (sandboxed, extensible) | Only raw PHP templates via `View\PHP`. No compiled templates, no component system, no template inheritance syntax. Adapter interface exists for custom engines. |
| **No i18n/localization** | Trans facade, pluralization, JSON lang files | Translation component, ICU support | Zero translation support. Must be built from scratch for multi-language apps. |
| **No task scheduling** | `schedule:run` with cron expressions | Scheduler component (6.3+) | No built-in way to schedule recurring tasks. `Job` supports `scheduled_at` for deferred execution but not periodic recurrence. |
| **No middleware pipeline** | Global + route + group middleware stack with priorities | Kernel listeners, event subscribers | `Route::before()`/`after()` and `RouteGroup` provide per-route and group-level hooks. Auth extends routes with `->auth()`, `->csrf()`, `->rateLimit()`, `->secureHeaders()`. But there is no formal middleware stack with ordering, priorities, or terminable middleware. |
| **No form handling** | Form requests with validation | Form component with CSRF, types | No form builder, no form request objects. Validation exists via `Check` but is decoupled from any form abstraction. |
| **No database seeding** | Seeder classes with factories | Fixtures (DoctrineFixturesBundle) | No structured way to populate test/dev data. |
| **No testing utilities** | PHPUnit integration, Dusk, mocking | PHPUnit bridge, Panther, profiler | No test helpers, HTTP testing, or mocking utilities built-in. |
| **No CLI scaffolding** | `make:model`, `make:controller`, etc. | `make:entity`, `make:controller`, etc. | `CLI` class provides full command routing with ANSI output and TUI helpers, but has no code generation or scaffolding commands. |
| **No Redis/Memcached cache** | Redis, Memcached, DynamoDB, etc. | Redis, Memcached, APCu, etc. | Only file and memory cache drivers. No distributed cache support out of the box. |
| **No WebSocket/broadcasting** | Laravel Echo, Pusher, Reverb | Mercure integration | No real-time features. |
| **No PSR compliance** | PSR-7 via adapters, PSR-11, PSR-15 | PSR-7, PSR-11, PSR-14, PSR-15 | Custom interfaces throughout. Limits interoperability with PSR-compatible middleware and libraries. |
| **No ORM relationship depth** | Eloquent: polymorphic, pivot, eager load, scopes | Doctrine: full relational mapping | Only `hasOne`/`hasMany`. No polymorphic, no many-to-many pivot tables, no eager loading, no query scopes. Relations auto-generate accessor methods but always lazy-load via individual queries. |
| **No encryption** | AES-256-CBC via `Crypt` facade | Sodium-based encryption | `Hash` and `Password` utilities exist for hashing. `Token` handles JWT signing. But no general-purpose symmetric encrypt/decrypt for arbitrary data. |

---

## 4. Competitive Parity

These features exist in Core and match or nearly match the competition.

| Feature | Core implementation | Comparison notes |
|---|---|---|
| **Routing** | `Route` class with parameter extraction, groups, named routes (tags), compiled dispatch, reverse routing via `Route::URL()`. Three dispatch modes: linear, trie, and compiled fast. | On par with both. Compiled dispatcher is competitive with Symfony's URL matcher. |
| **Authentication** | `Auth` class with session + bearer/JWT support. Custom resolvers, auto JWT decode, session regeneration on login/logout. | Simpler than Laravel Sanctum/Passport but functional for most apps. |
| **Authorization** | `Gate` class with `define()`/`allows()` mirroring Laravel's Gate. Route integration via `->can()`. | Covers the core use case. Lacks policies, resource authorization, and `@can` directives. |
| **CSRF protection** | `CSRF` class with token generation, verification, rotation. Route integration via `->csrf()`. | Equivalent to both competitors. |
| **Rate limiting** | `RateLimiter` with fixed-window algorithm, cache backend, automatic headers (`X-RateLimit-*`, `Retry-After`). Route integration via `->rateLimit()`. | Laravel added this in 8.x; Core's is comparable. |
| **Security headers** | `SecurityHeaders` class applying X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy, HSTS, CORP. | Built-in. Neither Laravel nor Symfony ships this by default without packages. |
| **HTTP client** | `HTTP` class wrapping cURL with `get`/`post`/`put`/`delete`, JSON auto-detection, auth, proxy, timeout. | Laravel wraps Guzzle; Symfony has HttpClient. Core's is simpler but functional. |
| **Session management** | `Session` class with secure defaults: strengthened IDs (48 chars), strict mode, HTTP-only, SameSite cookies. Read-only accessor for views. | On par with both frameworks. |
| **Email sending** | `Email` with `Native`/`Smtp`/`Ses`/`Proxy` drivers, attachment support, event hooks. | Comparable to Laravel Mail, ahead of Symfony without Mailer component. |
| **Validation** | `Check` class with pipe-separated rules (`required\|email\|max:100`), custom validators, error messages with placeholders. | Simpler than both but covers common cases. Lacks form-request integration and conditional/nested rules. |
| **Error handling** | Multi-mode error handling (HTML/JSON/silent) via configuration. | Adequate for most use cases. Lacks Symfony's profiler/debug toolbar. |
| **Queue / Jobs** | `Job` class extends `Model` for database-backed persistent queue with `queue()`, `execute()`, `retry()`, `cleanQueue()`, and `scheduled_at` support. `Work` class provides in-memory coroutines for deferred execution. | Database queue is functional. Lacks dedicated worker daemon, multiple queue backends (Redis/SQS), job batching, and rate-limited dispatch. |
| **Service locator** | `Service` class with `register()` (singleton), `registerFactory()`, and magic `__callStatic` access. | Provides basic service location. Not a full DI container but covers the common pattern. |

---

## 5. Summary Scorecard

| Category | Core | Laravel | Symfony |
|---|---|---|---|
| Routing | Strong | Strong | Strong |
| HTTP Request/Response | Strong | Strong | Strong |
| Authentication | Adequate | Strong | Adequate |
| Authorization | Adequate | Strong | Strong |
| CSRF | Strong | Strong | Strong |
| Rate Limiting | Strong | Strong | Adequate |
| Security Headers | Strong | Basic | Basic |
| ORM / Database | Basic | Strong | Strong |
| Query Builder | Missing | Strong | Strong |
| Migrations | Missing | Strong | Strong |
| Caching | Adequate | Strong | Strong |
| Email | Adequate | Strong | Adequate |
| Template Engine | Basic | Strong | Strong |
| Validation | Adequate | Strong | Strong |
| File Storage | Adequate | Strong | Adequate |
| CLI / Console | Adequate | Strong | Strong |
| Queue / Jobs | Adequate | Strong | Strong |
| Events | Strong | Strong | Strong |
| i18n / Localization | Missing | Strong | Strong |
| Scheduling | Missing | Strong | Adequate |
| Testing Utilities | Missing | Strong | Strong |
| DI Container | Basic | Strong | Strong |
| Code Generation | Missing | Strong | Strong |
| WebSocket / Real-time | Missing | Adequate | Adequate |
| PSR Compliance | Missing | Adequate | Strong |
| Middleware Pipeline | Basic | Strong | Strong |
| Form Handling | Missing | Adequate | Strong |
| Encryption | Missing | Strong | Strong |
| API Resources | Adequate | Strong | Adequate |
| Content Negotiation | Strong | Basic | Adequate |
| Performance / Footprint | Strong | Adequate | Adequate |
| Zero Dependencies | Strong | Missing | Missing |

### At a Glance

- **Strong:** 9 categories
- **Adequate:** 9 categories
- **Basic:** 4 categories (ORM, Template Engine, DI Container, Middleware)
- **Missing:** 10 categories

### Core's Niche

Core excels as a **zero-dependency, high-performance micro-framework** for developers who value simplicity, full control, and minimal overhead. It covers the 80% of features needed for typical web applications in a footprint that is orders of magnitude smaller than Laravel or Symfony.

The missing features (query builder, migrations, i18n, DI container, template engine) are precisely the areas where the full-stack frameworks justify their dependency weight. For projects that need these, Core requires either raw implementation or selective integration of third-party libraries.

---

## Appendix: Key Class Reference

| Feature | Core class(es) | File(s) |
|---|---|---|
| Routing | `Route`, `RouteGroup` | `classes/Route.php` |
| Request/Response | `Request`, `Response` | `classes/Request.php`, `classes/Response.php` |
| Authentication | `Auth` | `classes/Auth.php` |
| Authorization | `Gate` | `classes/Gate.php` |
| CSRF | `CSRF` | `classes/CSRF.php` |
| Rate Limiting | `RateLimiter` | `classes/RateLimiter.php` |
| Security Headers | `SecurityHeaders` | `classes/SecurityHeaders.php` |
| ORM | `Model`, `Persistence`, `Relation` | `classes/Model.php`, `classes/Persistence.php`, `classes/Relation.php` |
| Database | `SQL`, `SQLConnection` | `classes/SQL.php` |
| Cache | `Cache`, `Cache\Files`, `Cache\Memory` | `classes/Cache.php`, `classes/Cache/` |
| Email | `Email`, `Email\Smtp`, `Email\Ses`, `Email\Native` | `classes/Email.php`, `classes/Email/` |
| Views | `View`, `View\PHP` | `classes/View.php`, `classes/View/` |
| Validation | `Check` | `classes/Check.php` |
| File Storage | `FileSystem\Native`, `FileSystem\Memory`, `FileSystem\ZIP` | `classes/FileSystem/` |
| CLI | `CLI` | `classes/CLI.php` |
| Queue / Jobs | `Job`, `Work` | `classes/Job.php`, `classes/Work.php` |
| Events | `Event`, `Events` (trait) | `classes/Event.php`, `classes/Events.php` |
| Filters | `Filter`, `Filters` (trait) | `classes/Filter.php`, `classes/Filters.php` |
| Service Locator | `Service` | `classes/Service.php` |
| JWT | `Token` | `classes/Token.php` |
| Hashing | `Hash`, `Password` | `classes/Hash.php`, `classes/Password.php` |
| HTTP Client | `HTTP` | `classes/HTTP.php` |
| Session | `Session` | `classes/Session.php` |
| Content Negotiation | `Negotiation` | `classes/Negotiation.php` |
| CSV | `CSV` | `classes/CSV.php` |
| Module System | `Module` (trait) | `classes/Module.php` |
| Options | `Options` | `classes/Options.php` |
| Text | `Text` | `classes/Text.php` |
