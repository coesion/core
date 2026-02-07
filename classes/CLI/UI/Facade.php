<?php

namespace CLI\UI;

class Facade {
  private Terminal $terminal;
  private Style $style;

  public function __construct(?Terminal $terminal = null) {
    $this->terminal = $terminal ?? new Terminal();
    $this->style = new Style($this->terminal);
  }

  public function input(string $prompt, array $opts = []): string {
    $default = $opts['default'] ?? '';
    $placeholder = $opts['placeholder'] ?? '';
    $label = $this->decoratePrompt($prompt, $default, $placeholder, $opts);

    $scriptedValue = $opts['scripted_value'] ?? ($default !== '' ? $default : ($placeholder !== '' ? $placeholder : 'demo'));
    $line = $this->readLine($label, false, [
      'scripted' => !empty($opts['scripted']),
      'scripted_value' => (string)$scriptedValue,
    ]);
    $line = trim($line);
    return $line === '' ? (string)$default : $line;
  }

  public function confirm(string $prompt, array $opts = []): bool {
    $default = $opts['default'] ?? false;
    $suffix = $default ? '[Y/n]' : '[y/N]';
    $label = $this->decoratePrompt("$prompt $suffix", '', '', $opts);
    $scriptedValue = $opts['scripted_value'] ?? ($default ? 'y' : 'n');
    $line = strtolower(trim($this->readLine($label, false, [
      'scripted' => !empty($opts['scripted']),
      'scripted_value' => (string)$scriptedValue,
    ])));
    if ($line === '') return (bool)$default;
    return in_array($line[0], ['y', '1', 't'], true);
  }

  public function select(string $prompt, array $items, array $opts = []) {
    if (!$items) return $opts['default'] ?? null;
    $list_opts = $opts;
    if (!empty($opts['card'])) {
      $list_opts['card_title'] = $prompt;
      $this->printList('', $items, false, $list_opts);
    } else {
      $this->printList($prompt, $items, true, $opts);
    }

    $default = $opts['default'] ?? null;
    $scriptedValue = $opts['scripted_value'] ?? ($opts['scripted_select'] ?? '2');
    $line = trim($this->readLine($this->decoratePrompt('Select [1-' . count($items) . ']', '', '', $opts + ['card_prompt' => false]), false, [
      'scripted' => !empty($opts['scripted']),
      'scripted_value' => (string)$scriptedValue,
    ]));
    if ($line === '' && $default !== null) return $default;
    $idx = (int)$line;
    if ($idx < 1 || $idx > count($items)) return $items[0];
    return $items[$idx - 1];
  }

  public function multiSelect(string $prompt, array $items, array $opts = []): array {
    if (!$items) return [];
    $list_opts = $opts;
    if (!empty($opts['card'])) {
      $list_opts['card_title'] = $prompt;
      $this->printList('', $items, false, $list_opts);
    } else {
      $this->printList($prompt, $items, true, $opts);
    }

    $scriptedValue = $opts['scripted_value'] ?? ($opts['scripted_select'] ?? '1,3');
    $line = trim($this->readLine($this->decoratePrompt('Select (comma separated)', '', '', $opts + ['card_prompt' => false]), false, [
      'scripted' => !empty($opts['scripted']),
      'scripted_value' => (string)$scriptedValue,
    ]));
    if ($line === '') return (array)($opts['default'] ?? []);
    $parts = array_filter(array_map('trim', explode(',', $line)));
    $out = [];
    foreach ($parts as $part) {
      $idx = (int)$part;
      if ($idx >= 1 && $idx <= count($items)) {
        $out[] = $items[$idx - 1];
      }
    }
    return $out;
  }

  public function filter(string $prompt, array $items, array $opts = []) {
    if (!$items) return $opts['default'] ?? null;
    $scriptedValue = $opts['scripted_value'] ?? 'ap';
    $queryScripted = $opts['scripted_query'] ?? ($opts['scripted_value'] ?? 'ap');
    $query = trim($this->readLine($this->decoratePrompt("$prompt (type to filter)", '', '', $opts + ['card_prompt' => false]), false, [
      'scripted' => !empty($opts['scripted']),
      'scripted_value' => (string)$queryScripted,
    ]));
    $filtered = $this->filterItems($items, $query, $opts['fuzzy'] ?? true);
    if (!$filtered) $filtered = $items;
    $list_opts = $opts;
    if (!empty($opts['scripted_select'])) {
      $list_opts['scripted_value'] = (string)$opts['scripted_select'];
    }
    if (!empty($opts['card'])) {
      $list_opts['card_title'] = 'Filtered results';
    }
    return $this->select('Filtered results', $filtered, $list_opts);
  }

