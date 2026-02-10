<?php

/**
 * ReleaseCommon
 *
 * Shared helpers for release planning/cutting/checking.
 *
 * @package core
 * @author Stefano Azzolini <lastguest@gmail.com>
 * @copyright Coesion - 2026
 */

function releaseRootPath(){
  return dirname(__DIR__);
}

function releaseVersionPath(){
  return releaseRootPath() . DIRECTORY_SEPARATOR . 'VERSION';
}

function releaseChangelogPath(){
  return releaseRootPath() . DIRECTORY_SEPARATOR . 'CHANGELOG.md';
}

function releaseCorePath(){
  return releaseRootPath() . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'Core.php';
}

function releaseReadVersion(){
  $path = releaseVersionPath();
  if (!is_file($path)) {
    throw new RuntimeException("Missing VERSION file at $path");
  }

  $version = trim((string)file_get_contents($path));
  if (!releaseIsSemver($version)) {
    throw new RuntimeException("Invalid VERSION value '$version'. Expected X.Y.Z");
  }

  return $version;
}

function releaseWriteVersion($version){
  if (!releaseIsSemver($version)) {
    throw new RuntimeException("Cannot write invalid version '$version'");
  }

  $path = releaseVersionPath();
  if (file_put_contents($path, $version . "\n") === false) {
    throw new RuntimeException("Failed writing VERSION file at $path");
  }
}

function releaseIsSemver($version){
  return is_string($version) && preg_match('/^\d+\.\d+\.\d+$/', $version);
}

function releaseReadCoreConstVersion(){
  $path = releaseCorePath();
  if (!is_file($path)) {
    throw new RuntimeException("Missing Core class file at $path");
  }

  $code = (string)file_get_contents($path);
  if (!preg_match("/public\\s+const\\s+VERSION\\s*=\\s*'([^']+)';/", $code, $m)) {
    throw new RuntimeException("Could not read Core::VERSION from $path");
  }

  return $m[1];
}

function releaseWriteCoreConstVersion($version){
  $path = releaseCorePath();
  $code = (string)file_get_contents($path);
  $updated = preg_replace("/public\\s+const\\s+VERSION\\s*=\\s*'[^']+';/", "public const VERSION = '$version';", $code, 1);

  if ($updated === null || $updated === $code) {
    throw new RuntimeException("Failed updating Core::VERSION in $path");
  }

  if (file_put_contents($path, $updated) === false) {
    throw new RuntimeException("Failed writing $path");
  }
}

function releaseExec($command, &$exitCode = null){
  $output = [];
  exec($command . ' 2>&1', $output, $code);
  $exitCode = $code;
  return implode("\n", $output);
}

function releaseLatestTag(){
  $out = releaseExec('git describe --tags --abbrev=0', $code);
  if ($code !== 0) {
    return null;
  }

  $tag = trim($out);
  return $tag === '' ? null : $tag;
}

function releaseTagToVersion($tag){
  $tag = trim((string)$tag);
  if ($tag === '') {
    return null;
  }

  if ($tag[0] === 'v' || $tag[0] === 'V') {
    $tag = substr($tag, 1);
  }

  return releaseIsSemver($tag) ? $tag : null;
}

function releaseVersionToTag($version){
  return 'v' . $version;
}

function releaseHeadTagVersion(){
  $out = releaseExec('git tag --points-at HEAD', $code);
  if ($code !== 0) {
    return null;
  }

  foreach (array_filter(array_map('trim', explode("\n", $out))) as $tag) {
    $version = releaseTagToVersion($tag);
    if ($version !== null) {
      return $version;
    }
  }

  return null;
}

function releaseCommitRange($latestTag){
  if ($latestTag) {
    return $latestTag . '..HEAD';
  }

  return 'HEAD';
}

function releaseCollectCommits($latestTag = null){
  $range = releaseCommitRange($latestTag);
  $format = '%H%x1f%s%x1f%b%x1e';
  $out = releaseExec("git log --pretty=format:$format $range", $code);

  if ($code !== 0) {
    throw new RuntimeException("Unable to read git log for range $range");
  }

  $commits = [];
  foreach (explode("\x1e", $out) as $row) {
    $row = trim($row);
    if ($row === '') {
      continue;
    }

    $parts = explode("\x1f", $row, 3);
    $commits[] = [
      'hash' => $parts[0] ?? '',
      'subject' => trim($parts[1] ?? ''),
      'body' => trim($parts[2] ?? ''),
    ];
  }

  return $commits;
}

