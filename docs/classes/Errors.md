# Errors


Overview:
`Errors` handles PHP errors and exceptions and can output in several modes.

Key behavior:
- `Errors::capture()` installs handlers.
- Errors trigger both class-level and global events; if no handlers respond, the error is printed.

Public API:
- `Errors::capture($tracing_level = null)` installs handlers.
- `Errors::mode($mode = null)` gets or sets output mode.
- `Errors::traceError(...)` and `Errors::traceException(...)` implement handlers.
- Deprecated convenience event methods: `onFatal`, `onWarning`, `onNotice`, `onAny`.

Modes:
- `Errors::SIMPLE`, `Errors::HTML`, `Errors::SILENT`, `Errors::JSON`.

Example:
```php
Errors::mode(Errors::JSON);
Errors::capture(E_ALL);
```

The Errors module allow you catch and manage errors.

### Starting the error handler
---

You can start the error handler via the `capture` method.

```php
Errors::capture();
```

From now on errors are converted to `ErrorExceptions` and routed to the handler which dispatches them in `core.error.*` events, filtered by kind.

You can bind directly to these events via the special `Errors::on*` methods.

```php
Event::on('core.error.warning',function($exception){
  syslog(LOG_WARNING,$exception->getMessage());
});
```

Preferred shortcut :

```php
Errors::onWarning(function($exception){
  syslog(LOG_WARNING,$exception->getMessage());
});
```

These are the error mapping rules:

ErrorType | Gravity | Event | Method
----|------|----|----
`E_NOTICE` | Informational  | `core.error.notice` | `Errors::onNotice`
`E_USER_NOTICE ` | Informational  | `core.error.notice` | `Errors::onNotice`
`E_STRICT ` | Informational  | `core.error.notice` | `Errors::onNotice`
`E_WARNING ` | Warning  | `core.error.warning` | `Errors::onWarning`
`E_USER_WARNING ` | Warning  | `core.error.warning` | `Errors::onWarning`
`E_USER_ERROR ` | Fatal  | `core.error.fatal` | `Errors::onFatal`

Every error will be **also** dispatched via the `core.error` event. You can bind directly to this event via the `Errors::onAny` method.

> If a single error handler returns `true`, the current error will be silenced and not propagated any more.

### Setting the display mode
---

Errors can be displayed with various formats:

Modes | Description | Example
----|------|----
`Errors::SIMPLE` | Prints the error message in plain text  | `Notice: undefined variable x.`
`Errors::HTML` | Prints the error message wrapped in html  | `<pre class="app error"><code>Notice: undefined variable x.</code></pre>`
`Errors::SILENT` | Don't print anything  | 
`Errors::JSON` | Print a JSON string of an error envelope  | `{"error":"Notice: undefined variable x."}` 

```php
Errors::mode(Errors::HTML);
```

The [Errors](./Errors.md) module allow you catch and manage errors.

### Starting the error handler
---

You can start the error handler via the `capture` method.

```php
Errors::capture();
```

From now on errors are converted to `ErrorExceptions` and routed to the handler which dispatches them in `core.error.*` events, filtered by kind.

You can bind directly to these events via the special `Errors::on*` methods.

```php
Event::on('core.error.warning',function($exception){
  syslog(LOG_WARNING,$exception->getMessage());
});
```

Preferred shortcut :

```php
Errors::onWarning(function($exception){
  syslog(LOG_WARNING,$exception->getMessage());
});
```

These are the error mapping rules:

ErrorType | Gravity | Event | Method
----|------|----|----
`E_NOTICE` | Informational  | `core.error.notice` | `Errors::onNotice`
`E_USER_NOTICE ` | Informational  | `core.error.notice` | `Errors::onNotice`
`E_STRICT ` | Informational  | `core.error.notice` | `Errors::onNotice`
`E_WARNING ` | Warning  | `core.error.warning` | `Errors::onWarning`
`E_USER_WARNING ` | Warning  | `core.error.warning` | `Errors::onWarning`
`E_USER_ERROR ` | Fatal  | `core.error.fatal` | `Errors::onFatal`

Every error will be **also** dispatched via the `core.error` event. You can bind directly to this event via the `Errors::onAny` method.

> If a single error handler returns `true`, the current error will be silenced and not propagated any more.

### Setting the display mode
---

Errors can be displayed with various formats:

Modes | Description | Example
----|------|----
`Errors::SIMPLE` | Prints the error message in plain text  | `Notice: undefined variable x.`
`Errors::HTML` | Prints the error message wrapped in html  | `<pre class="app error"><code>Notice: undefined variable x.</code></pre>`
`Errors::SILENT` | Don't print anything  | 
`Errors::JSON` | Print a JSON string of an error envelope  | `{"error":"Notice: undefined variable x."}` 

```php
Errors::mode(Errors::HTML);
```



