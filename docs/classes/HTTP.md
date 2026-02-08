# HTTP


Overview:
`HTTP` is a cURL wrapper with helpers for GET/POST/PUT/DELETE and JSON support.

Key behavior:
- Uses a shared user agent and header set.
- Can decode JSON responses automatically by content type.
- SSL verification is enabled by default.

Public API:
- `HTTP::get($url, $data = null, $headers = [], $user = null, $pass = null)`
- `HTTP::post($url, $data = null, $headers = [], $user = null, $pass = null)`
- `HTTP::put($url, $data = null, $headers = [], $user = null, $pass = null)`
- `HTTP::delete($url, $data = null, $headers = [], $user = null, $pass = null)`
- `HTTP::useJSON($value = null)` toggles JSON-encoded body for write methods.
- `HTTP::verify($value = null)` gets or sets SSL verification.
- `HTTP::timeout($seconds = null)` gets or sets request timeout (seconds).
- `HTTP::addHeader($name, $value)` and `HTTP::removeHeader($name)`
- `HTTP::headers($name = null)` reads configured headers.
- `HTTP::userAgent($value = null)` gets or sets UA string.
- `HTTP::proxy($value = false)` gets or sets proxy host:port.
- `HTTP::lastResponseBody()` and `HTTP::lastResponseHeader()`
- `HTTP::info($url = null)` returns cURL info.

Supporting classes:
- `HTTP_Request` models a raw HTTP request and can be cast to string.
- `HTTP_Response` models a response with status, headers, and content.

Example:
```php
HTTP::useJSON(true);
$response = HTTP::post('https://api.example.com/items', ['name' => 'Box']);
```

The HTTP module allow you to make request via cURL.

### Request an URL
---

You can perform an HTTP request (`GET`,`POST`,`PUT`,`DELETE`) on a passed URL via the `HTTP::get/post/put/delete` methods.

```php
$homepage = HTTP::get("http://www.coesion.com");
```

If the response header `Content-Type: application/json` is present, returned payload will be automatically decoded to a PHP object.

```php
$coesion = HTTP::get('http://graph.facebook.com/coesion');
echo $coesion->website;
```

```
http://coesion.com
```

### Passing data to a request
---

You can pass data to the request defining an associative array as the **second** parameter for the call.

```php
HTTP::post("http://api.example.com/user",[
  'name'     => 'Frank',
  'surname'  => 'Castle',
  'email'    => 'punisher@vengeance.com',
]);
```

By default, POST/PUT data is sended via `application/x-www-form-urlencoded`, you can switch to a `application/json` encoding by passing `true` to the `HTTP::useJSON` method.

```php
HTTP::useJSON(true);
HTTP::post("http://api.example.com/user",[
  'name'     => 'Frank',
  'surname'  => 'Castle',
  'email'    => 'punisher@vengeance.com',
]);
```

```
POST http://api.example.com/user
Content-Type: application/json
```
```json
{
  "name": "Frank",
  "surname": "Castle",
  "email": "punisher@vengeance.com"
}
```

### Adding headers
---

You can pass extra headers per request defining an associative array as the **third** parameter for the call.

```php
$results = HTTP::get("http://api.example.com/item",[],[
  'Authorization' => 'Bearer d7033f287da887b1d463830ba48b9982',
]);
```

```
GET http://api.example.com/item
Authorization: Bearer d7033f287da887b1d463830ba48b9982
```

A global header can be appended to **every** request by using the `HTTP::addHeader($name, $value)` method.

```php
HTTP::addHeader('X-Extra','Howdy');
$results = HTTP::get("http://api.example.com/item",[],[
  'Authorization' => 'Bearer d7033f287da887b1d463830ba48b9982',
]);
```

```
GET http://api.example.com/item
X-Extra: Howdy
Authorization: Bearer d7033f287da887b1d463830ba48b9982
```

A global header can be removed via the `HTTP::removeHeader($name)` method.

```php
HTTP::removeHeader('X-Extra');
```

By default the current user-agent is used in all requests :

```
Mozilla/4.0 (compatible; Core::HTTP; Windows NT 6.1)
```

You can however change it with the `HTTP::userAgent($value)` method.

```php
HTTP::userAgent('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/36.0.1985.143 Safari/537.36');
```

