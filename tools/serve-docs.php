<?php

// Doc server + router in one file.

$root = dirname(__DIR__);
$docsDir = $root . DIRECTORY_SEPARATOR . 'docs';
$enableAsciiFlame = getenv('CORE_DOCS_ASCII_FLAME') === '1';

if (PHP_SAPI === 'cli') {
  $args = $argv;
  array_shift($args);
  $port = null;
  $open = true;

  foreach ($args as $arg) {
    if ($arg === '--no-open') {
      $open = false;
    } elseif (strpos($arg, '--port=') === 0) {
      $port = (int)substr($arg, 7);
    } elseif (is_numeric($arg)) {
      $port = (int)$arg;
    }
  }

  if (!$port) {
    $port = 8000;
    while (!isPortFree($port)) {
      $port++;
      if ($port > 9000) {
        fwrite(STDERR, "No free port found between 8000-9000. Use --port.\n");
        exit(1);
      }
    }
  }

  $host = '127.0.0.1';
  $url = "http://$host:$port/";

  $cmd = escapeshellarg(PHP_BINARY) . " -S $host:$port " . escapeshellarg(__FILE__);

  fwrite(STDOUT, "Doc server running at $url\n");
  if ($open) {
    openBrowser($url);
  }

  passthru($cmd);
  exit(0);
}

// Router mode
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';

// Serve static assets from docs/assets
if (strpos($path, '/assets/') === 0) {
  $assetPath = realpath($docsDir . $path);
  if ($assetPath && str_starts_with($assetPath, realpath($docsDir)) && is_file($assetPath)) {
    $mime = mime_content_type($assetPath) ?: 'application/octet-stream';
    header('Content-Type: ' . $mime);
    readfile($assetPath);
    exit;
  }
  http_response_code(404);
  exit;
}

$requested = $_GET['file'] ?? '';
if ($requested === '' && $path !== '/' && $path !== '') {
  $requested = ltrim($path, '/');
}
if ($requested === '') {
  $requested = 'guides/README.md';
}
$filePath = resolveDocPath($docsDir, $requested);
[$classesTree, $traitsTree, $guidesTree] = buildSidebar($docsDir);
$toc = [];

