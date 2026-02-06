# MessageReadOnly

Overview:
Read-only accessor for Message values, intended for view rendering.

Public API:
- `__get($key)` returns `Message::get($key)`.
- `__isset($key)` always returns true (messages are lazily retrieved).


