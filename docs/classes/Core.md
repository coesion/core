# Core

Overview:
`Core` provides runtime metadata and diagnostics, and a minimal logging hook.

Use `Core` for runtime diagnostics and version metadata in health checks, debug endpoints, and operational tooling.

Public API:
- `Core::version()` returns the framework version string.
- `Core::diagnostics()` returns runtime information (PHP, SAPI, OPcache status).
- `Core::log($level, $message, array $context = [])` triggers `core.log` events.

Notes:
- Namespaced aliases are registered automatically via `Core\Aliases`, so `Core\Route` maps to global `Route` on demand.
