# Request


Overview:
`Request` wraps access to request data and headers and provides content negotiation support.

Public API:
- `Request::accept($key = 'type', $choices = '')` negotiates Accept headers.
- `Request::input($key = null, $default = null)` reads from `$_REQUEST`.
- `Request::env($key = null, $default = null)` reads from `$_ENV`.
- `Request::server($key = null, $default = null)` reads from `$_SERVER`.
- `Request::post($key = null, $default = null)` reads from `$_POST`.
- `Request::get($key = null, $default = null)` reads from `$_GET`.
- `Request::files($key = null, $default = null)` reads from `$_FILES`.
- `Request::cookie($key = null, $default = null)` reads from `$_COOKIE`.
- `Request::host($with_protocol = true)` returns host.
- `Request::URL()` returns full URL.
- `Request::header($key = null, $default = null)` reads headers.
- `Request::URI()` returns request URI.
- `Request::baseURI()` returns front controller base path.
- `Request::method()` returns HTTP method.
- `Request::IP()` returns client IP.
- `Request::UA()` returns user agent.
- `Request::data($key = null, $default = null)` returns parsed body or raw input.

Example:
```php
if (Request::method() === 'post') {
  $body = Request::data();
}
```

Handles the HTTP request for the current execution.

### Getting an input parameter
---

Inputs passed to the request can be retrieved with the `Request::input($key=null, $default=null)` method.

The function searches for an input named `$key` in the `$_REQUEST` superglobal, if not found returns the `$default` value passed (resolved if `$default` is callable).

If you call `Request::input()` it will returns an associative array of all `$_REQUEST` content.

`$_GET`, `$_POST`, `$_FILES`, `$_COOKIE` can be accessed directly with the `Request::get/post/files/cookie` methods.
 
```php
echo "Hello, ", Request::input('name','Friend'), '!';
```

```
GET /?name=Alyx
```
```
Hello, Alyx!
```

### Getting the URL / URI
---

The `Request::URL()` method returns the current request URL, complete with host and protocol.

The `Request::URI()` method returns the current request URL, without host and protocol and relative to the front controller path.

```
DocumentRoot : /web/mysite.com/public
Front Controller Path : /web/mysite.com/public/foo/bar/index.php

Request::URL() –> http://mysite.com/foo/bar/someroute
Request::URI() –> /someroute
```

### Getting the HTTP method
---

The `Request::method()` method returns the current request HTTP method, lowercase.

```php
echo Request::method();
```

```
get
```

### Getting RAW/JSON data
---

If data was passed with the request, the method `Request::data($key=null, $default=null)` will retrieve all (if called with no parameters) data or a single property if `$key` is passed.

If requested data was empty, `$default` will be returned (resolved if callable is passed).

If request data is passed with the `Content-Type: application/json` header, will be automatically decoded.

```bash
POST /
Content-Type: application/json

{
 "name": "Chell"
}
```

```php
print_r( Request::data() );
```

```php
stdClass Object
(
    [name] => Chell
)
```

Handles the HTTP [request](./request.md) for the current execution.

