<?php

/**
 * Static Docs Site Builder
 *
 * Renders docs markdown into a static HTML site for GitHub Pages.
 *
 * @package core
 * @author Stefano Azzolini <lastguest@gmail.com>
 * @copyright Coesion - 2026
 */

$root = dirname(__DIR__);
$docsDir = $root . DIRECTORY_SEPARATOR . 'docs';
$outDir = $root . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'docs-site';

foreach (($argv ?? []) as $arg) {
  if (strpos($arg, '--out=') === 0) {
    $outDir = rtrim(substr($arg, 6), DIRECTORY_SEPARATOR);
  }
}

if (!is_dir($docsDir)) {
  fwrite(STDERR, "Docs directory not found: $docsDir\n");
  exit(1);
}

resetDir($outDir);

$markdownFiles = findMarkdownFiles($docsDir);
$map = [];
foreach ($markdownFiles as $srcPath) {
  $rel = normalizeRelPath($docsDir, $srcPath);
  $map[$rel] = outputPathForMarkdown($rel);
}

copyAssetsAndStaticFiles($docsDir, $outDir);

$nav = buildNavigation($map);
foreach ($map as $sourceRel => $outputRel) {
  $sourcePath = $docsDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $sourceRel);
  $raw = file_get_contents($sourcePath);
  [$content, $toc, $title] = renderMarkdown($raw, $sourceRel, $outputRel, $map);
  $page = renderPageHtml($title, $content, $toc, $nav, $outputRel, $sourceRel);
  $targetPath = $outDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $outputRel);
  ensureDir(dirname($targetPath));
  file_put_contents($targetPath, $page);
}

if (isset($map['guides/README.md'])) {
  $indexPath = $outDir . DIRECTORY_SEPARATOR . 'index.html';
  $target = 'guides/index.html';
  $redirect = '<!doctype html><html><head><meta charset="utf-8"><meta http-equiv="refresh" content="0; url='
    . htmlspecialchars($target, ENT_QUOTES, 'UTF-8')
    . '"><title>Docs</title></head><body>Redirecting to <a href="'
    . htmlspecialchars($target, ENT_QUOTES, 'UTF-8')
    . '">documentation</a>.</body></html>';
  file_put_contents($indexPath, $redirect);
}

fwrite(STDOUT, "Static docs site generated in $outDir\n");

function findMarkdownFiles(string $docsDir): array {
  $files = [];
  $iter = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($docsDir, FilesystemIterator::SKIP_DOTS)
  );
  foreach ($iter as $file) {
    if (!$file->isFile()) continue;
    $path = $file->getPathname();
    if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'md') continue;
    $files[] = $path;
  }
  sort($files, SORT_STRING);
  return $files;
}

function copyAssetsAndStaticFiles(string $docsDir, string $outDir): void {
  $iter = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($docsDir, FilesystemIterator::SKIP_DOTS)
  );
  foreach ($iter as $file) {
    if (!$file->isFile()) continue;
    $src = $file->getPathname();
    $ext = strtolower(pathinfo($src, PATHINFO_EXTENSION));
    if ($ext === 'md') continue;
    $rel = normalizeRelPath($docsDir, $src);
    $dst = $outDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    ensureDir(dirname($dst));
    copy($src, $dst);
  }
}

function buildNavigation(array $map): array {
  $nav = [
    'guides' => [],
    'classes' => [],
    'examples' => [],
    'other' => [],
  ];
  foreach ($map as $sourceRel => $outputRel) {
    $parts = explode('/', $sourceRel);
    $top = $parts[0] ?? 'other';
    $item = [
      'source' => $sourceRel,
      'output' => $outputRel,
      'label' => labelFromSource($sourceRel),
    ];
    if (!isset($nav[$top])) $top = 'other';
    $nav[$top][] = $item;
  }
  foreach ($nav as $key => $items) {
    usort($items, function ($a, $b) {
      return strcasecmp($a['label'], $b['label']);
    });
    $nav[$key] = $items;
  }
  return $nav;
}

function labelFromSource(string $sourceRel): string {
  $base = basename($sourceRel);
  if (strcasecmp($base, 'README.md') === 0) {
    $dir = basename(dirname($sourceRel));
    return $dir !== '.' ? ucfirst($dir) . ' Home' : 'Home';
  }
  return preg_replace('/\.md$/i', '', $base);
}

function outputPathForMarkdown(string $rel): string {
  if (preg_match('#(^|/)README\.md$#i', $rel)) {
    $dir = dirname($rel);
    if ($dir === '.' || $dir === '') {
      return 'index.html';
    }
    return normalizePath($dir . '/index.html');
  }
  return preg_replace('/\.md$/i', '.html', $rel);
}

