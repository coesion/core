# URL


Overview:
`URL` is a helper object for parsing and composing URLs.

Public API:
- `new URL($url = '')` constructs an instance.
- Magic `__get` and `__set` allow access to components: `scheme`, `host`, `path`, `query`, `fragment`.
- `__toString()` reassembles the URL.

Example:
```php
$url = new URL('https://example.com/a?b=1');
$url->path = '/c';
echo (string)$url;
```

The URL class gives you a helper for parsing, building and handling URLs.

### Parse an URL
---

Create a new URL object passing the existing URL as constructor parameter, that will be automatically parsed into URL components.

```php
$url = new URL('https://user:pass@www.alpha.beta.com:9080/path/to/resource.html?foo=bar&another[]=2&another[]=3#frag_link');

print_r($url);
```

```
Object
(
    [scheme] => https
    [user] => user
    [pass] => pass
    [host] => www.alpha.beta.com
    [port] => 9080
    [path] => /path/to/resource.html
    [query] => Array
        (
            [foo] => bar
            [another] => Array
                (
                    [0] => 2
                    [1] => 3
                )

        )

    [fragment] => frag_link
)
```

URL class can autocast itself as the builded URL string :

```php
echo "$url";
```

```
https://user:pass@www.alpha.beta.com:9080/path/to/resource.html?foo=bar&another%5B0%5D=2&another%5B1%5D=3#frag_link
```

## Build or modify an URL
---

You can modify or build from scratch an URL altering the single components :

```php
$url = new URL();

$url->host = 'coesion.com';
$url->user = 'demo';

echo $url;
```

```
demo@coesion.com
```

```php
$url = new URL("ftps://test.com:9000/index.php");

$url->scheme = 'https';
$url->port = false;

echo $url;
```

```
https://test.com/index.php
```

## Examples
---

#### Build a mailto address :

```php
$link = new URL('mailto://');
$link->user = 'info';
$link->host = 'myserver.com';

$link->query['subject'] = 'This is a subject';
$link->query['body'] = 'Hi! This is a test... :D';

echo $link;
```

```
mailto://info@myserver.com?subject=This+is+a+subject&body=Hi%21+This+is+a+test...+%3AD
```

