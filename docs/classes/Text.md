# Text


Overview:
`Text` contains string utilities: simple templating, slugify, and accent removal.

Public API:
- `Text::render($template, $values = null)` renders `{{ var }}` placeholders.
- `Text::slugify($text)` returns a URL-safe slug.
- `Text::removeAccents($text)` transliterates accents.
- `Text::cut($text, $start_tag, $end_tag = null)` extracts a substring.

Example:
```php
echo Text::render('Hello {{ name }}', ['name' => 'Ada']);
```

The Text module contains text related utility.

### Render a string template
---

Fast string templating, it uses a dot notation path for retrieving value.

Values must be enclosed in `{{ }}` double curly braces.

```php
echo Text::render('Your IP is : {{ server.REMOTE_HOST }}',[
  'server' => $_SERVER
]);
```
```
Your IP is : 192.30.252.131
```

The [Text](./Text.md) module contains text related utility.

### Render a string template
---

Fast string templating, it uses a dot notation path for retrieving value.

Values must be enclosed in `{{ }}` double curly braces.

```php
echo Text::render('Your IP is : {{ server.REMOTE_HOST }}',[
  'server' => $_SERVER
]);
```
```
Your IP is : 192.30.252.131
```



