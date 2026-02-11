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
GitHub Actions workflow expressions: step-level `if:` cannot reference `secrets.*` directly; map secrets into job/workflow `env` first and gate steps with `env.*` conditions.