if (!$filePath) {
  http_response_code(404);
  $contentHtml = '<p>Document not found.</p>';
} else {
  $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
  $raw = file_get_contents($filePath);
  if ($ext === 'md') {
    [$contentHtml, $toc] = renderMarkdown($raw, $requested);
  } else {
    $contentHtml = '<pre>' . htmlspecialchars($raw, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</pre>';
  }
}

renderPage($classesTree, $traitsTree, $guidesTree, $requested, $contentHtml, $toc);

function isPortFree(int $port): bool {
  $socket = @stream_socket_server("tcp://127.0.0.1:$port", $errno, $errstr);
  if ($socket) {
    fclose($socket);
    return true;
  }
  return false;
}

function openBrowser(string $url): void {
  if (stripos(PHP_OS_FAMILY, 'Windows') !== false) {
    pclose(popen('start "" ' . escapeshellarg($url), 'r'));
  } elseif (stripos(PHP_OS_FAMILY, 'Darwin') !== false) {
    exec('open ' . escapeshellarg($url));
  } else {
    exec('xdg-open ' . escapeshellarg($url));
  }
}

function resolveDocPath(string $docsDir, string $requested): ?string {
  $requested = str_replace(['\\', "\0"], ['/', ''], $requested);
  $requested = ltrim($requested, '/');
  $candidate = realpath($docsDir . DIRECTORY_SEPARATOR . $requested);
  if (!$candidate) return null;
  $docsRoot = realpath($docsDir);
  if (!str_starts_with($candidate, $docsRoot)) return null;
  if (!is_file($candidate)) return null;
  return $candidate;
}

function buildTree(string $dir, string $base = ''): array {
  $items = [];
  $entries = array_diff(scandir($dir), ['.', '..']);
  sort($entries, SORT_STRING);
  foreach ($entries as $entry) {
    $path = $dir . DIRECTORY_SEPARATOR . $entry;
    $rel = ltrim($base . '/' . $entry, '/');
    if (is_dir($path)) {
      if ($entry === 'assets') continue;
      $items[] = [
        'type' => 'dir',
        'name' => $entry,
        'path' => $rel,
        'children' => buildTree($path, $rel),
      ];
    } else {
      $items[] = [
        'type' => 'file',
        'name' => $entry,
        'path' => $rel,
      ];
    }
  }
  return $items;
}

function buildSidebar(string $docsDir): array {
  $classesDir = $docsDir . DIRECTORY_SEPARATOR . 'classes';
  $classNodes = [];
  if (is_dir($classesDir)) {
    $classNodes = buildTree($classesDir, 'classes');
    $classNodes = array_values(array_filter($classNodes, function ($node) {
      return $node['type'] === 'file';
    }));
  }

  $aliasMap = getClassAliasMap(dirname($docsDir) . DIRECTORY_SEPARATOR . 'classes');
  $traitNames = getTraitBaseNames(dirname($docsDir) . DIRECTORY_SEPARATOR . 'classes');
  $classes = [];
  $traits = [];
  foreach ($classNodes as $node) {
    $base = stripMdExtension($node['name']);
    if (isset($traitNames[$base])) {
      $traits[] = $node;
    } else {
      $classes[] = $node;
    }
  }

  $classes = groupClassNodes($classes, $aliasMap);
  $classes = sortNodes($classes);
  $traits = sortNodes($traits);
  foreach ($traits as &$traitNode) {
    $traitNode['is_trait'] = true;
  }
  unset($traitNode);

  $guidesNodes = [];
  $examplesNodes = [];

  $guidesDir = $docsDir . DIRECTORY_SEPARATOR . 'guides';
  if (is_dir($guidesDir)) {
    $guidesNodes = buildTree($guidesDir, 'guides');
    $guidesNodes = array_values(array_filter($guidesNodes, function ($node) {
      return $node['type'] === 'file' && strtolower($node['name']) !== 'home.md';
    }));
  }

  $examplesDir = $docsDir . DIRECTORY_SEPARATOR . 'examples';
  if (is_dir($examplesDir)) {
    $examplesNodes = buildTree($examplesDir, 'examples');
    $examplesNodes = array_values(array_filter($examplesNodes, function ($node) {
      return $node['type'] === 'file';
    }));
  }

  $guidesNodes = sortNodes($guidesNodes);
  $examplesNodes = sortNodes($examplesNodes);

  $guides = [];
  if ($guidesNodes) {
    $guides[] = [
      'type' => 'dir',
      'name' => 'Guides',
      'path' => 'guides',
      'children' => $guidesNodes,
    ];
  }
  if ($examplesNodes) {
    $guides[] = [
      'type' => 'dir',
      'name' => 'Examples',
      'path' => 'examples',
      'children' => $examplesNodes,
    ];
  }

  return [$classes, $traits, $guides];
}

function sortNodes(array $nodes): array {
  usort($nodes, function ($a, $b) {
    return strcasecmp($a['name'], $b['name']);
  });
  return $nodes;
}

function getTraitBaseNames(string $classesDir): array {
  $traits = [];
  if (!is_dir($classesDir)) {
    return $traits;
  }
  foreach (glob($classesDir . DIRECTORY_SEPARATOR . '*.php') as $file) {
    $content = @file_get_contents($file);
    if ($content === false) {
      continue;
    }
    if (preg_match('/^\s*trait\s+([A-Za-z_][A-Za-z0-9_]*)/m', $content, $m)) {
      $traits[$m[1]] = true;
    }
  }
  return $traits;
}

function getClassAliasMap(string $classesDir): array {
  $aliases = [];
  if (!is_dir($classesDir)) {
    return $aliases;
  }
  foreach (glob($classesDir . DIRECTORY_SEPARATOR . '*.php') as $file) {
    $content = @file_get_contents($file);
    if ($content === false) {
      continue;
    }
    if (preg_match_all('/class_alias\\(\\s*[\'"]([^\'"]+)[\'"]\\s*,\\s*[\'"]([^\'"]+)[\'"]\\s*/i', $content, $matches, PREG_SET_ORDER)) {
      foreach ($matches as $m) {
        $origin = $m[1];
        $alias = $m[2];
        $aliases[$alias] = $origin;
      }
    }
  }
  return $aliases;
}

function renderPage(array $classesTree, array $traitsTree, array $guidesTree, string $current, string $contentHtml, array $toc): void {
  header('Content-Type: text/html; charset=utf-8');
  $title = 'Docs';
  $classesHtml = renderTree($classesTree, $current, true);
  $traitsHtml = renderTree($traitsTree, $current, true);
  $guidesHtml = renderTree($guidesTree, $current, false);
  $tocHtml = renderToc($toc);

  echo '<!doctype html><html><head><meta charset="utf-8">';
  echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
  echo '<title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title>';
  echo '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;600;700&display=swap">';
  echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css">';
  echo '<style>' . getStyles() . '</style>';
  echo '</head><body>';
  echo '<div class="layout">';
  echo '<aside class="sidebar">';
  if ($enableAsciiFlame) {
    echo '<pre id="ascii-flame" class="ascii-flame" aria-hidden="true">' . getAsciiFlame() . '</pre>';
  }
  echo '<div class="sidebar-content"><div class="brand"><img src="/assets/core-logo.png" alt="Core"></div>';
  echo '<div class="nav-section"><div class="section-title">On This Page</div>' . $tocHtml . '</div>';
  echo '<div class="nav-section"><div class="section-title">Classes & Modules</div>' . $classesHtml . '</div>';
  echo '<div class="nav-section"><div class="section-title">Traits</div>' . $traitsHtml . '</div>';
  echo '<div class="nav-section"><div class="section-title">Guides & Examples</div>' . $guidesHtml . '</div>';
  echo '</div></aside>';
  echo '<main class="content">' . $contentHtml . '</main>';
  echo '</div>';
  echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>';
  echo '<script>document.querySelectorAll(\'pre code\').forEach(function(el){if (window.hljs && hljs.highlightElement){hljs.highlightElement(el);}});</script>';
  if ($enableAsciiFlame) {
    echo '<script>' . getAsciiFlameScript() . '</script>';
  }
  echo '</body></html>';
}

function renderTree(array $tree, string $current, bool $decorateClasses): string {
  $html = '<ul>';
  foreach ($tree as $node) {
    if ($node['type'] === 'dir') {
      $html .= '<li class="dir">' . htmlspecialchars($node['name'], ENT_QUOTES, 'UTF-8');
      $children = $node['children'];
      if ($node['name'] === 'classes') {
        $children = groupClassNodes($children);
      }
      $html .= renderTree($children, $current, $decorateClasses);
      $html .= '</li>';
    } else {
      $isActive = $node['path'] === $current;
      $labelText = stripMdExtension($node['name']);
      if ($node['path'] === 'guides/README.md') {
        $labelText = 'Home';
      }
      $label = htmlspecialchars($labelText, ENT_QUOTES, 'UTF-8');
      if ($decorateClasses) {
        $label = '<span class="doc-icon doc-icon--class"><img src="/assets/small_module.png" alt=""></span>' . $label;
        if (!empty($node['is_trait'])) {
          $label .= '<span class="badge badge-trait" title="Trait">∷</span>';
        }
      }
      $url = '?file=' . rawurlencode($node['path']);
      $cls = $isActive ? ' class="active"' : '';
      $html .= "<li><a$cls href=\"$url\">$label</a>";
      if (!empty($node['children'])) {
        $html .= '<ul class="sub">';
        foreach ($node['children'] as $child) {
          $childActive = $child['path'] === $current;
          $childLabelText = stripMdExtension($child['name']);
          if ($child['path'] === 'guides/README.md') {
            $childLabelText = 'Home';
          }
          $childLabel = htmlspecialchars($childLabelText, ENT_QUOTES, 'UTF-8');
          if ($decorateClasses) {
            $childLabel = '<span class="doc-icon doc-icon--class"><img src="/assets/small_module.png" alt=""></span>' . $childLabel;
            if (!empty($child['is_alias'])) {
              $childLabel .= '<span class="badge badge-alias" title="Alias">↪</span>';
            }
          }
          $childUrl = '?file=' . rawurlencode($child['path']);
          $childCls = $childActive ? ' class="active"' : '';
          $html .= "<li><a$childCls href=\"$childUrl\">$childLabel</a></li>";
        }
        $html .= '</ul>';
      }
      $html .= '</li>';
    }
  }
  $html .= '</ul>';
  return $html;
}

function stripMdExtension(string $name): string {
  if (str_ends_with($name, '.md')) {
    return substr($name, 0, -3);
  }
  return $name;
}

function groupClassNodes(array $nodes, array $aliasMap = []): array {
  $files = [];
  $dirs = [];
  foreach ($nodes as $node) {
    if ($node['type'] === 'file') {
      $files[] = $node;
    } else {
      $dirs[] = $node;
    }
  }

  $baseNames = [];
  foreach ($files as $file) {
    $base = stripMdExtension($file['name']);
    $baseNames[$base] = true;
  }

  $childrenMap = [];
  $parents = [];
  foreach ($files as $file) {
    $base = stripMdExtension($file['name']);
    if (isset($aliasMap[$base])) {
      $file['is_alias'] = true;
      $file['alias_of'] = $aliasMap[$base];
      $childrenMap[$aliasMap[$base]][] = $file;
      continue;
    }
    $parent = findParentBase($base, $baseNames);
    if ($parent && $parent !== $base) {
      $childrenMap[$parent][] = $file;
    } else {
      $parents[] = $file;
    }
  }

  foreach ($parents as &$parent) {
    $base = stripMdExtension($parent['name']);
    if (!empty($childrenMap[$base])) {
      $childrenMap[$base] = dedupeChildren($childrenMap[$base]);
      usort($childrenMap[$base], function ($a, $b) {
        return strcasecmp($a['name'], $b['name']);
      });
      $parent['children'] = $childrenMap[$base];
    }
  }
  unset($parent);

  return array_merge($dirs, $parents);
}

function dedupeChildren(array $children): array {
  $seen = [];
  $out = [];
  foreach ($children as $child) {
    $key = $child['path'] ?? $child['name'];
    if (isset($seen[$key])) {
      continue;
    }
    $seen[$key] = true;
    $out[] = $child;
  }
  return $out;
}

function findParentBase(string $name, array $baseNames): ?string {
  if (strpos($name, '_') !== false) {
    $candidate = strtok($name, '_');
    if (isset($baseNames[$candidate])) return $candidate;
  }
  if (strpos($name, '-') !== false) {
    $candidate = strtok($name, '-');
    if (isset($baseNames[$candidate])) return $candidate;
  }

  $best = null;
  $bestLen = 0;
  foreach ($baseNames as $base => $_) {
    if ($base === $name) continue;
    if (str_starts_with($name, $base)) {
      $nextChar = substr($name, strlen($base), 1);
      if ($nextChar !== '' && ctype_upper($nextChar) && strlen($base) > $bestLen) {
        $best = $base;
        $bestLen = strlen($base);
      }
    }
  }
  return $best;
}

function renderMarkdown(string $text, string $currentPath): array {
  $text = str_replace(["\r\n", "\r"], "\n", $text);
  $lines = explode("\n", $text);
  $html = '';
  $inCode = false;
  $codeLang = '';
  $inList = false;
  $inQuote = false;
  $toc = [];

  $count = count($lines);
  for ($i = 0; $i < $count; $i++) {
    $line = $lines[$i];
    if (preg_match('/^```(.*)$/', $line, $m)) {
      if ($inCode) {
        $html .= '</code></pre>';
        $inCode = false;
        $codeLang = '';
      } else {
        if ($inList) { $html .= '</ul>'; $inList = false; }
        $codeLang = trim($m[1]);
        $html .= '<pre><code' . ($codeLang ? ' class="language-' . htmlspecialchars($codeLang, ENT_QUOTES, 'UTF-8') . '"' : '') . '>';
        $inCode = true;
      }
      continue;
    }

    if ($inCode) {
      $html .= htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "\n";
      continue;
    }

    // Table detection: header line + separator line
    if (isTableHeader($line, $lines[$i + 1] ?? '')) {
      if ($inList) { $html .= '</ul>'; $inList = false; }
      [$tableHtml, $newIndex] = renderTable($lines, $i, $currentPath);
      $html .= $tableHtml;
      $i = $newIndex;
      continue;
    }

    if (preg_match('/^(#{1,6})\s+(.*)$/', $line, $m)) {
      if ($inList) { $html .= '</ul>'; $inList = false; }
      if ($inQuote) { $html .= '</blockquote>'; $inQuote = false; }
      $level = strlen($m[1]);
      $titleText = trim($m[2]);
      $id = slugifyHeading($titleText);
      $content = inlineMarkdown($titleText, $currentPath);
      $html .= "<h$level id=\"$id\">$content</h$level>";
      $toc[] = [
        'level' => $level,
        'title' => strip_tags($content),
        'href' => '?file=' . rawurlencode($currentPath) . '#' . rawurlencode($id),
      ];
      continue;
    }

    if (preg_match('/^\s*[-*+]\s+(.*)$/', $line, $m)) {
      if (!$inList) { $html .= '<ul>'; $inList = true; }
      if ($inQuote) { $html .= '</blockquote>'; $inQuote = false; }
      $html .= '<li>' . inlineMarkdown($m[1], $currentPath) . '</li>';
      continue;
    }

    if (preg_match('/^\s*(\*{3,}|-{3,}|_{3,})\s*$/', $line)) {
      if ($inList) { $html .= '</ul>'; $inList = false; }
      if ($inQuote) { $html .= '</blockquote>'; $inQuote = false; }
      $html .= '<hr>';
      continue;
    }

    if (preg_match('/^\s*>\s?(.*)$/', $line, $m)) {
      if ($inList) { $html .= '</ul>'; $inList = false; }
      if (!$inQuote) { $html .= '<blockquote>'; $inQuote = true; }
      $html .= '<p>' . inlineMarkdown($m[1], $currentPath) . '</p>';
      continue;
    }

    if (trim($line) === '') {
      if ($inList) { $html .= '</ul>'; $inList = false; }
      if ($inQuote) { $html .= '</blockquote>'; $inQuote = false; }
      $html .= '<div class="spacer"></div>';
      continue;
    }

    if ($inQuote) {
      $html .= '<p>' . inlineMarkdown($line, $currentPath) . '</p>';
      continue;
    }

    $html .= '<p>' . inlineMarkdown($line, $currentPath) . '</p>';
  }

  if ($inCode) {
    $html .= '</code></pre>';
  }
  if ($inList) {
    $html .= '</ul>';
  }
  if ($inQuote) {
    $html .= '</blockquote>';
  }

  return [$html, $toc];
}

function isTableHeader(string $line, string $next): bool {
  if (strpos($line, '|') === false) return false;
  $next = trim($next);
  if ($next === '') return false;
  // Separator: pipes + dashes + colons + spaces
  return (bool)preg_match('/^[\s\|\-:]+$/', $next) && strpos($next, '-') !== false;
}

function splitTableRow(string $line): array {
  $line = trim($line);
  $line = trim($line, '|');
  $cells = array_map('trim', explode('|', $line));
  return $cells;
}

function renderTable(array $lines, int $startIndex, string $currentPath = ''): array {
  $header = splitTableRow($lines[$startIndex]);
  $i = $startIndex + 2; // skip separator
  $rows = [];
  $count = count($lines);
  for (; $i < $count; $i++) {
    $line = $lines[$i];
    if (trim($line) === '' || strpos($line, '|') === false) break;
    $rows[] = splitTableRow($line);
  }

  $html = '<table><thead><tr>';
  foreach ($header as $cell) {
    $html .= '<th>' . inlineMarkdown($cell, $currentPath) . '</th>';
  }
  $html .= '</tr></thead><tbody>';
  foreach ($rows as $row) {
    $html .= '<tr>';
    foreach ($row as $cell) {
      $html .= '<td>' . inlineMarkdown($cell, $currentPath) . '</td>';
    }
    $html .= '</tr>';
  }
  $html .= '</tbody></table>';

  return [$html, $i - 1];
}

function inlineMarkdown(string $text, string $currentPath = ''): string {
  $text = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
  // Underline
  $text = preg_replace('/__(.+?)__/', '<u>$1</u>', $text);
  // Strikethrough
  $text = preg_replace('/~~(.+?)~~/', '<del>$1</del>', $text);
  // Bold (strong)
  $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
  // Italic (em)
  $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);
  // Inline code
  $text = preg_replace('/`([^`]+)`/', '<code class="hljs inline">$1</code>', $text);
  // Links
  $text = preg_replace_callback('/\[([^\]]+)\]\(([^\)]+)\)/', function ($m) use ($currentPath) {
    $label = $m[1];
    $href = $m[2];
    [$cleanHref, $targetBlank, $isClassLink] = normalizeDocLink($href, $currentPath);
    if ($isClassLink) {
      $label = '<span class="doc-icon doc-icon--inline"><img src="/assets/module.png" alt=""></span>' . $label;
    }
    $attrs = $targetBlank ? ' target="_blank" rel="noopener"' : '';
    return '<a href="' . htmlspecialchars($cleanHref, ENT_QUOTES, 'UTF-8') . '"' . $attrs . '>' . $label . '</a>';
  }, $text);
  return $text;
}

