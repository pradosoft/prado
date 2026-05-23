#!/usr/bin/env bash
# Runs the Selenium functional test suite.
# Starts the PHP server and geckodriver, runs phpunit, then stops them on exit.
set -euo pipefail

PHP_PID=""
GECKO_PID=""
PHPUNIT_PID=""

cleanup() {
	[ -n "$PHPUNIT_PID" ] && kill "$PHPUNIT_PID" 2>/dev/null || true
	[ -n "$PHP_PID" ]     && kill "$PHP_PID"     2>/dev/null || true
	[ -n "$GECKO_PID" ]   && kill "$GECKO_PID"   2>/dev/null || true
}
trap cleanup EXIT SIGTERM SIGINT SIGHUP

echo "Starting PHP server on port 8037..."
php -q -S 127.0.0.1:8037 -t ./ &
PHP_PID=$!

echo "Starting geckodriver on port 4444..."
GECKODRIVER=$(command -v geckodriver 2>/dev/null \
	|| echo "/usr/local/share/gecko_driver/geckodriver")
"$GECKODRIVER" &>/dev/null &
GECKO_PID=$!

# Wait for both to be ready
for i in $(seq 1 150); do
	nc -z 127.0.0.1 8037 2>/dev/null && \
	nc -z 127.0.0.1 4444 2>/dev/null && break
	sleep 0.1
done

# Run phpunit in the background so the parent-process poll can run alongside it.
vendor/bin/phpunit --testsuite selenium &
PHPUNIT_PID=$!

# Record the PID of the process that launched this script.
# When that process disappears (even via SIGKILL) we stop everything.
PARENT_PID=$PPID

while kill -0 "$PHPUNIT_PID" 2>/dev/null; do
	if ! kill -0 "$PARENT_PID" 2>/dev/null; then
		# Parent is gone — EXIT trap will clean up.
		exit 0
	fi
	sleep 0.1
done

wait "$PHPUNIT_PID"
