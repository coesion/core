# FrankenPHP

Overview:
FrankenPHP provides a modern PHP runtime with a built-in web server and optional worker mode for long-lived processes. This guide covers classic mode, worker mode, Docker deployment, and static binaries.

Classic mode
- Works like standard PHP-FPM; your app can run without any framework changes.
- Recommended for the simplest deployments and broad compatibility.

Worker mode
- A single process handles multiple requests. You must reset per-request state.
- In this framework, call `Response::reset()` at the start of each request loop.

Example worker loop:
```php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/tools/frankenphp-worker.php';
```

Docker + Caddyfile
Example `Dockerfile`:
```dockerfile
FROM dunglas/frankenphp:latest
WORKDIR /app
COPY . /app
RUN composer install --no-dev --optimize-autoloader
CMD ["frankenphp", "run", "--config", "/app/Caddyfile"]
```

Example `Caddyfile`:
```caddyfile
:8080 {
  root * /app/public
  php_server
}
```

Worker mode with Caddyfile:
```caddyfile
:8080 {
  root * /app/public
  php_server {
    worker /app/tools/frankenphp-worker.php
  }
}
```

Static binaries (full guide)
1. Build with official tooling:
   - Follow the FrankenPHP static build instructions for your platform.
   - Ensure the PHP extensions you need are compiled in.
2. Bundle your app:
   - Copy your app into the image used by the static build.
   - Or ship the binary and mount your app at runtime.
3. Provide configuration:
   - Use a Caddyfile next to the binary.
   - Include your OPcache/preload settings if needed.
4. Validate:
   - Run the binary locally: `./frankenphp run --config ./Caddyfile`.
   - Confirm requests are handled and `Response::reset()` runs in worker mode.

Notes:
- Worker mode assumes your application bootstrap is safe to run once at startup.
- Avoid storing request-specific data in static state unless you clear it manually.
- Response state is the only state this framework resets automatically in worker mode.
