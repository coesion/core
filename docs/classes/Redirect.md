# Redirect

Overview:
`Redirect` provides HTTP redirection helpers using `Response`.

Use `Redirect` to centralize HTTP redirection behavior, including referrer fallback and URL filtering before location output.

Public API:
- `Redirect::to($url, $status = 302)` sends a redirect.
- `Redirect::back()` redirects to the HTTP referrer or `redirect_uri`.
- `Redirect::viaJavaScript($url, $parent = false)` performs JS redirect.

Filters:
- All URLs are passed through `Filter::with('core.redirect', $url)`.

The Redirect module handles request agent redirection to other locations.

### HTTP Redirect
---

A simple `Location` header redirection can be achieved via the `Redirect::to($url)` method. 

```php
if ( ! Session::get('loggedUser') ) Redirect::to('/login');
```

**Warning :**
> The `to` method performs an immediate exit.

### JavaScript Redirect
---

The `Redirect::viaJavaScript($url)` method send to the browser a script for `location` redirection.

```php
Redirect::viaJavaScript('/login');
```

This outputs :

```html
<script>location.href="/login"</script>
```

> If the optional boolean parameter `$parent` is passed as `true` the `parent.location` object is used. This is useful for redirecting inside iframes, like in Facebook Page Tab apps.