function releaseAnalyzeCommits(array $commits){
  $analysis = [
    'bump' => 'none',
    'has_substantial' => false,
    'sections' => [
      'added' => [],
      'changed' => [],
      'fixed' => [],
      'breaking' => [],
    ],
    'upgrade_notes' => [],
  ];

  foreach ($commits as $commit) {
    $subject = $commit['subject'];
    $body = $commit['body'];

    $type = 'other';
    $summary = $subject;
    $breaking = false;

    if (preg_match('/^([a-z]+)(\([^)]+\))?(!)?:\s+(.+)$/i', $subject, $m)) {
      $type = strtolower($m[1]);
      $summary = trim($m[4]);
      $breaking = !empty($m[3]);
    }

    if (stripos($body, 'BREAKING CHANGE:') !== false) {
      $breaking = true;
      foreach (preg_split('/\r?\n/', $body) as $line) {
        if (stripos($line, 'BREAKING CHANGE:') === 0) {
          $note = trim(substr($line, strlen('BREAKING CHANGE:')));
          if ($note !== '') {
            $analysis['upgrade_notes'][] = $note;
          }
        }
      }
    }

    if ($breaking) {
      $analysis['has_substantial'] = true;
      $analysis['bump'] = 'major';
      $analysis['sections']['breaking'][] = $summary;
      continue;
    }

    if ($type === 'feat') {
      $analysis['has_substantial'] = true;
      if ($analysis['bump'] !== 'major') {
        $analysis['bump'] = 'minor';
      }
      $analysis['sections']['added'][] = $summary;
      continue;
    }

    if ($type === 'fix') {
      $analysis['has_substantial'] = true;
      if (!in_array($analysis['bump'], ['major', 'minor'], true)) {
        $analysis['bump'] = 'patch';
      }
      $analysis['sections']['fixed'][] = $summary;
      continue;
    }

    if (in_array($type, ['perf', 'refactor', 'revert'], true)) {
      $analysis['has_substantial'] = true;
      if (!in_array($analysis['bump'], ['major', 'minor'], true)) {
        $analysis['bump'] = 'patch';
      }
      $analysis['sections']['changed'][] = $summary;
      continue;
    }

    if (in_array($type, ['docs', 'chore', 'ci', 'build', 'test', 'style'], true)) {
      $analysis['sections']['changed'][] = $summary;
      continue;
    }

    $analysis['sections']['changed'][] = $summary;
  }

  $analysis['upgrade_notes'] = array_values(array_unique($analysis['upgrade_notes']));

  return $analysis;
}

function releaseNextVersion($current, $bump){
  if (!releaseIsSemver($current)) {
    throw new RuntimeException("Invalid current version '$current'");
  }

  [$major, $minor, $patch] = array_map('intval', explode('.', $current));

  if ($bump === 'major') {
    $major++;
    $minor = 0;
    $patch = 0;
  } elseif ($bump === 'minor') {
    $minor++;
    $patch = 0;
  } elseif ($bump === 'patch') {
    $patch++;
  } else {
    throw new RuntimeException("Unsupported bump type '$bump'");
  }

  return $major . '.' . $minor . '.' . $patch;
}

function releaseTopChangelogVersion(){
  $path = releaseChangelogPath();
  if (!is_file($path)) {
    return null;
  }

  $content = (string)file_get_contents($path);
  if (preg_match('/^##\s+v?(\d+\.\d+\.\d+)/m', $content, $m)) {
    return $m[1];
  }

  return null;
}

function releaseRenderChangelogEntry($version, array $analysis){
  $date = gmdate('Y-m-d');
  $lines = [];
  $lines[] = "## v$version - $date";
  $lines[] = '';
  $lines[] = 'Quick guide:';
  $lines[] = '- What changed: see Added/Changed/Fixed below.';
  $lines[] = '- Impact: review Breaking and Upgrade Notes before deployment.';
  $lines[] = '';

  $sections = [
    'Added' => $analysis['sections']['added'],
    'Changed' => $analysis['sections']['changed'],
    'Fixed' => $analysis['sections']['fixed'],
    'Breaking' => $analysis['sections']['breaking'],
  ];

  foreach ($sections as $title => $items) {
    $lines[] = "### $title";
    if ($items === []) {
      $lines[] = '- None.';
    } else {
      foreach ($items as $item) {
        $lines[] = '- ' . $item;
      }
    }
    $lines[] = '';
  }

  $lines[] = '### Upgrade Notes';
  if ($analysis['sections']['breaking'] === []) {
    $lines[] = '- No upgrade action required.';
  } elseif ($analysis['upgrade_notes'] !== []) {
    foreach ($analysis['upgrade_notes'] as $note) {
      $lines[] = '- ' . $note;
    }
  } else {
    $lines[] = '- Review breaking items and update integrations before release.';
  }
  $lines[] = '';

  return implode("\n", $lines);
}

function releasePrependChangelogEntry($entry){
  $path = releaseChangelogPath();
  $header = "# Changelog\n\n";

  $existing = is_file($path) ? (string)file_get_contents($path) : '';
  if ($existing === '' || strpos($existing, '# Changelog') !== 0) {
    $content = $header . $entry;
  } else {
    $rest = ltrim(substr($existing, strlen('# Changelog')));
    $content = "# Changelog\n\n" . $entry . "\n" . $rest;
  }

  if (file_put_contents($path, $content) === false) {
    throw new RuntimeException("Failed to write changelog at $path");
  }
}

function releaseRepoClean(){
  $out = releaseExec('git status --porcelain', $code);
  return $code === 0 && trim($out) === '';
}

function releaseEnsureDir($path){
  if (!is_dir($path) && !mkdir($path, 0775, true) && !is_dir($path)) {
    throw new RuntimeException("Failed to create directory $path");
  }
}

function releaseRunPassthru($command){
  passthru($command, $code);
  if ($code !== 0) {
    throw new RuntimeException("Command failed ($code): $command");
  }
}
