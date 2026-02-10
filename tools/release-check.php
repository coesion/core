<?php

require_once __DIR__ . '/release-common.php';

$strict = in_array('--strict', $argv, true);
$errors = [];

try {
  $root = releaseRootPath();
  $version = releaseReadVersion();
  $coreVersion = releaseReadCoreConstVersion();

  if ($coreVersion !== $version) {
    $errors[] = "Core::VERSION ($coreVersion) does not match VERSION ($version).";
  }

  $composerPath = $root . DIRECTORY_SEPARATOR . 'composer.json';
  $composer = json_decode((string)file_get_contents($composerPath), true);
  if (!is_array($composer)) {
    $errors[] = 'composer.json is not valid JSON.';
  } elseif (array_key_exists('version', $composer)) {
    $errors[] = 'composer.json must not include a version field; VERSION + tags are authoritative.';
  }

  $headTagVersion = releaseHeadTagVersion();
  $topChangelog = releaseTopChangelogVersion();

  if ($headTagVersion !== null) {
    if ($headTagVersion !== $version) {
      $errors[] = "HEAD tag version ($headTagVersion) does not match VERSION ($version).";
    }
    if ($topChangelog !== $version) {
      $errors[] = "Top CHANGELOG version ($topChangelog) must match VERSION ($version) on a tagged release.";
    }
  }

  if ($strict) {
    $latestTag = releaseLatestTag();
    $commits = releaseCollectCommits($latestTag);
    $analysis = releaseAnalyzeCommits($commits);

    if ($analysis['has_substantial'] && $headTagVersion === null) {
      $errors[] = 'Substantial unreleased changes detected (feat/fix/refactor/perf/breaking) without a release tag.';
    }
  }

  if ($errors !== []) {
    foreach ($errors as $error) {
      fwrite(STDERR, "[release-check] $error\n");
    }
    exit(1);
  }

  echo "release-check: OK\n";
} catch (Throwable $e) {
  fwrite(STDERR, '[release-check] ' . $e->getMessage() . "\n");
  exit(1);
}
