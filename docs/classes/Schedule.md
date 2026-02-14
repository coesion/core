# Schedule

Overview:
`Schedule` provides cron-based task scheduling built on top of the `Job` queue. Register recurring tasks with cron expressions and run due tasks with a single call.

Key behavior:
- Jobs are registered in-memory with a name, cron expression, Job type, and optional payload.
- `Schedule::due()` evaluates all registered cron expressions against the current (or given) time.
- `Schedule::run()` queues all due jobs via `Job::queue()`.
- Cron expressions support wildcards, ranges, steps, and lists.

Public API:
- `Schedule::register($name, $cron, $type, $payload = null)` — register a recurring job.
- `Schedule::unregister($name)` — remove a scheduled job.
- `Schedule::due($time = null)` — return jobs whose cron expression matches now.
- `Schedule::run($time = null)` — execute all due jobs via `Job::queue()`.
- `Schedule::matches($cron, $time)` — test a cron expression against a timestamp.
- `Schedule::all()` — get all registered jobs.
- `Schedule::flush()` — clear all registered jobs.

Cron format: `minute hour day month weekday`
- `*` — any value
- `5` — exact value
- `1-5` — range
- `1,3,5` — list
- `*/5` — step (every 5)
- `1-10/2` — range with step

Example:
```php
// Register jobs
Schedule::register('cleanup', '0 2 * * *', 'db.cleanup');
Schedule::register('reports', '0 9 * * 1', 'email.weekly', ['to' => 'admin@example.com']);
Schedule::register('heartbeat', '*/5 * * * *', 'system.ping');

// Register job handlers
Job::register('db.cleanup', function($job, $payload) {
    SQL::exec("DELETE FROM logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
});

// In your cron runner (called every minute):
Schedule::run();
```
