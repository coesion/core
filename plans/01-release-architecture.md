# 01 - Release Architecture

## System Diagram

```text
Factory repo (source, tests, build tools)
  | tag push vX.Y.Z
  +--> PHP workflow
  |      -> build dist/core.php
  |      -> build dist/artifact
  |      -> validate artifact contract
  |      -> mirror publish to php artifact repo
  |
  +--> JS workflow
         -> test/build js
         -> build js/dist/artifact
         -> validate artifact contract
         -> mirror publish to js artifact repo
```

## Source of Truth

- PHP runtime version: `VERSION` and `Core::VERSION` must match.
- JS runtime version: `js/VERSION` and `js/package.json` version must match.
- Artifact destinations: `release-targets.json`.

## Safety Model

- No publish if manifest validation fails.
- No publish if artifact contract validation fails.
- No commit/push if mirror repo has no generated diff.