function renderToc(array $toc): string {
  if (!$toc) {
    return '<div class="toc-empty">No headings</div>';
  }
  $html = '<ul class="toc">';
  foreach ($toc as $item) {
    $level = max(1, min(6, (int)$item['level']));
    $label = htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8');
    $href = htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8');
    $html .= '<li class="toc-l' . $level . '"><a href="' . $href . '">' . $label . '</a></li>';
  }
  $html .= '</ul>';
  return $html;
}

function slugifyHeading(string $text): string {
  $text = strtolower(trim($text));
  $text = preg_replace('/[^\p{L}\p{N}\s-]/u', '', $text);
  $text = preg_replace('/\s+/', '-', $text);
  $text = trim($text, '-');
  return $text !== '' ? $text : 'section';
}

function getAsciiFlame(): string {
  return <<<FLAME
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
FLAME;
}

function getAsciiFlameScript(): string {
  return <<<'JS'
(function () {
  var el = document.getElementById('ascii-flame');
  if (!el) return;

  var width = 28;
  var height = 14;
  var chars = ' .:-=+*#%@';
  var field = new Array(width * height).fill(0);
  var resizeTimeout = null;

  function measure() {
    var fontSize = 10;
    var lineHeight = 10;
    var charWidth = 6;
    var cols = Math.max(24, Math.floor(el.clientWidth / charWidth));
    var rows = Math.max(16, Math.floor(el.clientHeight / lineHeight));
    width = cols;
    height = rows;
    field = new Array(width * height).fill(0);
  }

  function seed() {
    var base = (height - 1) * width;
    for (var x = 0; x < width; x++) {
      field[base + x] = Math.floor(Math.random() * chars.length);
    }
  }

  function step() {
    seed();
    for (var y = 0; y < height - 1; y++) {
      for (var x = 0; x < width; x++) {
        var idx = y * width + x;
        var below = idx + width;
        var left = below - 1 >= (y + 1) * width ? below - 1 : below;
        var right = below + 1 < (y + 2) * width ? below + 1 : below;
        var val = (field[below] + field[left] + field[right] + field[below]) / 4;
        val = Math.max(0, val - (Math.random() * 1.2));
        field[idx] = val;
      }
    }
  }

  function render() {
    var out = '';
    for (var y = 0; y < height; y++) {
      for (var x = 0; x < width; x++) {
        var v = field[y * width + x];
        var ci = Math.max(0, Math.min(chars.length - 1, Math.floor(v)));
        out += chars[ci];
      }
      out += '\n';
    }
    el.textContent = out;
  }

  function tick() {
    step();
    render();
  }

  measure();
  window.addEventListener('resize', function () {
    if (resizeTimeout) {
      clearTimeout(resizeTimeout);
    }
    resizeTimeout = setTimeout(function () {
      measure();
    }, 120);
  });

  setInterval(tick, 90);
})();
JS;
}

