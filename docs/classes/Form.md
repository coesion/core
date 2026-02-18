# Form

Overview:
`Form` handles request-bound validation flows with deterministic output, CSRF checks, and old-input flashing.

Use `Form` when endpoints need one-shot parsing + validation + CSRF verification without repeating glue code between `Request`, `Check`, `CSRF`, and `Session`.

Public API:
- `Form::submit($rules, $options = [])`
- `Form::errors()`
- `Form::old($key = null, $default = null)`
- `Form::flash($data = [])`
- `Form::csrfToken()`
- `Form::csrfField($name = null)`

`submit` options:
- `source`: `input|post|get` (default: `input`)
- `defaults`: default field map merged before validation
- `only`: optional whitelist of accepted fields
- `normalizers`: map of `field => callable($value, $allData)`
- `csrf`: enable/disable CSRF verification
- `csrf_methods`: methods that require CSRF (default: `post|put|patch|delete`)
- `csrf_options`: options forwarded to `CSRF::verify()`
- `flash_on_error`: whether to flash input when invalid
- `flash_key`: session key for flashed old input

Return envelope:
- `valid` (bool)
- `data` (array)
- `errors` (array)
- `csrf.checked` (bool)
- `csrf.valid` (bool)

Example:
```php
$_REQUEST = ['email' => '  test@example.com  '];

$result = Form::submit([
  'email' => 'required|email',
], [
  'csrf' => false,
  'normalizers' => [
    'email' => function ($value) {
      return strtolower(trim((string) $value));
    },
  ],
]);

if (!$result['valid']) {
  var_dump(Form::errors());
}
```