function renderPageHtml(string $title, string $content, array $toc, array $nav, string $currentOutputRel, string $sourceRel): string {
  $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
  $tocHtml = renderToc($toc);
  $navHtml = renderNav($nav, $currentOutputRel);
  $css = getStyles();
  return '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
    . '<title>' . $safeTitle . '</title>'
    . '<style>' . $css . '</style>'
    . '</head><body><div class="layout"><aside class="sidebar"><div class="brand">Coesion/Core Docs</div>'
    . '<div class="nav"><div class="section"><h4>On This Page</h4>' . $tocHtml . '</div>'
    . $navHtml
    . '</div></aside><main class="content"><div class="doc-meta">'
    . htmlspecialchars($sourceRel, ENT_QUOTES, 'UTF-8')
    . '</div>' . $content . '</main></div></body></html>';
}

function renderNav(array $nav, string $currentOutputRel): string {
  $out = '';
  $labels = [
    'guides' => 'Guides',
    'classes' => 'Classes',
    'examples' => 'Examples',
    'other' => 'Other',
  ];
  foreach ($labels as $key => $sectionLabel) {
    $items = $nav[$key] ?? [];
    if (!$items) continue;
    $out .= '<div class="section"><h4>' . htmlspecialchars($sectionLabel, ENT_QUOTES, 'UTF-8') . '</h4><ul>';
    foreach ($items as $item) {
      $href = relativePath($currentOutputRel, $item['output']);
      $active = $item['output'] === $currentOutputRel ? ' class="active"' : '';
      $out .= '<li><a' . $active . ' href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">'
        . htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') . '</a></li>';
    }
    $out .= '</ul></div>';
  }
  return $out;
}

function renderToc(array $toc): string {
  if (!$toc) {
    return '<p class="toc-empty">No headings</p>';
  }
  $out = '<ul class="toc">';
  foreach ($toc as $item) {
    $cls = 'toc-l' . max(1, min(6, (int)$item['level']));
    $out .= '<li class="' . $cls . '"><a href="' . htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') . '">'
      . htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8')
      . '</a></li>';
  }
  $out .= '</ul>';
  return $out;
}

function renderMarkdown(string $text, string $sourceRel, string $currentOutputRel, array $map): array {
  $text = str_replace(["\r\n", "\r"], "\n", $text);
  $lines = explode("\n", $text);
  $html = '';
  $toc = [];
  $title = preg_replace('/\.md$/i', '', basename($sourceRel));
  $inCode = false;
  $inList = false;
  $inQuote = false;
  $codeLang = '';
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
        if ($inQuote) { $html .= '</blockquote>'; $inQuote = false; }
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

    if (isTableHeader($line, $lines[$i + 1] ?? '')) {
      if ($inList) { $html .= '</ul>'; $inList = false; }
      if ($inQuote) { $html .= '</blockquote>'; $inQuote = false; }
      [$tableHtml, $newIndex] = renderTable($lines, $i, $sourceRel, $currentOutputRel, $map);
      $html .= $tableHtml;
      $i = $newIndex;
      continue;
    }

    if (preg_match('/^(#{1,6})\s+(.*)$/', $line, $m)) {
      if ($inList) { $html .= '</ul>'; $inList = false; }
      if ($inQuote) { $html .= '</blockquote>'; $inQuote = false; }
      $level = strlen($m[1]);
      $heading = trim($m[2]);
      if ($title === preg_replace('/\.md$/i', '', basename($sourceRel))) {
        $title = strip_tags($heading);
      }
      $id = slugify($heading);
      $inline = inlineMarkdown($heading, $sourceRel, $currentOutputRel, $map);
      $html .= '<h' . $level . ' id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '">' . $inline . '</h' . $level . '>';
      $toc[] = [
        'level' => $level,
        'title' => strip_tags($inline),
        'href' => '#' . rawurlencode($id),
      ];
      continue;
    }

    if (preg_match('/^\s*[-*+]\s+(.*)$/', $line, $m)) {
      if (!$inList) $html .= '<ul>';
      if ($inQuote) { $html .= '</blockquote>'; $inQuote = false; }
      $inList = true;
      $html .= '<li>' . inlineMarkdown($m[1], $sourceRel, $currentOutputRel, $map) . '</li>';
      continue;
    }

    if (preg_match('/^\s*>\s?(.*)$/', $line, $m)) {
      if ($inList) { $html .= '</ul>'; $inList = false; }
      if (!$inQuote) $html .= '<blockquote>';
      $inQuote = true;
      $html .= '<p>' . inlineMarkdown($m[1], $sourceRel, $currentOutputRel, $map) . '</p>';
      continue;
    }

    if (trim($line) === '') {
      if ($inList) { $html .= '</ul>'; $inList = false; }
      if ($inQuote) { $html .= '</blockquote>'; $inQuote = false; }
      continue;
    }

    if ($inQuote) {
      $html .= '<p>' . inlineMarkdown($line, $sourceRel, $currentOutputRel, $map) . '</p>';
      continue;
    }

    $html .= '<p>' . inlineMarkdown($line, $sourceRel, $currentOutputRel, $map) . '</p>';
  }

  if ($inCode) $html .= '</code></pre>';
  if ($inList) $html .= '</ul>';
  if ($inQuote) $html .= '</blockquote>';

  return [$html, $toc, $title];
}