  public function password(string $prompt, array $opts = []): string {
    if (!$this->terminal->isTTY()) {
      return $this->readLine($this->decoratePrompt($prompt, '', '', $opts), false, [
        'scripted' => !empty($opts['scripted']),
        'scripted_value' => (string)($opts['scripted_value'] ?? 'secret'),
      ]);
    }

    if (stripos(PHP_OS_FAMILY, 'Windows') !== false) {
      if (!empty($opts['scripted'])) {
        return $this->readLine($this->decoratePrompt($prompt, '', '', $opts), true, [
          'scripted' => true,
          'scripted_value' => (string)($opts['scripted_value'] ?? 'secret'),
        ]);
      }
      return $this->readPasswordWindows($prompt, $opts);
    }

    return $this->readLine($this->decoratePrompt($prompt, '', '', $opts), true, [
      'scripted' => !empty($opts['scripted']),
      'scripted_value' => (string)($opts['scripted_value'] ?? 'secret'),
    ]);
  }

  public function file(string $prompt, array $opts = []): string {
    $root = $opts['root'] ?? getcwd();
    $root = $root ?: getcwd();
    $current = realpath($root) ?: $root;
    $scripted = !empty($opts['scripted']);

    while (true) {
      if (empty($opts['card'])) {
        $this->printHeader($prompt . ' (' . $current . ')', $opts);
      }
      $entries = $this->listDir($current);
      $choices = array_merge(['..'], $entries);
      $list_opts = $opts;
      if (!empty($opts['card'])) {
        $list_opts['card_title'] = $prompt . ' (' . $current . ')';
      }
      $this->printList('', $choices, false, $list_opts);
      $scriptedValue = $opts['scripted_value'] ?? '';
      if ($scripted) {
        if ($scriptedValue === '' || $scriptedValue === '1') {
          $scriptedValue = (string)$this->firstNonDotIndex($choices);
        }
      }
      $line = trim($this->readLine($this->decoratePrompt('Select', '', '', $opts + ['card_prompt' => false]), false, [
        'scripted' => $scripted,
        'scripted_value' => (string)$scriptedValue,
      ]));
      $idx = (int)$line;
      if ($idx < 1 || $idx > count($choices)) continue;
      $picked = $choices[$idx - 1];
      if ($picked === '..') {
        if ($scripted) {
          return $current;
        }
        $parent = dirname($current);
        $current = $parent ?: $current;
        continue;
      }
      $path = $current . DIRECTORY_SEPARATOR . $picked;
      if (is_dir($path)) {
        $current = $path;
        continue;
      }
      return $path;
    }
  }

  public function write(string $prompt, array $opts = []): string {
    $preferEditor = $opts['editor'] ?? true;
    $editor = getenv('EDITOR');
    if ($preferEditor && $editor) {
      return \CLI::edit('', $opts['filename'] ?? 'notes.txt');
    }

    $this->printHeader($prompt, $opts);
    echo "Finish with Ctrl+D (Unix) or Ctrl+Z then Enter (Windows).\n";
    if (!empty($opts['scripted'])) {
      $scriptedValue = (string)($opts['scripted_value'] ?? "Line one\nLine two");
      $lines = explode("\n", $scriptedValue);
      foreach ($lines as $line) {
        echo $line . PHP_EOL;
      }
      return $scriptedValue;
    }
    $lines = [];
    while (($line = fgets(STDIN)) !== false) {
      $lines[] = rtrim($line, "\r\n");
    }
    return implode(PHP_EOL, $lines);
  }

  public function style(string $text, array $opts = []): string {
    return $this->style->apply($text, $opts);
  }

  public function join(array $pieces, array $opts = []): string {
    $axis = $opts['axis'] ?? 'vertical';
    $gap = (int)($opts['gap'] ?? 0);
    $glue = $axis === 'horizontal'
      ? str_repeat(' ', max(1, $gap))
      : str_repeat(PHP_EOL, max(1, $gap));
    return implode($glue, $pieces);
  }

  private function decoratePrompt(string $prompt, string $default, string $placeholder, array $opts = []): string {
    $label = $prompt;
    if ($default !== '') $label .= ' [' . $default . ']';
    if ($placeholder !== '') $label .= ' (' . $placeholder . ')';
    $label .= ': ';

    if (!empty($opts['inline'])) {
      $label = '> ' . $label;
    }

    if (!empty($opts['accent'])) {
      $label = $this->style->apply($label, ['fg' => $opts['accent'], 'bold' => true]);
    } else if (!empty($opts['inline'])) {
      $label = $this->style->apply($label, ['fg' => '#a78bfa', 'bold' => true]);
    }

    $cardPrompt = !array_key_exists('card_prompt', $opts) ? true : (bool)$opts['card_prompt'];
    if (!empty($opts['card']) && $cardPrompt) {
      $label = $this->style->card($opts['title'] ?? 'Prompt', rtrim($label), [
        'padding' => 1,
        'theme' => $opts['theme'] ?? 'lipgloss',
        'border' => $opts['border'] ?? 'rounded',
      ]) . PHP_EOL;
    }

    return $label;
  }

