<?php

namespace CLI\UI;

class Style {
  private Terminal $terminal;

  private const ANSI16 = [
    'black' => 30,
    'red' => 31,
    'green' => 32,
    'yellow' => 33,
    'blue' => 34,
    'magenta' => 35,
    'cyan' => 36,
    'white' => 37,
    'gray' => 90,
    'lightred' => 91,
    'lightgreen' => 92,
    'lightyellow' => 93,
    'lightblue' => 94,
    'lightmagenta' => 95,
    'lightcyan' => 96,
    'lightwhite' => 97,
  ];

  public function __construct(?Terminal $terminal = null) {
    $this->terminal = $terminal ?? new Terminal();
  }

  public function apply(string $text, array $opts = []): string {
    if (!$this->terminal->isTTY()) return $text;

    $codes = [];
    if (!empty($opts['bold'])) $codes[] = '1';
    if (!empty($opts['dim'])) $codes[] = '2';
    if (!empty($opts['italic'])) $codes[] = '3';
    if (!empty($opts['underline'])) $codes[] = '4';

    if (!empty($opts['fg'])) {
      $codes[] = $this->colorCode((string)$opts['fg'], false);
    }
    if (!empty($opts['bg'])) {
      $codes[] = $this->colorCode((string)$opts['bg'], true);
    }

    $prefix = $codes ? "\033[" . implode(';', $codes) . "m" : '';
    $suffix = $codes ? "\033[0m" : '';

    return $prefix . $text . $suffix;
  }

  public function pad(string $text, int $left = 0, int $right = 0, int $top = 0, int $bottom = 0): string {
    $lines = explode("\n", $text);
    $padded = [];
    $line_pad = str_repeat(' ', max(0, $left));
    $right_pad = str_repeat(' ', max(0, $right));
    foreach ($lines as $line) {
      $padded[] = $line_pad . $line . $right_pad;
    }
    $top_pad = array_fill(0, max(0, $top), '');
    $bottom_pad = array_fill(0, max(0, $bottom), '');
    return implode("\n", array_merge($top_pad, $padded, $bottom_pad));
  }

  public function card(string $title, string $body, array $opts = []): string {
    $padding = (int)($opts['padding'] ?? 1);
    $border = $opts['border'] ?? true;
    $theme = $opts['theme'] ?? 'lipgloss';
    $title_style = $opts['title_style'] ?? ($theme === 'lipgloss'
      ? ['bold' => true, 'fg' => '#a78bfa']
      : ['bold' => true, 'fg' => '#93c5fd']);
    $border_style = $opts['border_style'] ?? ($theme === 'lipgloss'
      ? ['fg' => '#94a3b8']
      : ['fg' => '#64748b']);

    $styled_title = $title !== '' ? $this->apply($title, $title_style) : '';
    $content = $body;
    if ($styled_title !== '') {
      $content = $styled_title . "\n" . $body;
    }
    $content = str_replace(["\r\n", "\r"], "\n", $content);
    $content = $this->pad($content, $padding, $padding, $padding, $padding);

    if (!$border) return $content;

    $lines = explode("\n", $content);
    $max_len = 0;
    foreach ($lines as $line) {
      $len = $this->visibleWidth($line);
      if ($len > $max_len) $max_len = $len;
    }
    $border_chars = $this->borderChars($opts['border'] ?? 'rounded');
    $top = $border_chars['tl'] . str_repeat($border_chars['h'], $max_len + 2) . $border_chars['tr'];
    $bottom = $border_chars['bl'] . str_repeat($border_chars['h'], $max_len + 2) . $border_chars['br'];
    $boxed = [$this->apply($top, $border_style)];
    foreach ($lines as $line) {
      $pad = $max_len - $this->visibleWidth($line);
      $boxed[] = $this->apply($border_chars['v'] . ' ', $border_style)
        . $line
        . str_repeat(' ', $pad)
        . $this->apply(' ' . $border_chars['v'], $border_style);
    }
    $boxed[] = $this->apply($bottom, $border_style);
    return implode("\n", $boxed);
  }

  private function stripAnsi(string $text): string {
    return preg_replace('/\x1b\[[0-9;]*m/', '', $text);
  }

  private function visibleWidth(string $text): int {
    $clean = $this->stripAnsi($text);
    if (function_exists('mb_strwidth')) {
      return (int)mb_strwidth($clean);
    }
    if (function_exists('mb_strlen')) {
      return (int)mb_strlen($clean);
    }
    return strlen($clean);
  }

  private function borderChars(string $border): array {
    if ($border === 'rounded') {
      return [
        'tl' => '╭',
        'tr' => '╮',
        'bl' => '╰',
        'br' => '╯',
        'h' => '─',
        'v' => '│',
      ];
    }
    return [
      'tl' => '+',
      'tr' => '+',
      'bl' => '+',
      'br' => '+',
      'h' => '-',
      'v' => '|',
    ];
  }

  private function colorCode(string $value, bool $background): string {
    $profile = $this->terminal->colorProfile();
    $value = strtolower($value);

    if ($profile === 'truecolor' && preg_match('/^#?([0-9a-f]{6})$/i', $value, $m)) {
      $hex = $m[1];
      $r = hexdec(substr($hex, 0, 2));
      $g = hexdec(substr($hex, 2, 2));
      $b = hexdec(substr($hex, 4, 2));
      return ($background ? '48' : '38') . ";2;$r;$g;$b";
    }

    if (is_numeric($value) && $profile !== '16') {
      $idx = (int)$value;
      $idx = max(0, min(255, $idx));
      return ($background ? '48' : '38') . ";5;$idx";
    }

    if (isset(self::ANSI16[$value])) {
      $base = self::ANSI16[$value];
      return (string)($background ? $base + 10 : $base);
    }

    return $background ? '49' : '39';
  }
}
