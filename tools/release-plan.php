<?php

require_once __DIR__ . '/release-common.php';

try {
  $version = releaseReadVersion();
  $coreVersion = releaseReadCoreConstVersion();
  if ($coreVersion !== $version) {
    throw new RuntimeException("Core::VERSION ($coreVersion) does not match VERSION ($version)");
  }

  $latestTag = releaseLatestTag();
  if ($latestTag !== null) {
    $tagVersion = releaseTagToVersion($latestTag);
    if ($tagVersion === null) {
      throw new RuntimeException("Latest tag '$latestTag' is not a semver tag");
    }
    if ($tagVersion !== $version) {
      throw new RuntimeException("VERSION ($version) does not match latest tag $latestTag");
    }
  }

  $commits = releaseCollectCommits($latestTag);
  $analysis = releaseAnalyzeCommits($commits);

  if ($analysis['bump'] === 'none') {
    echo "No release needed. No substantial commits since ";
    echo $latestTag ?: 'repository start';
    echo ".\n";
    exit(0);
  }

  $next = releaseNextVersion($version, $analysis['bump']);
  $entry = releaseRenderChangelogEntry($next, $analysis);

  $releaseDir = releaseRootPath() . DIRECTORY_SEPARATOR . '.release';
  releaseEnsureDir($releaseDir);

  $planPath = $releaseDir . DIRECTORY_SEPARATOR . 'next.json';
  $planData = [
    'current_version' => $version,
    'latest_tag' => $latestTag,
    'bump' => $analysis['bump'],
    'next_version' => $next,
    'commit_count' => count($commits),
    'generated_at_utc' => gmdate('c'),
  ];

  $json = json_encode($planData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
  if ($json === false || file_put_contents($planPath, $json . "\n") === false) {
    throw new RuntimeException("Failed writing release plan $planPath");
  }

  $notesPath = $releaseDir . DIRECTORY_SEPARATOR . 'next.md';
  if (file_put_contents($notesPath, $entry) === false) {
    throw new RuntimeException("Failed writing release notes $notesPath");
  }

  echo "Release plan ready.\n";
  echo "- Current: $version\n";
  echo "- Bump: {$analysis['bump']}\n";
  echo "- Next: $next\n";
  echo "- Plan: $planPath\n";
  echo "- Notes: $notesPath\n";
} catch (Throwable $e) {
  fwrite(STDERR, $e->getMessage() . "\n");
  exit(1);
}
