# Interop

Core ships in-repo PSR-like adapters under `classes/Interop` without adding runtime dependencies.

Available contracts:
- `Interop\\HttpRequestLike`
- `Interop\\HttpResponseLike`
- `Interop\\ContainerLike`
- `Interop\\MiddlewareLike`
- `Interop\\RequestHandlerLike`

Available adapters:
- `Interop\\CoreRequestAdapter`
- `Interop\\CoreResponseAdapter`
- `Interop\\CoreServiceContainerAdapter`
- `Interop\\CoreMiddlewarePipelineAdapter`

Validation:
```bash
vendor/bin/phpunit --filter InteropTest
```
