Repo is being modernized for PHP 8.5+ with PHPUnit 11 updates.
ripgrep (rg) is not installed in this environment; use PowerShell Select-String for searches.
Route currently uses optimized_tree and group-level pruning to avoid deep traversal; loop mode will be controlled via Options (core.route.loop_mode) rather than a Route API.
Benchmarks must live in a separate sub-app under benchmarks/ with their own composer.json/vendor; no new deps in root.
Loop mode dispatch now uses compiled_dispatcher (static map + regex dispatchers); compiled_tree remains for debug/trie usage.
Composer is already available in this WSL environment via /mnt/c/ProgramData/ComposerSetup/bin/composer.phar, which emits PHP 8.5 E_STRICT deprecation notices.
Dynamic prefix hint bucketing can become too granular (unique hints explode dispatcher bundles), causing regressions; apply a frequency threshold before using hints.