function isTableHeader(string $line, string $next): bool {
  if (strpos($line, '|') === false) return false;
  $next = trim($next);
  if ($next === '') return false;
  return (bool)preg_match('/^[\s\|\-:]+$/', $next) && strpos($next, '-') !== false;
}

function renderTable(array $lines, int $start, string $sourceRel, string $currentOutputRel, array $map): array {
  $header = splitTableRow($lines[$start]);
  $i = $start + 2;
  $rows = [];
  $count = count($lines);
  for (; $i < $count; $i++) {
    $line = $lines[$i];
    if (trim($line) === '' || strpos($line, '|') === false) break;
    $rows[] = splitTableRow($line);
  }

  $html = '<table><thead><tr>';
  foreach ($header as $cell) {
    $html .= '<th>' . inlineMarkdown($cell, $sourceRel, $currentOutputRel, $map) . '</th>';
  }
  $html .= '</tr></thead><tbody>';
  foreach ($rows as $row) {
    $html .= '<tr>';
    foreach ($row as $cell) {
      $html .= '<td>' . inlineMarkdown($cell, $sourceRel, $currentOutputRel, $map) . '</td>';
    }
    $html .= '</tr>';
  }
  $html .= '</tbody></table>';
  return [$html, $i - 1];
}

function splitTableRow(string $line): array {
  $line = trim(trim($line), '|');
  return array_map('trim', explode('|', $line));
}

function inlineMarkdown(string $text, string $sourceRel, string $currentOutputRel, array $map): string {
  $text = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
  $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);
  $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
  $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);
  $text = preg_replace('/~~(.+?)~~/', '<del>$1</del>', $text);
  $text = preg_replace('/__(.+?)__/', '<u>$1</u>', $text);
  $text = preg_replace_callback('/\[([^\]]+)\]\(([^\)]+)\)/', function ($m) use ($sourceRel, $currentOutputRel, $map) {
    $label = $m[1];
    $href = trim($m[2]);
    if ($href === '') {
      return '<a href="#">' . $label . '</a>';
    }
    if (preg_match('/^(https?:\/\/|mailto:)/i', $href)) {
      return '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener">' . $label . '</a>';
    }
    if (str_starts_with($href, '#')) {
      return '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">' . $label . '</a>';
    }

    $parts = explode('#', $href, 2);
    $path = $parts[0];
    $anchor = $parts[1] ?? '';
    $resolvedSource = resolveLinkSource($sourceRel, $path);
    $resolvedOutput = $resolvedSource;
    if (str_ends_with(strtolower($resolvedSource), '.md') && isset($map[$resolvedSource])) {
      $resolvedOutput = $map[$resolvedSource];
    }
    $target = relativePath($currentOutputRel, $resolvedOutput);
    if ($anchor !== '') $target .= '#' . rawurlencode($anchor);
    return '<a href="' . htmlspecialchars($target, ENT_QUOTES, 'UTF-8') . '">' . $label . '</a>';
  }, $text);
  return $text;
}

function resolveLinkSource(string $sourceRel, string $hrefPath): string {
  $hrefPath = str_replace('\\', '/', $hrefPath);
  if (str_starts_with($hrefPath, '/')) {
    return normalizePath(ltrim($hrefPath, '/'));
  }
  $baseDir = dirname($sourceRel);
  if ($baseDir === '.') $baseDir = '';
  return normalizePath(($baseDir ? $baseDir . '/' : '') . $hrefPath);
}

