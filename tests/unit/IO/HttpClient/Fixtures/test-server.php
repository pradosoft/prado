<?php

/**
 * PHP built-in server router for HttpClient integration tests.
 *
 * Spawned by HttpServerTestTrait. Routes a small set of endpoints used by
 * the IHttpHandler conformance tests and THttpClientTest.
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

// /redirect?status=302&to=/echo — a redirect with the given status (default 302) to the given location (default /echo)
if ($path === '/redirect') {
	header('Location: ' . ($_GET['to'] ?? '/echo'));
	http_response_code((int) ($_GET['status'] ?? 302));
	return true;
}

// /redirect-loop — redirects to itself forever
if ($path === '/redirect-loop') {
	header('Location: /redirect-loop');
	http_response_code(302);
	return true;
}

// /gzip and /deflate — a body compressed with the named content coding, sent whatever the request accepts
if ($path === '/gzip' || $path === '/deflate') {
	$data = $path === '/gzip' ? gzencode('compressed body') : gzcompress('compressed body');
	header('Content-Type: text/plain');
	header('Content-Encoding: ' . substr($path, 1));
	header('Content-Length: ' . strlen($data));
	echo $data;
	return true;
}

// /cookies — two Set-Cookie header lines
if ($path === '/cookies') {
	header('Set-Cookie: a=1; Path=/', false);
	header('Set-Cookie: b=2; Path=/', false);
	echo 'cookies';
	return true;
}

// /unsized — a body without Content-Length, flushed in parts
if ($path === '/unsized') {
	header('Content-Type: text/plain');
	echo 'part1-';
	flush();
	echo 'part2';
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
