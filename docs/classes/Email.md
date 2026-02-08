# Email

Overview:
`Email` is an email delivery facade that delegates to a configured driver.

Use `Email` when you need to send notifications through interchangeable providers while keeping envelope creation and send flow identical.

Key behavior:
- Drivers live in the `Email\` namespace.
- Default driver is `Email\Native` as configured at the bottom of the file.
- Sending triggers class-level and global events.

Public API:
- `Email::using($driver, $options = null)` selects a driver and initializes it.
- `Email::create($mail = [])` returns an `Email\Envelope` instance.
- `Email::send($mail)` sends an envelope and returns a boolean.

Events:
- `Email::trigger('send', ...)` and `Event::trigger('core.email.send', ...)` are fired.

Example:
```php
Email::using('native');
Email::send([
  'to' => 'user@example.com',
  'from' => 'noreply@example.com',
  'subject' => 'Hello',
  'message' => 'Welcome',
]);
```

The Email modules will allow you to send email messages via various services providers.

### Choosing the email service
---

You can choose the Email service via the `using` method. The optional second parameter is dictionary of init paramenters to pass to the selected driver.

The default driver is **Native**, the a service that uses the PHP `mail` function.

```php
Email::using('native');
```

Init a service with parameters :

```php
Email::using('SMTP',[
  'host' => 'smtp.starkindustries.com',
  'port' => 25,
  'username' => 'tony',
  'password' => 'pepperpotts',
]);
```

### Sending an email
---

You can send an email with the chosen service via the `Email::send` method.

An associative array must be passed with the email definition.

```php
Email::send([
  'to'       =>  'info@shield.com',
  'from'     =>  'Tony <tony@starkindustries.com>',
  'subject'  =>  'About your proposal...',
  'message'  =>  '<b>NOT</b> interested.',
]);
```

### Sending an email with attachments
---

You can add attachments passing file URLs to the `attachments` key :

```php
Email::send([
  'to'           =>  'Bruce <dr.banner@greenrelax.com>',
  'from'         =>  'Tony <tony@starkindustries.com>',
  'subject'      =>  'Jarvis Code Matrix',
  'message'      =>  "Hey Pea, I'm sending you the AI code matrix of Jarvis.",
  'attachments'  =>  '/usr/home/tony/jarvis.matrix.dat',
]);
```

The `attachments` key also accept an array of multiple file descriptors :

- Local file paths
- URLs
- Raw content

```php
  'attachments'  =>  [
      '/usr/home/tony/jarvis.matrix.dat',
      'http://vignette3.wikia.nocookie.net/marvelcentral/images/6/61/Iron_man_tony_stark_hi_res.jpg',
      [
         'name'    => 'report.csv',
         'content' => "A,B,C,D\n1,2,3,4\n5,6,7,8",
      ],
  ],
```

### Email address formats
---

The `to` and `from` properties accepts one or an array of email addresses in these formats :

1. `user@host.com`
1. `<user@host.com>`
1. `Name Surname <user@host.com>`
