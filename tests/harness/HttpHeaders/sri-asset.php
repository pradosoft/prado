<?php
/**
 * Subresource Integrity test asset for Playwright functional tests.
 *
 * The PHP built-in server serves this file directly (outside Prado routing).
 * It returns a small JavaScript body with an `Access-Control-Allow-Origin: *`
 * header so the script can be loaded cross-origin (the test page is served from
 * 127.0.0.1 while this asset is requested via localhost) with
 * `crossorigin="anonymous"`, which Subresource Integrity requires for
 * cross-origin resources.
 *
 * The body is sourced from sri-content.php so SriPage can hash the identical
 * bytes when pinning the integrity value.
 */

$js = include __DIR__ . '/sri-content.php';

header('Content-Type: application/javascript');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store');
echo $js;
