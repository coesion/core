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
