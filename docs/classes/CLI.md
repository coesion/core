# CLI


Overview:
`CLI` is a lightweight command router for CLI scripts. It parses argv, supports flags, and dispatches to named command handlers.

Key behavior:
- Commands are registered with `CLI::on()`.
- Options are passed as `--key=value` or `--flag`.
- It provides a help and error handler facility.

Public API:
- `CLI::on($command, callable $callback, $description = '')` registers a command.
- `CLI::help(callable $callback = null)` sets or invokes help.
- `CLI::error(callable $callback = null)` sets or invokes error.
- `CLI::run($args = null)` parses args and dispatches.
- `CLI::input($key = null, $default = null)` retrieves options.
- `CLI::commands()` returns command metadata for help output.
- `CLI::write($message)` and `CLI::writeln($message)` support inline color tags.
- `CLI::color($color)` sets ANSI colors.
- `CLI::edit($text, $filename = '')` opens an editor.
- `CLI::UI()` returns the TUI facade (gum-like prompts and styling).

Example:
```php
CLI::on('greet :name', function ($name) {
  CLI::writeln("Hello $name");
}, 'Print a greeting');
CLI::run();
```

## TUI (CLI::UI)
---

The TUI facade provides gum-like prompts and styling without external dependencies.

Core methods:
- `input($prompt, $opts = [])`
- `confirm($prompt, $opts = [])`
- `select($prompt, array $items, $opts = [])`
- `multiSelect($prompt, array $items, $opts = [])`
- `filter($prompt, array $items, $opts = [])`
- `password($prompt, $opts = [])`
- `file($prompt, $opts = [])`
- `write($prompt, $opts = [])`
- `style($text, $opts = [])`
- `join(array $pieces, $opts = [])`

Example:
```php
$ui = CLI::UI();
$name = $ui->input('Your name', [
  'placeholder' => 'Rick',
  'inline' => true,
  'card' => true,
  'title' => 'Input',
  'theme' => 'lipgloss',
  'border' => 'rounded',
]);

$color = $ui->select('Pick a color', ['Red', 'Green', 'Blue'], [
  'card' => true,
  'card_prompt' => false,
  'title' => 'Select',
  'theme' => 'lipgloss',
  'border' => 'rounded',
]);
```

Scripted and debug options:
- `CORE_CLI_FORCE_TTY=1` forces TTY rendering.
- `CORE_CLI_SCRIPTED=1` auto-fills inputs for non-interactive runs.
- `CORE_CLI_SNAPSHOT=1` stops after rendering prompts in the demo tool.

## CLI TUI Demo
---

Demo tool:
```
php tools/cli-demo.php demo
```

Composer script:
```
composer cli-demo
```

Scripted demo (non-interactive):
```
CORE_CLI_FORCE_TTY=1 CORE_CLI_SCRIPTED=1 CORE_CLI_SNAPSHOT=1 php tools/cli-demo.php demo
```

You can define a command line interface via "command routes".

Similar to the Route module, the CLI module is responsible for this feature.

### Create a simple CLI app
---

Create a new file and give execution permissions:

```bash
$ touch myapp && chmod +x myapp
```

Write this stub into `myapp` file :

```php
#!/usr/bin/env php
<?php
// Load Core and vendors
include 'vendor/autoload.php';

// Define commands routes here...

// Run the CLI dispatcher
CLI::run();
```

### Define a command route
---

CLI routes are defined by whitespace separated fragments.

```php
CLI::on('hello',function(){
  echo "Hello, friend.",PHP_EOL;
});
```

```bash
$ ./myapp hello
Hello, friend.
```

Other "static" parameters, if passed are required for the command execution.

```php
CLI::on('hello friend',function(){
  echo "Hello, friend.",PHP_EOL;
});
```

```bash
$ ./myapp hello
Error: Command [hello] is incomplete.
$ ./myapp hello friend
Hello, friend.
```

You can extract parameter from the route by prefixing the fragment name by a semicolon ":". Extracted fragments are required and will be passed to the route callback by left-to-right position.

```php
CLI::on('hello :name',function($name){
  echo "Hello, $name.",PHP_EOL;
});
```

```bash
$ ./myapp hello
Error: Command [hello] needs more parameters.
$ ./myapp hello "Gordon Freeman"
Hello, Gordon Freeman.
```

### Read options
---

Options are position free parameters, they can be passed everywhere in the command route and are optional.

You can retrieve their value with the `CLI::input($name, $default = null)` method.

```php
CLI::on('process :filename',function($filename){
  $optimize   = CLI::input('optimize',false);
  $outputfile = CLI::input('output',$filename.'.out');
  
  $data = file_get_contents($filename);
  /* process $data */
  if ($optimize) { /* optimize data */ };
  file_put_contents($outputfile,$data);
});
```

```bash
./myapp process --optimize ./test.html --output=test_opt.html
```

If you don't pass an argument for an option `--optimize`, the `true` value will be used.


