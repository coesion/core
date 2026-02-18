Repo is being modernized for PHP 8.5+ with PHPUnit 11 updates.
ripgrep (rg) is not installed in this environment; use PowerShell Select-String for searches.
Route currently uses optimized_tree and group-level pruning to avoid deep traversal; loop mode will be controlled via Options (core.route.loop_mode) rather than a Route API.
Benchmarks must live in a separate sub-app under benchmarks/ with their own composer.json/vendor; no new deps in root.
Loop mode dispatch now uses compiled_dispatcher (static map + regex dispatchers); compiled_tree remains for debug/trie usage.
Composer is already available in this WSL environment via /mnt/c/ProgramData/ComposerSetup/bin/composer.phar, which emits PHP 8.5 E_STRICT deprecation notices.
Dynamic prefix hint bucketing can become too granular (unique hints explode dispatcher bundles), causing regressions; apply a frequency threshold before using hints.
tools/serve-docs.php is a runtime docs server (query-based routing) and not suitable as-is for GitHub Pages static publishing; use a dedicated static export step.
Class documentation lives in docs/classes/ with 60 markdown files; they are not line-ending consistent (mixed formatting/blank-line patterns), so bulk doc transforms should key off heading anchors, not fixed line numbers.
Docs renderer caveat: tools/serve-docs.php defines CORE_DOCS_ASCII_FLAME flag at file scope; renderPage must receive that flag as a parameter (or declare global) or PHP emits undefined variable warnings at sidebar render lines.
Some docs/classes markdown files contain mixed line endings (CRLF/LF), which can surface as stray carriage-return characters during scripted text extraction and insertion.
Bulk markdown intro rewrites can accidentally stack multiple generated paragraphs in docs/classes; cleanup passes should first strip previously generated intro patterns before reinserting.
Loader::register maps class names directly to filenames (with original class case), so acronym class renames on case-sensitive filesystems must also rename the PHP file (e.g., CSRF -> classes/CSRF.php) to keep Loader autoload working.
Dynamic prefix hint bucketing can become too granular (unique hints explode dispatcher bundles), causing regressions; apply a frequency threshold before using hints.
Current repo discovery: tools/preload.php and tools/build-phar.php previously scanned only classes/*.php and excluded namespaced subfolders.
Class tree count at time of migration: 71 PHP class files total, 52 in classes/ root and 19 in subfolders.
Packaging/preload baseline after migration: build minified dist/core.php via tools/build-core.php and point opcache.preload directly to dist/core.php.
For repo split, current source repository still had package name `coesion/core`; this conflicts with artifact-only Packagist ownership, so source package metadata was moved to `coesion/core-dev`.
Artifact publishing now relies on generated `dist/artifact` payload with `composer.json` using `autoload.files` => ["core.php"] for automatic registration via `vendor/autoload.php`.
Unexpected drift discovered: classes/Core.php had VERSION=1.0.0 while package metadata was 1.1.0; runtime version can drift unless centralized.
Canonical release version policy now uses root VERSION file (plain X.Y.Z) and git tags must be vX.Y.Z.
Static docs builder (`tools/build-docs-site.php`) did not inject highlight.js CSS/JS, while runtime docs server (`tools/serve-docs.php`) already did; this mismatch caused unhighlighted code snippets on GitHub Pages output.
td CLI can return 'enable WAL mode: database is locked (261)' when multiple td commands run concurrently; run td operations sequentially in this repo.
release-cut currently fails at git add dist/core.php unless forced, because dist/ is gitignored in this workspace; use git add -f dist/core.php when finalizing a release commit.
rg is available in this macOS workspace; prior WSL-specific note about rg being missing does not apply here.
.sidecar/shells.json.lock can appear as a zero-byte local artifact during Codex sessions; exclude it from release commits unless it contains intentional data.
`td usage --new-session` can fail with database lock when td commands are run concurrently in the same turn; run td commands sequentially.
`docs/guides/Router-Benchmarks.md` last-updated timestamp can drift stale from generated benchmark artifacts; freshness checks should enforce a max age.
`composer proof-refresh` should not call `tools/benchmark_report.php` directly without an input benchmark JSON; in clean environments it fails unless `benchmarks/results/bench_*.json` exists.
`class_exists('Error')` is true on PHP 8+ because of the built-in `\Error` class, so that check does not validate the removed Core alias shim in `classes/Error.php`.
Weekly acquisition metrics (repo visits/clones) are not derivable from local repo state; automation must accept explicit KPI inputs (or authenticated API data) and should default to safe placeholders.
Issue template labels are stable and can drive weekly KPI counts via GitHub Search API: regression=`bug+agent`, workflow=`enhancement+agent`, proof=`documentation+agent`.
`release-policy.yml` currently runs on branch pushes and tag pushes; using `--strict` unconditionally on branch pushes blocks any substantial conventional commits (`feat/fix/refactor/perf/breaking`) until a tag exists, so strict should be tag-gated while branches run non-strict release checks.
`mirror-artifacts.yml` previously used a JS-only secret name (`CORE_JS_ARTIFACT_TOKEN`) while other artifact workflows already standardized on `CORE_ARTIFACT_TOKEN`; unify on CORE_ARTIFACT_TOKEN to avoid secret mismatch failures in JS mirror steps.
