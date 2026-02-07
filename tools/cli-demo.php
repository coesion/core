<?php

require __DIR__ . '/../classes/Loader.php';

function cli_demo_is_tty(): bool {
  if (function_exists('stream_isatty')) {
    return @stream_isatty(STDIN);
  }
  if (function_exists('posix_isatty')) {
    return @posix_isatty(STDIN);
  }
  return false;
}

function cli_demo_summary(): void {
  echo "CLI TUI Kitchensink Demo\n";
  echo "Usage:\n";
  echo "  php tools/cli-demo.php demo\n\n";
  echo "This demo requires an interactive TTY to run prompts.\n";
  echo "If you see this message, run it in a terminal.\n\n";
  echo "Planned prompts:\n";
  echo "  input, confirm, select, multiSelect, filter, password, file, write\n";
  echo "Styling:\n";
  echo "  style, join\n";
}

CLI::on('help', function () {
  cli_demo_summary();
}, 'Show help for the CLI demo');

CLI::on('demo', function () {
  if (!method_exists('CLI', 'UI')) {
    echo "CLI::UI() is not available yet. Implement the TUI module first.\n";
    return 1;
  }

  $scripted = CLI::input('scripted') || getenv('CORE_CLI_SCRIPTED') === '1';
  $snapshot = CLI::input('snapshot') || getenv('CORE_CLI_SNAPSHOT') === '1';
  if (!cli_demo_is_tty() && !CLI::input('force-tty') && getenv('CORE_CLI_FORCE_TTY') !== '1' && !$scripted) {
    cli_demo_summary();
    return 0;
  }

  $ui = CLI::UI();

  $name = is_callable([$ui, 'input'])
    ? $ui->input('Your name', ['placeholder' => 'Rick', 'inline' => true, 'card' => true, 'title' => 'Input', 'theme' => 'lipgloss', 'border' => 'rounded', 'scripted' => $scripted, 'scripted_value' => 'Daryl'])
    : 'User';

  $confirm = is_callable([$ui, 'confirm'])
    ? $ui->confirm('Do you want to continue?', ['default' => true, 'inline' => true, 'card' => true, 'title' => 'Confirm', 'theme' => 'lipgloss', 'border' => 'rounded', 'scripted' => $scripted, 'scripted_value' => 'y'])
    : true;

  $color = is_callable([$ui, 'select'])
    ? $ui->select('Pick a color', ['Red', 'Green', 'Blue', 'Yellow'], ['card' => true, 'title' => 'Select', 'theme' => 'lipgloss', 'border' => 'rounded', 'card_prompt' => false, 'inline' => true, 'scripted' => $scripted, 'scripted_value' => '2'])
    : 'Red';

  $tags = is_callable([$ui, 'multiSelect'])
    ? $ui->multiSelect('Pick tags', ['fast', 'safe', 'portable', 'fun'], ['card' => true, 'title' => 'Multi Select', 'theme' => 'lipgloss', 'border' => 'rounded', 'card_prompt' => false, 'inline' => true, 'scripted' => $scripted, 'scripted_value' => '1,3'])
    : ['fast'];

  $filter = is_callable([$ui, 'filter'])
    ? $ui->filter('Filter a fruit', ['apple', 'apricot', 'banana', 'blueberry', 'grape'], ['card' => true, 'title' => 'Filter', 'theme' => 'lipgloss', 'border' => 'rounded', 'card_prompt' => false, 'inline' => true, 'scripted' => $scripted, 'scripted_query' => 'ap', 'scripted_select' => '2'])
    : 'apple';

  $secret = is_callable([$ui, 'password'])
    ? $ui->password('Enter a secret', ['card' => true, 'title' => 'Password', 'inline' => true, 'theme' => 'lipgloss', 'border' => 'rounded', 'scripted' => $scripted, 'scripted_value' => 'secret'])
    : '';

  $file = is_callable([$ui, 'file'])
    ? $ui->file('Pick a file', ['root' => getcwd(), 'card' => true, 'title' => 'File Picker', 'theme' => 'lipgloss', 'border' => 'rounded', 'card_prompt' => false, 'inline' => true, 'scripted' => $scripted, 'scripted_value' => '1'])
    : '';

  $notes = is_callable([$ui, 'write'])
    ? $ui->write('Write notes (Ctrl+D to finish)', ['card' => true, 'title' => 'Write', 'theme' => 'lipgloss', 'border' => 'rounded', 'scripted' => $scripted, 'scripted_value' => "Line one\nLine two"])
    : '';

  if ($snapshot) {
    return 0;
  }

  $lines = [
    "Name: $name",
    "Continue: " . ($confirm ? 'yes' : 'no'),
    "Color: $color",
    "Tags: " . (is_array($tags) ? implode(', ', $tags) : (string)$tags),
    "Filter: $filter",
    "Secret: " . ($secret !== '' ? str_repeat('*', min(8, strlen($secret))) : '(empty)'),
    "File: $file",
    "Notes: " . ($notes !== '' ? substr($notes, 0, 80) . (strlen($notes) > 80 ? '...' : '') : '(empty)'),
  ];

  if (is_callable([$ui, 'join'])) {
    $output = $ui->join($lines, ['axis' => 'vertical', 'gap' => 1]);
  } else {
    $output = implode(PHP_EOL . PHP_EOL, $lines);
  }

  if (is_callable([$ui, 'style'])) {
    $output = $ui->style($output, ['bold' => true, 'fg' => '#22d3ee']);
  }

  echo $output, PHP_EOL;

  return 0;
}, 'Run the CLI TUI kitchensink demo');

CLI::run();
