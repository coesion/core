# Examples

**Basic Routing**
```php
<?php
require __DIR__ . '/vendor/autoload.php';

Route::on('/', function () {
  return 'Hello from Core!';
});

Route::dispatch();
Response::send();
```

**JSON Response**
```php
Route::get('/health', function () {
  return ['ok' => true, 'time' => time()];
});

Route::dispatch();
Response::send();
```

**Route Middleware**
```php
Route::get('/private', function () {
  return 'secret';
})
->before(function () {
  if (!Session::exists('user_id')) {
    Response::status(401, 'Unauthorized');
    return false;
  }
})
->after(function () {
  Response::header('X-Processed-By', 'Core');
});

Route::dispatch();
Response::send();
```

**View Rendering**
```php
View::using(new MyViewAdapter());
Route::get('/home', function () {
  return View::from('home', ['title' => 'Welcome']);
