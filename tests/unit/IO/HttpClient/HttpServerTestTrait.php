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
 *
 * On Windows the child process is spawned with `bypass_shell` and
 * `create_no_window` so that it is not attached to the parent's console.
 * Without this, `proc_terminate()` broadcasts a `CTRL_C_EVENT` to the entire
 * console process group, which causes the CMD batch runner (`phpunit.bat`) to
 * print "Terminate batch job (Y/N)?" and hang indefinitely.
 *
 * Shutdown always terminates the child process before closing its pipes.
 * Windows `proc_open()` pipes do not support non-blocking mode
 * (`stream_set_blocking()` is a no-op), so draining a pipe before the child
 * exits blocks forever.
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
		// On Windows, detach the child from the console so that proc_terminate()
		// uses TerminateProcess() directly instead of broadcasting a CTRL_C_EVENT
		// to the whole process group (which would hang the phpunit.bat runner).
		$options = PHP_OS_FAMILY === 'Windows'
			? ['bypass_shell' => true, 'create_no_window' => true]
			: [];
		$proc = proc_open($cmd, $descriptors, $pipes, null, null, $options ?: null);
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
			// Terminate FIRST, then close pipes.
			//
			// The naive order (drain → terminate) deadlocks on Windows: PHP's
			// stream_set_blocking() is a no-op for proc_open() pipes on that
			// platform, so stream_get_contents() blocks until the child exits,
			// but the child never exits because proc_terminate() hasn't been
			// called yet.
			//
			// Terminating first ensures the child process is dead (or dying)
			// before we touch the pipes, so their read ends reach EOF quickly
			// and fclose() returns without spinning.
			$status = proc_get_status(self::$serverProc);
			if (!empty($status['running'])) {
				proc_terminate(self::$serverProc);
			}
			foreach (self::$serverPipes as $pipe) {
				if (is_resource($pipe)) {
					@fclose($pipe);
				}
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
