# Job

Overview:
`Job` is a simple database-backed queue built on `Model`.

Use `Job` for database-backed background work such as email delivery, retries, and async processing that can be executed outside the request cycle.

Key behavior:
- The queue table schema is documented in the class file.
- Jobs are executed by type via event callbacks.

Public API:
- `Job::queue($type, $payload = null, $when = null)` creates a job.
- `Job::register($type, $callback)` registers a worker for a type.
- `Job::cleanQueue($all = false)` deletes completed jobs.
- `Job::execute()` dequeues and runs a pending job.
- `Job::run()` runs the current job instance.
- `Job::error($message = null)` marks job as error.
- `Job::retry($message = null)` resets to pending.

Example:
```php
Job::register('email', function (Job $job, $payload) {
  Email::send($payload);
});
Job::queue('email', ['to' => 'user@example.com']);
Job::execute();
```
