<?php

/**
 * PHP built-in server router for HttpClient integration tests.
 *
 * Spawned by HttpServerTestTrait. Routes a small set of endpoints used by
 * TCurlHttpClientTest, TFopenHttpClientTest, and TStreamDownloaderTest.
 */

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

// /echo — JSON describing the request itself (used for verb / body / header tests)
if ($path === '/echo') {
	header('Content-Type: application/json');
	$headers = function_exists('getallheaders') ? getallheaders() : [];
	echo json_encode([
		'method' => $method,
		'path' => $_SERVER['REQUEST_URI'],
		'headers' => $headers,
		'body' => file_get_contents('php://input'),
	]);
	return true;
}

// /status/{code} — return the given status code with a small body
if (preg_match('#^/status/(\d{3})$#', $path, $m)) {
	$code = (int) $m[1];
	http_response_code($code);
	header('Content-Type: text/plain');
	echo "status {$code}";
	return true;
}

// /redirect — 302 → /echo
if ($path === '/redirect') {
	header('Location: /echo');
	http_response_code(302);
	return true;
}

// /headers — emits a few custom response headers
if ($path === '/headers') {
	header('X-Custom-A: alpha');
	header('X-Custom-B: bravo');
	header('Content-Type: text/plain');
	echo 'ok';
	return true;
}

// /large?bytes=N — emits N predictable bytes (default 16KiB) for stream tests
if ($path === '/large') {
	$bytes = (int) ($_GET['bytes'] ?? 16384);
	$bytes = max(1, min($bytes, 1048576)); // cap at 1 MiB to keep tests fast
	header('Content-Type: application/octet-stream');
	header('Content-Length: ' . $bytes);
	// stream out in 1KiB chunks
	$chunk = str_repeat('A', 1024);
	$remaining = $bytes;
	while ($remaining > 0) {
		$out = substr($chunk, 0, min(1024, $remaining));
		echo $out;
		flush();
		$remaining -= strlen($out);
	}
	return true;
}

// /slow — sleeps before responding; used for timeout tests
if ($path === '/slow') {
	$delay = (float) ($_GET['delay'] ?? 1.0);
	usleep((int) ($delay * 1_000_000));
	echo 'slow';
	return true;
}

// default — 200 hello
header('Content-Type: text/plain');
echo 'hello';
return true;