function relativePath(string $fromRel, string $toRel): string {
  $fromDir = dirname($fromRel);
  if ($fromDir === '.') $fromDir = '';
  $fromParts = $fromDir === '' ? [] : explode('/', normalizePath($fromDir));
  $toParts = explode('/', normalizePath($toRel));

  while ($fromParts && $toParts && $fromParts[0] === $toParts[0]) {
    array_shift($fromParts);
    array_shift($toParts);
  }
  $up = array_fill(0, count($fromParts), '..');
  $parts = array_merge($up, $toParts);
  $rel = implode('/', $parts);
  return $rel !== '' ? $rel : './';
}

function normalizePath(string $path): string {
  $parts = explode('/', str_replace('\\', '/', $path));
  $out = [];
  foreach ($parts as $part) {
    if ($part === '' || $part === '.') continue;
    if ($part === '..') {
      array_pop($out);
      continue;
    }
    $out[] = $part;
  }
  return implode('/', $out);
}

function normalizeRelPath(string $baseDir, string $path): string {
  $base = rtrim(str_replace('\\', '/', realpath($baseDir)), '/');
  $full = str_replace('\\', '/', realpath($path));
  return ltrim(substr($full, strlen($base)), '/');
}

function slugify(string $text): string {
  $text = strtolower(trim($text));
  $text = preg_replace('/[^\p{L}\p{N}\s-]/u', '', $text);
  $text = preg_replace('/\s+/', '-', $text);
  $text = trim($text, '-');
  return $text !== '' ? $text : 'section';
}

function resetDir(string $dir): void {
  if (is_dir($dir)) {
    $iter = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
      RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iter as $item) {
      if ($item->isDir()) {
        rmdir($item->getPathname());
      } else {
        unlink($item->getPathname());
      }
    }
    rmdir($dir);
  }
  mkdir($dir, 0777, true);
}

function ensureDir(string $dir): void {
  if (!is_dir($dir)) mkdir($dir, 0777, true);
}

function getStyles(): string {
  return <<<CSS
body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; color: #e5e7eb; background: #0b1220; }
.layout { display: grid; grid-template-columns: 300px 1fr; min-height: 100vh; }
.sidebar { position: sticky; top: 0; align-self: start; height: 100vh; overflow-y: auto; padding: 20px 16px; border-right: 1px solid #1f2937; background: #111827; }
.brand { font-size: 16px; font-weight: 700; margin-bottom: 14px; color: #f9fafb; }
.section { margin-bottom: 14px; }
.section h4 { font-size: 11px; letter-spacing: 0.08em; text-transform: uppercase; margin: 0 0 8px; color: #9ca3af; }
.sidebar ul { list-style: none; margin: 0; padding: 0; }
.sidebar li { margin: 3px 0; }
.sidebar a { color: #cbd5e1; text-decoration: none; display: block; font-size: 13px; padding: 5px 8px; border-radius: 6px; }
.sidebar a:hover, .sidebar a.active { color: #f8fafc; background: #1f2937; }
.toc a { font-size: 12px; color: #9ca3af; }
.toc .toc-l2 a { padding-left: 10px; }
.toc .toc-l3 a { padding-left: 18px; }
.toc .toc-l4 a { padding-left: 26px; }
.toc .toc-l5 a { padding-left: 34px; }
.toc .toc-l6 a { padding-left: 42px; }
.toc-empty { margin: 0; font-size: 12px; color: #6b7280; }
.content { padding: 28px 36px; }
.doc-meta { font-size: 12px; color: #9ca3af; margin-bottom: 10px; }
.content h1, .content h2, .content h3, .content h4 { color: #f8fafc; margin-top: 24px; }
.content h1 { margin-top: 8px; }
.content p, .content li { line-height: 1.65; color: #d1d5db; }
.content a { color: #93c5fd; }
.content pre { background: #020617; color: #e2e8f0; padding: 14px; border: 1px solid #1e293b; border-radius: 8px; overflow: auto; }
.content code { background: #111827; border: 1px solid #1f2937; border-radius: 4px; padding: 1px 5px; }
.content pre code { border: 0; padding: 0; background: transparent; }
.content blockquote { margin: 12px 0; border-left: 4px solid #334155; padding: 8px 12px; background: #111827; color: #cbd5e1; }
.content table { border-collapse: collapse; width: 100%; margin: 14px 0; font-size: 14px; }
.content th, .content td { border: 1px solid #1f2937; padding: 8px 10px; text-align: left; }
.content th { background: #0f172a; color: #f8fafc; }
@media (max-width: 960px) {
  .layout { grid-template-columns: 1fr; }
  .sidebar { position: static; height: auto; border-right: 0; border-bottom: 1px solid #1f2937; }
}
CSS;
}
