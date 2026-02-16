# Events (trait)

Overview:
`Events` provides a simple listener registry for any class using it.

Use the `Events` trait to add class-local event hooks to your own modules, enabling plugin-like extension points without external dependencies.

Public API:
- `::on($name, callable $listener)` adds a listener.
- `::onSingle($name, callable $listener)` replaces listeners.
- `::off($name, callable $listener = null)` removes listeners.
- `::alias($source, $alias)` creates an alias key.
- `::trigger($name, ...$args)` dispatches listeners.
- `::triggerOnce($name, ...$args)` dispatches once with optional arguments and clears.
