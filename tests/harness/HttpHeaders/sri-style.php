<?php
/**
 * Subresource Integrity stylesheet asset for Playwright functional tests.
 *
 * The PHP built-in server serves this file directly (outside Prado routing). It
 * returns the CSS of sri-style-content.php with `Access-Control-Allow-Origin: *`,
 * so the stylesheet loads cross-origin (the page is served from 127.0.0.1 and
 * this asset from localhost) with `crossorigin="anonymous"`, which Subresource
 * Integrity requires for cross-origin resources.
 */

$css = include __DIR__ . '/sri-style-content.php';

header('Content-Type: text/css');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: no-store');
echo $css;