function normalizeDocLink(string $href, string $currentPath): array {
  $href = trim($href);
  if ($href === '') {
    return ['#', false, false];
  }
  if (preg_match('/^(https?:\/\/|mailto:)/i', $href)) {
    return [$href, true, false];
  }
  if (str_starts_with($href, '#')) {
    return [$href, false, false];
  }

  $parts = explode('#', $href, 2);
  $path = $parts[0];
  $anchor = $parts[1] ?? '';

  if (str_starts_with($path, '/')) {
    $path = ltrim($path, '/');
  } else {
    $baseDir = trim(str_replace('\\', '/', dirname($currentPath)), '.');
    $path = ($baseDir !== '' ? $baseDir . '/' : '') . $path;
  }
  $path = normalizePath($path);

  $isClassLink = str_starts_with($path, 'classes/');
  if (str_ends_with(strtolower($path), '.md')) {
    $url = '/' . $path;
    if ($anchor !== '') {
      $url .= '#' . rawurlencode($anchor);
    }
    return [$url, false, $isClassLink];
  }

  $url = $path;
  if ($anchor !== '') {
    $url .= '#' . rawurlencode($anchor);
  }
  return [$url, false, $isClassLink];
}

function normalizePath(string $path): string {
  $parts = array_filter(explode('/', str_replace('\\', '/', $path)), 'strlen');
  $stack = [];
  foreach ($parts as $part) {
    if ($part === '.') continue;
    if ($part === '..') {
      array_pop($stack);
      continue;
    }
    $stack[] = $part;
  }
  return implode('/', $stack);
}

