# SessionReadOnly

Overview:
Read-only accessor for Session values, intended for view rendering.

Use `SessionReadOnly` in rendering contexts that should read session values without allowing accidental writes.

Public API:
- `get($key)` returns `Session::get($key)`.
- `__get($key)` returns `Session::get($key)`.
- `name()` returns `Session::name()`.
- `exists($key)` and `__isset($key)` return `Session::exists($key)`.
