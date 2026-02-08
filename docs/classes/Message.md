# Message


Overview:
`Message` provides cross-request flash messages stored in `Session` under `core.messages`.

Key behavior:
- Reads messages once and removes them on access.
- `Message::readOnly()` returns a read-only proxy for views.

Public API:
- `Message::get($key, $default = null)` reads and clears a key.
- `Message::set($key, $data = null)` sets a value.
- `Message::add($key, $data = null)` appends to an array.
- `Message::all($key = null)` reads and clears all (or a group).
- `Message::clear()` clears messages.
- `Message::readOnly()` returns a `MessageReadOnly` instance.

Supporting class:
- `MessageReadOnly` exposes `__get` for safe view usage.

Example:
```php
Message::set('notice', 'Saved');
$notice = Message::get('notice');
```

The Message module allow you pass messages between requests.

Getting a message (or all) delete them from session.

```php
Message::add('error',"This is an error.");
Message::add('info',"Testing messages!.");
Message::add('error',"Another error?.");

var_dump(
  Message::all('error'),
  Message::all(),
  Message::all() 
);
```

```
array(2) {
  [0]=> string(17) "This is an error."
  [1]=> string(14) "Another error?"
}
array(1) {
  ["info"]=> array(1) {
    [0]=> string(18) "Testing messages!."
  }
}
array(0) {
}
```

### Add a message
---

Messages must be registered to a container

```php
Message::add('error',"There was an error!");
Message::add('error',"Another one!");
```

### Get all messages
---

Get (and remove from stash) all the messages.

```php
$all_messages = Message::all();

print_r(Message::all());
```

```
false
```

### Get all messages of a kind

You can retrieve only messages of a specified container.

```php
foreach( Message::all('error') as $error ){
  echo "$error\n";
};
```

### Clear all messages

```php
Message::clear();
```

### Using messages in views
---

You can add a read-only accessor as a global view variable.

```php
View::addGlobal('Message', Message::readOnly() );
```

Now you can access messages directly in view templates via the `Message` global.

```html
{% for type,messages in Message.all() %}
  {% for text in messages %}
    <div class="errors {{ type }}">{{ text }}</div>
  {% endfor %}
{% endfor %}
```

The messages are one-shot only, only consumed they are deleted from the stash.