  private function readLine(string $prompt, bool $silent = false, array $opts = []): string {
    if ($silent && $this->terminal->isTTY()) {
      $this->toggleEcho(false);
    }
    echo $prompt;
    if (!empty($opts['scripted'])) {
      $value = (string)($opts['scripted_value'] ?? '');
      if ($silent && $value !== '') {
        echo str_repeat('*', min(12, strlen($value)));
      } else if ($value !== '') {
        echo $value;
      }
      echo PHP_EOL;
      if ($silent && $this->terminal->isTTY()) {
        $this->toggleEcho(true);
      }
      return $value;
    }
    $line = fgets(STDIN);
    $line = $line === false ? '' : rtrim($line, "\r\n");
    if ($silent && $this->terminal->isTTY()) {
      $this->toggleEcho(true);
      echo PHP_EOL;
    }
    return $line;
  }

  private function toggleEcho(bool $enable): void {
    if (stripos(PHP_OS_FAMILY, 'Windows') !== false) {
      return;
    }
    $cmd = $enable ? 'stty echo' : 'stty -echo';
    @shell_exec($cmd);
  }

  private function readPasswordWindows(string $prompt, array $opts = []): string {
    echo $this->decoratePrompt($prompt, '', '', $opts);
    $script = '$p = Read-Host -AsSecureString;' .
      '$b = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($p);' .
      '[Runtime.InteropServices.Marshal]::PtrToStringAuto($b)';
    $cmd = 'powershell -NoProfile -Command ' . escapeshellarg($script);
    $out = @shell_exec($cmd);
    $out = $out === null ? '' : $out;
    $out = rtrim($out, "\r\n");
    echo PHP_EOL;
    return $out;
  }

  private function printList(string $title, array $items, bool $showTitle = true, array $opts = []): void {
    $content = '';
    if ($showTitle && $title !== '') {
      $content .= $this->style->apply($title, ['fg' => '#93c5fd', 'bold' => true]) . "\n";
    }
    foreach ($items as $idx => $item) {
      $num = $idx + 1;
      $numLabel = $this->style->apply('[' . $num . ']', ['fg' => '#94a3b8']);
      $content .= "  " . $numLabel . " " . $item . "\n";
    }
    $content = rtrim($content, "\r\n");
    if (!empty($opts['card'])) {
      $card_title = $opts['card_title'] ?? ($opts['title'] ?? 'Options');
      echo $this->style->card($card_title, $content, [
        'padding' => 1,
        'theme' => $opts['theme'] ?? 'lipgloss',
        'border' => $opts['border'] ?? 'rounded',
      ]), PHP_EOL;
    } else {
      echo $content, PHP_EOL;
    }
  }

  private function printHeader(string $title, array $opts = []): void {
    if (!empty($opts['card'])) {
      echo $this->style->card($opts['title'] ?? 'Prompt', $title, [
        'padding' => 1,
        'theme' => $opts['theme'] ?? 'lipgloss',
        'border' => $opts['border'] ?? 'rounded',
      ]), PHP_EOL;
    } else {
      echo $this->style->apply($title, ['fg' => '#93c5fd', 'bold' => true]) . PHP_EOL;
    }
  }

  private function filterItems(array $items, string $query, bool $fuzzy): array {
    if ($query === '') return $items;
    $query = strtolower($query);
    $out = [];
    foreach ($items as $item) {
      $value = strtolower((string)$item);
      if ($fuzzy ? $this->fuzzyMatch($query, $value) : (strpos($value, $query) !== false)) {
        $out[] = $item;
      }
    }
    return $out;
  }

  private function fuzzyMatch(string $query, string $value): bool {
    $q = 0;
    $v = 0;
    while ($q < strlen($query) && $v < strlen($value)) {
      if ($query[$q] === $value[$v]) {
        $q++;
      }
      $v++;
    }
    return $q === strlen($query);
  }

  private function listDir(string $path): array {
    $entries = @scandir($path);
    if (!$entries) return [];
    $out = [];
    foreach ($entries as $entry) {
      if ($entry === '.' || $entry === '..') continue;
      $out[] = $entry;
    }
    return $out;
  }

  private function firstNonDotIndex(array $choices): int {
    foreach ($choices as $idx => $name) {
      if ($name !== '..') return $idx + 1;
    }
    return 1;
  }
}
