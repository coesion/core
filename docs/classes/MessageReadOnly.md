# MessageReadOnly

Overview:
Read-only accessor for Message values, intended for view rendering.

Use `MessageReadOnly` in templates that should consume flash messages safely without exposing mutation methods.

Public API:
- `__get($key)` returns `Message::get($key)`.
- `__isset($key)` always returns true (messages are lazily retrieved).
