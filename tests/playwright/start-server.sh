#!/usr/bin/env bash
# Starts the PHP built-in server for Playwright functional tests on port 8037
# and guarantees it is stopped when this script exits.
#
# Port 8037 is the shared functional test server port used by both Playwright
# and Selenium.  It is unprivileged (no sudo needed) and avoids conflicts with
# port 8080 (common development default for TWebServerAction) and other ports.
# Both tests/harness/ and vendor/pradosoft/prado-demos/ are served from the
# repo root under this single server via their URL path prefixes.
#
# Two complementary shutdown mechanisms:
#
#   1. Signal trap — catches EXIT, SIGTERM, SIGINT, SIGHUP.
#      Playwright sends SIGTERM to the webServer process on clean shutdown
#      or Ctrl-C; the trap fires and kills PHP before this script exits.
#
#   2. Parent-process poll — checks every 0.1 s that the process which
#      launched this script (Playwright's node runner) is still alive.
#      If Playwright is killed with SIGKILL, no signal reaches us, but
#      the poll detects the orphaned state and triggers cleanup via the
#      EXIT trap.

set -euo pipefail

PHP_PID=""

cleanup() {
	if [ -n "$PHP_PID" ] && kill -0 "$PHP_PID" 2>/dev/null; then
		kill "$PHP_PID" 2>/dev/null || true
		wait "$PHP_PID" 2>/dev/null || true
	fi
}
trap cleanup EXIT SIGTERM SIGINT SIGHUP

# Start the PHP server, capturing its PID.
php -q -S 127.0.0.1:8037 -t ./ &
PHP_PID=$!

# Record the PID of the process that launched this script.
# When that process disappears (even via SIGKILL) we stop the server.
PARENT_PID=$PPID

while kill -0 "$PHP_PID" 2>/dev/null; do
	if ! kill -0 "$PARENT_PID" 2>/dev/null; then
		# Parent is gone — EXIT trap will clean up PHP.
		exit 0
	fi
	sleep 0.1
done
