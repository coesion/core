<?php

require_once __DIR__ . '/release-common.php';

$bump = null;
$dryRun = false;
$push = false;

foreach (array_slice($argv, 1) as $arg) {
  if ($arg === '--dry-run') {
    $dryRun = true;
    continue;
  }

  if ($arg === '--push') {
    $push = true;
    continue;
  }

  if (in_array($arg, ['patch', 'minor', 'major'], true)) {
    $bump = $arg;
    continue;
  }

  fwrite(STDERR, "Unknown argument: $arg\n");
  exit(1);
}

try {
  if (!releaseRepoClean()) {
    throw new RuntimeException('Working tree is not clean. Commit or stash changes before release-cut.');
  }

  $version = releaseReadVersion();
  $coreVersion = releaseReadCoreConstVersion();
  if ($coreVersion !== $version) {
    throw new RuntimeException("Core::VERSION ($coreVersion) does not match VERSION ($version)");
  }

  $latestTag = releaseLatestTag();
  if ($latestTag !== null) {
    $tagVersion = releaseTagToVersion($latestTag);
    if ($tagVersion === null) {
      throw new RuntimeException("Latest tag '$latestTag' is not semver");
    }
    if ($tagVersion !== $version) {
      throw new RuntimeException("VERSION ($version) does not match latest tag $latestTag");
    }
  }

  $commits = releaseCollectCommits($latestTag);
  $analysis = releaseAnalyzeCommits($commits);

  if ($bump === null) {
    if ($analysis['bump'] === 'none') {
      throw new RuntimeException('No substantial commits found. Pass patch|minor|major explicitly to force a release.');
    }
    $bump = $analysis['bump'];
  }

  $next = releaseNextVersion($version, $bump);
  $tag = releaseVersionToTag($next);
  $entry = releaseRenderChangelogEntry($next, $analysis);

  if ($dryRun) {
    echo "Dry run only.\n";
    echo "- Current: $version\n";
    echo "- Bump: $bump\n";
    echo "- Next: $next\n";
    echo "- Tag: $tag\n\n";
    echo $entry;
    exit(0);
  }

  releaseWriteVersion($next);
  releaseWriteCoreConstVersion($next);
  releasePrependChangelogEntry($entry);

  releaseRunPassthru('php tools/build-core.php');
  releaseRunPassthru('php tools/build-artifact-repo.php');
  releaseRunPassthru('composer test');

  releaseRunPassthru('git add VERSION classes/Core.php CHANGELOG.md dist/core.php');
  releaseRunPassthru('git commit -m "Release ' . $tag . '"');
  releaseRunPassthru('git tag -a ' . escapeshellarg($tag) . ' -m ' . escapeshellarg('Release ' . $tag));

  if ($push) {
    releaseRunPassthru('git push');
    releaseRunPassthru('git push origin ' . escapeshellarg($tag));
  }

  echo "Release created: $tag\n";
  if (!$push) {
    echo "Tag is local only. Push with: git push && git push origin $tag\n";
  }
} catch (Throwable $e) {
  fwrite(STDERR, $e->getMessage() . "\n");
  exit(1);
}
