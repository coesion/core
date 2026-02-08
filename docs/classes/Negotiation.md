# Negotiation


Overview:
`Negotiation` parses Accept-style headers and selects preferred or best-matching content types.

Public API:
- `Negotiation::parse($query)` parses an Accept header.
- `Negotiation::bestMatch($acceptables, $choices)` returns best match.
- `new Negotiation($query)` constructs a parser.
- `$neg->preferred()` returns the preferred content type.
- `$neg->best($choices)` returns best match from a list.

Example:
```php
$neg = new Negotiation('text/html, application/json;q=0.9');
$best = $neg->best('application/json, text/plain');
```

The Negotiation is a module for handling Content Negotiation.  
> See Reference : [RFC 7231](https://tools.ietf.org/html/rfc7231)

### Get best match between need and offerings
---

> **Note:** You can use `*` as wildcards for matching a family of choices.

```php
$need  = 'image/*;q=0.9,*/*;q=0.2';
$offer = 'text/html,svg/xml,image/svg+xml';
echo Negotiation::bestMatch($need,$offer);
```

```
image/svg+xml
```

### Preferred and best match
---

Negotiation class automatically orders by priority based on `q` parameter.
 
```php
$negotiatior = new Negotiation('en-US;q=0.3,it,en;q=0.4,es;q=0.9,de');
```

You can obtain the preferred response via the `preferred` method.

```php
echo $negotiatior->preferred();
```

```
it
```

Or get the best match against another RFC7231 query

```php
echo $negotiatior->best('es,en-US');
```

```
es
```

`false` will be returned if no match can be found.

The [Negotiation](./Negotiation.md) is a module for handling Content Negotiation.  
> See Reference : [RFC 7231](https://tools.ietf.org/html/rfc7231)

