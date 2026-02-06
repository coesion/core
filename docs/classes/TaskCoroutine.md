# TaskCoroutine

Overview:
TaskCoroutine wraps a generator and provides the internal execution model for Work.

Public API:
- `new TaskCoroutine($id, Generator $coroutine)`
- `id()` returns the task ID.
- `pass($value)` provides a value for the next send.
- `run()` advances the generator.
- `complete()` returns true when the generator finishes.


