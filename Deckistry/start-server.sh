#!/usr/bin/env bash
cd "$(dirname "$0")"
exec /c/wamp64/bin/php/php8.3.14/php -S 127.0.0.1:8001 -t public/
