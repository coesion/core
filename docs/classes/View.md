# View


Overview:
`View` renders templates using a pluggable adapter and provides `View::from()` factory.

Public API:
- `View::using(View\Adapter $handler)` sets the template handler.
- `new View($template)` creates a view.
- `View::from($template, $data = null)` factory.
- `->with($data)` assigns data.
- `View::exists($templatePath)` checks template.

Example:
```php
View::using(new MyViewAdapter());
echo View::from('home', ['title' => 'Welcome']);
```

The View module handles the rendering of templates via various engines.

Core ships a vanilla PHP template engine (`View\PHP`), you can find other bridges in the [coesion](https://github.com/coesion) repository

### Init view engine
---

You can select a view engine with the `View::using($engine_instance)` method.

```php
View::using(new View\PHP(__DIR__.'/templates'));
```

### Create a view
---

You can create a view object the `View::from($template_name)` factory method.

The `$template_name` is the relative path of the template inside the template directory.

> Extension **must be omitted**, it's automatically handled by the engine.

```php
// Prepares /templates/index.php
$index_page = View::from('index');

// Prepares /templates/errors/404.error.php
$error_page = View::from('errors/404.error');
```

If you need a chain of fallbacks for templates (just like wordpress template hierarchy) pass an array of template names, they will be resolved in a top-bottom sequence.

```php
// Search for /templates/article-{id}.php
// or /templates/article-{slug}.php
// or fallback to /templates/article.php

$article = new Article;

$index_page = View::from([
  "article-{$article->id}",
  "article-{$article->slug}",
  "article",
]);
```

### Rendering a view
---

A view renders itself when casted to a string.

```php
echo View::from('index');
```

### Passing data to a view
---

You can pass data to a view via the `with(array $variables)` method.

```php
echo View::from('index')->with([
  'title' => 'Index page',
  'toc'   => [
     'First',
     'Second',
     'Third',
   ],
]);
```

You can use the passed variables directly in the template (example uses the [twig engine](https://github.com/coesion/twig) )

```html
<h1>{{ title }}</h1>
<ul>
 {% for item in toc %}
   <li>{{ item }}</li>
 {% endfor %}
</ul>
```

Renders

```html
<h1>Index page</h1>
<ul>
   <li>First</li>
   <li>Second</li>
   <li>Third</li>
</ul>
```

### Create a view with parameters shorthand
---

As a shorthand you can pass parameters to the view directly to the `from` factory method.

```php
echo View::from('index',[
  'title' => 'Index page',
]);

The [View](./View.md) module handles the rendering of templates via various engines.

Core ships a vanilla PHP template engine (`View\PHP`), you can find other bridges in the [coesion](https://github.com/coesion) repository

