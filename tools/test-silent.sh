#!/usr/bin/env bash
set -euo pipefail

# Run static analysis and tests without Composer's own deprecation noise.
vendor/bin/phpstan analyse --configuration=phpstan.neon --memory-limit=1G
vendor/bin/phpunit --colors=always --configuration=phpunit.xml
