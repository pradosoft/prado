<?php
/**
 * Cross-origin script file for the CSP callback functional tests.
 *
 * The PHP built-in server serves this file directly (outside Prado routing).
 * CspCallbackPage requests it via `localhost` while the page is served from
 * `127.0.0.1`, so `script-src 'self'` does not cover it and only the page nonce
 * lets it run.
 */

header('Content-Type: application/javascript');
header('Cache-Control: no-store');
echo "document.documentElement.dataset.callbackFileRan = '1';\n";
