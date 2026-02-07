# Events (trait)


Overview:
`Events` provides a simple listener registry for any class using it.

Public API:
- `::on($name, callable $listener)` adds a listener.
- `::onSingle($name, callable $listener)` replaces listeners.
- `::off($name, callable $listener = null)` removes listeners.
- `::alias($source, $alias)` creates an alias key.
- `::trigger($name, ...$args)` dispatches listeners.
- `::triggerOnce($name)` dispatches once and clears.

