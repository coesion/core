<?php

namespace CLI\UI;

class Terminal {
  public function isTTY(): bool {
    $forced = $this->env('CORE_CLI_FORCE_TTY');
    if ($forced === '1' || strtolower((string)$forced) === 'true') {
      return true;
    }
    if (function_exists('stream_isatty')) {
      return @stream_isatty(STDIN);
    }
    if (function_exists('posix_isatty')) {
      return @posix_isatty(STDIN);
    }
    return false;
  }

  public function colorProfile(): string {
    $forced = $this->env('CORE_CLI_COLOR');
    if ($forced) return $forced;

    $colorterm = $this->env('COLORTERM');
    if ($colorterm && stripos($colorterm, 'truecolor') !== false) return 'truecolor';

    $term = $this->env('TERM');
    if ($term && stripos($term, '256color') !== false) return '256';

    return '16';
  }

  public function width(): int {
    $cols = (int)trim((string)@shell_exec('tput cols'));
    return $cols > 0 ? $cols : 80;
  }

  public function height(): int {
    $rows = (int)trim((string)@shell_exec('tput lines'));
    return $rows > 0 ? $rows : 24;
  }

  private function env(string $key): ?string {
    $val = getenv($key);
    return $val === false ? null : $val;
  }
}
