<?php

/**
 * HttpServerTestTrait — boots PHP's built-in web server for integration tests.
 *
 * Usage in a TestCase:
 *
 *     use HttpServerTestTrait;
 *     public static function setUpBeforeClass(): void { self::startHttpServer(); }
 *     public static function tearDownAfterClass(): void { self::stopHttpServer(); }
 *
 * The server runs the router at fixtures/test-server.php. URLs are obtained via
 * `self::url('/path')`. If the server fails to come up (e.g. the runner has no
 * permission to bind to a local port), tests are marked skipped automatically.
 */
trait HttpServerTestTrait
{
	/** @var resource|null */
	private static $serverProc = null;
	private static array $serverPipes = [];
	private static int $serverPort = 0;
	private static ?string $serverSkipReason = null;

	protected static function startHttpServer(): void
	{
		if (self::$serverProc !== null) {
			return;
		}

		$router = __DIR__ . '/fixtures/test-server.php';
		$port = self::findFreePort();
		if ($port === 0) {
			self::$serverSkipReason = 'Could not bind a free port for the test server.';
			return;
		}

		$cmd = sprintf(
			'%s -S 127.0.0.1:%d %s',
			escapeshellarg(PHP_BINARY),
			$port,
			escapeshellarg($router)
		);
		$descriptors = [
			0 => ['pipe', 'r'],
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w'],
		];
		$proc = proc_open($cmd, $descriptors, $pipes);
		if (!is_resource($proc)) {
			self::$serverSkipReason = 'proc_open() failed to start the test server.';
			return;
		}

		// Close stdin and make stdout/stderr non-blocking so they don't fill up.
		fclose($pipes[0]);
		stream_set_blocking($pipes[1], false);
		stream_set_blocking($pipes[2], false);

		self::$serverProc = $proc;
		self::$serverPipes = [$pipes[1], $pipes[2]];
		self::$serverPort = $port;

		// Poll until the port answers (up to ~3 seconds).
		for ($i = 0; $i < 60; $i++) {
			$fp = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.1);
			if ($fp) {
				fclose($fp);
				return;
			}
			usleep(50_000);
		}

		self::$serverSkipReason = "Test HTTP server failed to come up on port {$port}.";
		self::stopHttpServer();
	}

	protected static function stopHttpServer(): void
	{
		if (self::$serverProc !== null) {
			// Drain pipes so the child can exit cleanly.
			foreach (self::$serverPipes as $pipe) {
				if (is_resource($pipe)) {
					@stream_get_contents($pipe);
					@fclose($pipe);
				}
			}
			$status = proc_get_status(self::$serverProc);
			if (!empty($status['running'])) {
				proc_terminate(self::$serverProc, 9);
			}
			proc_close(self::$serverProc);
		}
		self::$serverProc = null;
		self::$serverPipes = [];
	}

	/**
	 * Builds a fully qualified URL for a path on the test server.
	 */
	protected static function url(string $path): string
	{
		return 'http://127.0.0.1:' . self::$serverPort . $path;
	}

	/**
	 * Marks the current test skipped if the server is not running.
	 */
	protected function requireRunningHttpServer(): void
	{
		if (self::$serverProc === null) {
			$this->markTestSkipped(self::$serverSkipReason ?? 'Test HTTP server is not available.');
		}
	}

	private static function findFreePort(): int
	{
		$sock = @stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
		if (!$sock) {
			return 0;
		}
		$name = stream_socket_get_name($sock, false);
		fclose($sock);
		$parts = explode(':', (string) $name);
		return (int) end($parts);
	}
}