function getStyles(): string {
  return <<<CSS
  body { margin: 0; font-family: "Google Sans", "Noto Sans", sans-serif; color: #e6edf3; background: #0f1115; }
  .layout { display: grid; grid-template-columns: 300px 1fr; min-height: 100vh; }
  .sidebar { position: sticky; top: 0; align-self: start; height: 100vh; background: #11151c; color: #e6edf3; padding: 22px 18px; border-right: 1px solid #1b2028; overflow: hidden; }
  .sidebar-content { position: relative; z-index: 1; height: 100%; overflow: auto; padding-right: 6px; }
  .brand { margin-bottom: 14px; }
  .brand img { max-width: 160px; height: auto; display: block; }
  .nav-section { margin-bottom: 18px; }
  .section-title { font-size: 11px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: #8b98ad; margin: 12px 0 8px; }
  .sidebar ul { list-style: none; padding-left: 0; margin: 0; }
  .sidebar li { margin: 6px 0; font-size: 13px; }
  .sidebar a { color: #c9d1d9; text-decoration: none; display: block; padding: 4px 8px; border-radius: 6px; }
  .sidebar a.active, .sidebar a:hover { background: #1f2630; color: #ffffff; }
  .sidebar .dir { font-weight: 600; margin-top: 10px; color: #cbd5e1; }
  .sidebar ul.sub { padding-left: 12px; margin-top: 6px; }
  .sidebar ul.sub a { font-size: 12px; color: #aab4c2; }
  .sidebar ul.sub a.active, .sidebar ul.sub a:hover { color: #ffffff; }
  .doc-icon { display: inline-flex; align-items: center; margin-right: 6px; vertical-align: middle; }
  .doc-icon img { width: 14px; height: 14px; object-fit: contain; }
  .doc-icon--inline img { width: 16px; height: 16px; }
  .badge { display: inline-flex; align-items: center; justify-content: center; margin-left: 6px; font-size: 11px; font-weight: 700; color: #9aa7b8; background: #151a22; border: 1px solid #1b2028; border-radius: 10px; padding: 0 6px; line-height: 1.4; }
  .badge-alias { color: #9aa7b8; }
  .badge-trait { color: #86b7ff; }
  .ascii-flame { position: absolute; top: -10px; left: 0; width: 200px; height: 300px; margin: 0; font-family: "Consolas", "Menlo", "SFMono-Regular", monospace; font-size: 10px; line-height: 1; color: rgba(255,255,255,0.38); white-space: pre; opacity: 0.6; filter: grayscale(1); pointer-events: none; z-index: 0; transform: rotateZ(180deg); transform-origin: top; }
  .sidebar { scrollbar-width: thin; scrollbar-color: rgba(255,255,255,0.25) transparent; }
  .sidebar::-webkit-scrollbar { width: 8px; }
  .sidebar::-webkit-scrollbar-track { background: transparent; }
  .sidebar::-webkit-scrollbar-thumb { background-color: rgba(255,255,255,0.18); border-radius: 999px; border: 2px solid transparent; background-clip: content-box; }
  .sidebar::-webkit-scrollbar-thumb:hover { background-color: rgba(255,255,255,0.28); }
  .toc { margin: 0; padding: 0; }
  .toc li { margin: 2px 0; }
  .toc a { font-size: 12px; color: #9aa7b8; }
  .toc a:hover { color: #ffffff; }
  .toc-l2 a { padding-left: 10px; }
  .toc-l3 a { padding-left: 18px; }
  .toc-l4 a { padding-left: 26px; }
  .toc-l5 a { padding-left: 34px; }
  .toc-l6 a { padding-left: 42px; }
  .toc-empty { font-size: 12px; color: #7c8696; padding: 4px 6px; }
  .content { padding: 32px 40px; background: #0f1115; }
  .content h1, .content h2, .content h3 { font-family: "Google Sans", "Noto Sans", sans-serif; color: #f3f4f6; }
  .content pre { padding: 16px; border-radius: 6px; overflow: auto; background: #0b0e13; border: 1px solid #1b2028; }
  .content code { background: #151a22; color: #e6edf3; padding: 2px 4px; border-radius: 4px; }
  .content code.inline.hljs { background: #151a22; color: #e6edf3; border: 1px solid #1b2028; box-shadow: inset 0 0 0 1px #12161d; }
  .content code.inline.hljs .hljs-keyword { color: #7aa2f7; }
  .content code.inline.hljs .hljs-string { color: #9ece6a; }
  .content code.inline.hljs .hljs-number { color: #bb9af7; }
  .content code.inline.hljs .hljs-title,
  .content code.inline.hljs .hljs-built_in,
  .content code.inline.hljs .hljs-type { color: #ff9e64; }
  .content pre code { background: transparent; padding: 0; }
  .content table { border-collapse: collapse; width: 100%; margin: 12px 0; font-size: 14px; }
  .content th, .content td { border: 1px solid #1b2028; padding: 8px 10px; text-align: left; }
  .content th { background: #12161d; }
  .content p { line-height: 1.6; color: #d0d6de; }
  .content a { color: #7aa2f7; }
  .content blockquote { margin: 12px 0; padding: 8px 14px; border-left: 4px solid #273241; background: #11151c; color: #c9d1d9; }
  .content hr { border: 0; border-top: 1px solid #1b2028; margin: 16px 0; }
  .spacer { height: 10px; }
  .spacer { height: 10px; }
  @media (max-width: 900px) { .layout { grid-template-columns: 1fr; } .sidebar { border-right: none; border-bottom: 1px solid #e0d6c8; } }
CSS;
}
