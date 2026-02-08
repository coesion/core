# Filters (trait)

Overview:
`Filters` enables value transformation hooks identified by a name string.

Use the `Filters` trait when building classes that should expose overridable value pipelines via named filter hooks.

Public API:
- `::filter($names, callable $modder = null)` registers one or more filters.
- `::filterSingle($name, callable $modder)` replaces filters.
- `::filterRemove($name, callable $modder = null)` removes filters.
- `::filterWith($names, $default, ...$args)` applies the first available filter chain.

Example:
```php
Filter::add('core.redirect', function ($url) {
  return rtrim($url, '/');
});
```
