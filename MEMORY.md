# Memory

- Repo is being modernized for PHP 8.5+ with PHPUnit 11 updates.
- Added new tests covering classes that previously had no dedicated tests (cache, filesystem, email, HTTP, CLI/CSV, traits, loader, message/session, redirect, relation, persistence, shell, zip, view, job).
- Local PHP is 8.5.1; tests can be run directly without Docker in this environment.
- ripgrep (`rg`) is not installed in this environment; use PowerShell `Select-String` for searches.
- GitHub wiki content has been merged into `docs/classes/`; `docs/wiki/` removed and no wiki references remain.
- User wants tests run after any plan implementation or significant change.
- Rebrand project to Coesion/Core, remove all Coesion references, set package to coesion/core, author Stefano Azzolini <lastguest@gmail.com>, reset version to 1.0.0, and start with blank git history.
