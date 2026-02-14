<?php

/**
 * Schedule
 *
 * Cron-based task scheduling built on the Job queue.
 *
 * @package core
 * @author Stefano Azzolini <lastguest@gmail.com>
 * @copyright Coesion - 2026
 */

class Schedule {
    use Module;

    protected static $jobs = [];

    /**
     * Register a recurring scheduled job.
     *
     * @param string $name Unique job name
     * @param string $cron Cron expression (minute hour day month weekday)
     * @param string $type The Job type identifier
     * @param mixed $payload Optional payload passed to the job worker
     * @return void
     */
    public static function register($name, $cron, $type, $payload = null) {
        static::$jobs[$name] = [
            'cron'    => $cron,
            'type'    => $type,
            'payload' => $payload,
        ];
    }

    /**
     * Unregister a scheduled job.
     *
     * @param string $name
     * @return void
     */
    public static function unregister($name) {
        unset(static::$jobs[$name]);
    }

    /**
     * Return all registered jobs that are due now.
     *
     * @param int|null $time Unix timestamp to check against, defaults to now
     * @return array Array of job definitions with name, type, payload
     */
    public static function due($time = null) {
        $time = $time ?? time();
        $due = [];

        foreach (static::$jobs as $name => $job) {
            if (static::matches($job['cron'], $time)) {
                $due[] = [
                    'name'    => $name,
                    'type'    => $job['type'],
                    'payload' => $job['payload'],
                ];
            }
        }

        return $due;
    }

    /**
     * Execute all due jobs by queuing them via Job::queue().
     *
     * @param int|null $time Unix timestamp, defaults to now
     * @return array Array of queued job names
     */
    public static function run($time = null) {
        $queued = [];
        foreach (static::due($time) as $job) {
            Job::queue($job['type'], $job['payload']);
            $queued[] = $job['name'];
        }
        return $queued;
    }

    /**
     * Get all registered jobs.
     *
     * @return array
     */
    public static function all() {
        return static::$jobs;
    }

    /**
     * Clear all registered jobs.
     *
     * @return void
     */
    public static function flush() {
        static::$jobs = [];
    }

    /**
     * Check if a cron expression matches a given timestamp.
     *
     * Cron format: minute hour day month weekday
     * Supports: wildcards, specific values, ranges (1-5), steps (e.g. every 5), lists (1,3,5)
     *
     * @param string $cron
     * @param int $time
     * @return bool
     */
    public static function matches($cron, $time) {
        $parts = preg_split('/\s+/', trim($cron));
        if (count($parts) !== 5) return false;

        $checks = [
            (int) date('i', $time), // minute 0-59
            (int) date('G', $time), // hour 0-23
            (int) date('j', $time), // day 1-31
            (int) date('n', $time), // month 1-12
            (int) date('w', $time), // weekday 0-6 (Sunday=0)
        ];

        for ($i = 0; $i < 5; $i++) {
            if (!static::fieldMatches($parts[$i], $checks[$i])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if a single cron field matches a value.
     *
     * @param string $field Cron field expression
     * @param int $value Current time component value
     * @return bool
     */
    protected static function fieldMatches($field, $value) {
        if ($field === '*') return true;

        foreach (explode(',', $field) as $part) {
            // Handle step values: */5 or 1-10/2
            if (strpos($part, '/') !== false) {
                list($range, $step) = explode('/', $part, 2);
                $step = (int) $step;
                if ($step < 1) $step = 1;

                if ($range === '*') {
                    if ($value % $step === 0) return true;
                } elseif (strpos($range, '-') !== false) {
                    list($min, $max) = explode('-', $range, 2);
                    $min = (int) $min;
                    $max = (int) $max;
                    if ($value >= $min && $value <= $max && ($value - $min) % $step === 0) return true;
                }
                continue;
            }

            // Handle ranges: 1-5
            if (strpos($part, '-') !== false) {
                list($min, $max) = explode('-', $part, 2);
                if ($value >= (int) $min && $value <= (int) $max) return true;
                continue;
            }

            // Exact match
            if ((int) $part === $value) return true;
        }

        return false;
    }
}
