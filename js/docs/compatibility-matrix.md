# Core JS Compatibility Matrix

Status legend:
- `parity`
- `parity-with-note`
- `diverged-by-rfc`
- `not-implemented`

| Class | Status | Notes |
|---|---|---|
| Module | parity | Runtime extension support via `extend`. |
| Loader | parity-with-note | Resolution model is Node module-based. |
| Options | parity | Dot-path set/get/merge + loaders. |
| Errors | parity-with-note | Process-level capture semantics differ from PHP runtime hooks. |
| Introspect | parity | Class/method/extension/routes/capabilities implemented. |
| Request | parity-with-note | Request state is explicit via `Request.set` in runtime/tests. |
| Response | parity | Body/status/headers/push implemented. |
| Route/RouteGroup | parity-with-note | No direct echoed text model; callback output is normalized through `Response`. |
| SQL/Schema/Model | parity-with-note | In-memory adapter currently default; real DB adapters pending. |
| Session/Message | parity | Session map and flash-message semantics implemented. |
| Cache | parity-with-note | Memory/files drivers implemented; Redis pending. |
| Auth/CSRF/Gate | parity-with-note | Session+bearer flow implemented; deeper provider hooks pending. |
| RateLimiter | parity | Windowed in-memory limit checks implemented. |
| SecurityHeaders | parity | Default security headers applied on response. |
| Token | parity | HS256 JWT encode/decode with matching known vector. |
| i18n | parity-with-note | JSON loader complete; PHP file loader intentionally deferred. |
| File | parity-with-note | Native+memory mounts implemented; advanced adapters pending. |
| Schedule | parity | Cron parser/matcher and due selection implemented. |
| Work/TaskCoroutine | parity | Cooperative coroutine scheduler implemented. |
| Text | parity | Render/slugify/accent/cut implemented. |
| URL | parity | Parse/build/query semantics covered by tests. |
| Hash | parity-with-note | Murmur/UUID/random implemented; full legacy hash variants may still expand. |
| Password | parity | Scrypt-based make/verify plus constant-time compare. |
| HTTP/HTTP_Request/HTTP_Response | parity-with-note | Header/user-agent/proxy and string-cast contracts implemented; full client features pending. |
| CLI | parity | Command registration and metadata API implemented. |
| Service | parity | Singleton and factory registration patterns implemented. |
| Dictionary/Map/Structure | parity | Dot-path access/merge/fetch behavior implemented. |
| API/REST/Resource/Collection | parity-with-note | Resource projection and REST exposure implemented; SQL-backed API integration continues to expand. |
| SQL SQLite adapter | parity-with-note | Node `node:sqlite` adapter supported when runtime provides it; memory adapter remains default fallback. |
