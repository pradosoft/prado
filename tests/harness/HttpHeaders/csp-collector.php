<?php
/**
 * CSP violation report collector for Playwright functional tests.
 *
 * The PHP built-in server serves this file directly (outside Prado routing).
 * A per-test isolation token (query-param `t`) ensures each test run has its
 * own storage, so the workers: 1 serial test suite never sees stale reports.
 *
 * POST  Appends the raw request body to a newline-delimited temp file and
 *       responds with 204 No Content (the expected response for CSP collectors).
 *
 * GET   Returns all stored bodies as a JSON array of strings (one element per
 *       report body) and atomically clears the file so the next GET starts
 *       fresh within the same test run.
 */

$token = preg_replace('/[^a-zA-Z0-9\-_]/', '', (string) ($_GET['t'] ?? 'default'));
$dir   = sys_get_temp_dir() . '/prado-csp-collector/';

if (!is_dir($dir)) {
	@mkdir($dir, 0777, true);
}

$file = $dir . $token . '.ndjson';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$body = trim((string) file_get_contents('php://input'));
	if ($body !== '') {
		file_put_contents($file, $body . "\n", FILE_APPEND | LOCK_EX);
	}
	http_response_code(204);
	exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
	header('Content-Type: application/json');
	header('Cache-Control: no-store');

	if (!is_file($file)) {
		echo '[]';
		exit;
	}

	$content = (string) file_get_contents($file);
	@unlink($file);

	$lines = array_values(array_filter(array_map('trim', explode("\n", $content))));
	echo json_encode($lines);
	exit;
}

http_response_code(405);
