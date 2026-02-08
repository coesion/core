<img src="https://github.com/coesion/core/blob/master/docs/assets/core-logo.png?raw=true" height="130">

----

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/coesion/core/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/coesion/core/?branch=master)
[![Build Status](https://travis-ci.org/coesion/core.svg)](https://travis-ci.org/coesion/core)
[![Docs Pages](https://github.com/coesion/core/actions/workflows/docs-pages.yml/badge.svg)](https://github.com/coesion/core/actions/workflows/docs-pages.yml)
[![Total Downloads](https://poser.pugx.org/coesion/core/downloads.svg)](https://packagist.org/packages/coesion/core)
[![Latest Stable Version](https://poser.pugx.org/coesion/core/v/stable.svg)](https://packagist.org/packages/coesion/core)
[![Latest Unstable Version](https://poser.pugx.org/coesion/core/v/unstable.svg)](https://packagist.org/packages/coesion/core)
[![License](https://poser.pugx.org/coesion/core/license.svg)](https://packagist.org/packages/coesion/core)
[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/coesion/core?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)
[![FOSSA Status](https://app.fossa.io/api/projects/git%2Bgithub.com%2Fcoesion%2Fcore.svg?type=shield)](https://app.fossa.io/projects/git%2Bgithub.com%2Fcoesion%2Fcore?ref=badge_shield)


> Coesion/Core is a platform, a collection of components for rapid application development. It doesn't make decisions for you; it gives you tools to build your own solutions.


## Installation

Install via [composer](https://getcomposer.org/download/):

```bash
$ composer require coesion/core
```

## Documentation

See the docs in `docs/guides/README.md`.

Render static docs for GitHub Pages:

```bash
composer docs-build-pages
```

This generates the publishable site in `build/docs-site`.

## Routing

The router supports two execution scenarios controlled by options:

- `core.route.loop_mode` (default `false`): when `true`, routes are treated as immutable and `Route::compile()` should be called once after registration.
- `core.route.loop_dispatcher` (default `fast`): controls the loop-mode dispatcher (`fast` uses compiled static map + regex buckets; `tree` uses the legacy compiled trie).
- `core.route.debug` (default `false`): enables route stats collection and debug output via `Route::stats()` / `Route::debugTree()`.
- `core.route.append_echoed_text` (default `true`): when `false` and no hooks/events are registered, a fast-path skips middleware/events overhead.

Example (loop mode):

```php
Options::set('core.route.loop_mode', true);

Route::get('/hello', function () {
  return 'world';
});

Route::compile();

Route::dispatch('/hello', 'get');
```

## API Module

The API module exposes resources via RESTful endpoints.

Docs:
- `docs/classes/API.md`
- `docs/classes/REST.md`
- `docs/classes/Resource.md`
- `docs/classes/Collection.md`

## Auth/Security Add-on

Core ships a lightweight auth/security add-on with session and bearer token support, CSRF protection, secure headers, and rate limiting.

Docs:
- `docs/classes/Auth.md`
- `docs/classes/Gate.md`
- `docs/classes/Csrf.md`
- `docs/classes/SecurityHeaders.md`
- `docs/classes/RateLimiter.md`

Quick start:
```php
Auth::resolver(function ($identity, $source) {
  return User::find($identity);
});
Auth::boot();
```

## Benchmarks

Benchmark tooling lives in `benchmarks/` with its own `composer.json` and `vendor/`. This keeps the main repository dependency-free.

```bash
cd benchmarks
composer install
php bin/benchmark_router.php
```

## Route Debugging

Use the helper script to inspect the compiled tree and stats.

```bash
php tools/route_debug.php tree
php tools/route_debug.php stats
```


## Contributing

How to get involved:

1. [Star](https://github.com/coesion/core/stargazers) the project!
2. Answer questions that come through [GitHub issues](https://github.com/coesion/core/issues?state=open)
3. [Report a bug](https://github.com/coesion/core/issues/new) that you find

Core follows the [GitFlow branching model](http://nvie.com/posts/a-successful-git-branching-model). The ```master``` branch always reflects a production-ready state while the latest development is taking place in the ```develop``` branch.

Each time you want to work on a fix or a new feature, create a new branch based on the ```develop``` branch: ```git checkout -b BRANCH_NAME develop```. Only pull requests to the ```develop``` branch will be merged.

Pull requests are **highly appreciated**.

Solve a problem. Features are great, but even better is cleaning-up and fixing issues in the code that you discover.

## Versioning

Core is maintained by using the [Semantic Versioning Specification (SemVer)](http://semver.org).


## Copyright and license

Copyright 2026 Coesion under the [MIT license](LICENSE.md).


[![FOSSA Status](https://app.fossa.io/api/projects/git%2Bgithub.com%2Fcoesion%2Fcore.svg?type=large)](https://app.fossa.io/projects/git%2Bgithub.com%2Fcoesion%2Fcore?ref=badge_large)
