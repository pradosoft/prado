<?php

use Prado\IO\HttpClient\TStreamDownloader;
use Prado\IO\HttpClient\THttpClientException;
use Prado\IO\TStreamNotificationCallback;

require_once __DIR__ . '/HttpServerTestTrait.php';

/**
 * Tests for TStreamDownloader.
 *
 * Covers in-memory streaming via download(), file destinations + resource
 * destinations via downloadTo(), notification callback wiring, chunk-size
 * behaviour, and error paths. The in-memory and download-to-file branches
 * are exercised against PHP's built-in test server; file:// transfers exercise
 * the non-HTTP path that synthesizes a 200 response from a successful fopen.
 */
class TStreamDownloaderTest extends PHPUnit\Framework\TestCase
{
	use HttpServerTestTrait;

	private string $tmpDir;
	/** @var string[] */
	private array $tmpFiles = [];

	public static function setUpBeforeClass(): void
	{
		self::startHttpServer();
	}

	public static function tearDownAfterClass(): void
	{
		self::stopHttpServer();
	}

	protected function setUp(): void
	{
		$this->tmpDir = sys_get_temp_dir();
	}

	protected function tearDown(): void
	{
		foreach ($this->tmpFiles as $f) {
			if (is_file($f)) {
				@unlink($f);
			}
		}
		$this->tmpFiles = [];
	}

	private function tmp(string $suffix = '.bin'): string
	{
		$path = $this->tmpDir . '/prado-streamdl-' . uniqid('', true) . $suffix;
		$this->tmpFiles[] = $path;
		return $path;
	}

	// ── Accessors ─────────────────────────────────────────────────────────────

	public function testNotificationAccessor(): void
	{
		$d = new TStreamDownloader();
		$this->assertNull($d->getNotification());
		$cb = new TStreamNotificationCallback();
		$d->setNotification($cb);
		$this->assertSame($cb, $d->getNotification());
		$d->setNotification(null);
		$this->assertNull($d->getNotification());
	}

	public function testChunkSizeAccessorClampsToOne(): void
	{
		$d = new TStreamDownloader();
		$this->assertSame(8192, $d->getChunkSize());
		$d->setChunkSize(1024);
		$this->assertSame(1024, $d->getChunkSize());
		$d->setChunkSize(0);
		$this->assertSame(1, $d->getChunkSize());
		$d->setChunkSize(-50);
		$this->assertSame(1, $d->getChunkSize());
	}

	// ── In-memory streaming via download() ───────────────────────────────────

	public function testDownloadBuffersBodyInMemory(): void
	{
		$this->requireRunningHttpServer();
		$d = new TStreamDownloader();
		$response = $d->download('GET', self::url('/'));
		$this->assertSame(200, $response->getStatusCode());
		$this->assertSame('hello', $response->getBody());
	}

	public function testDownloadLargePayloadRoundTrips(): void
	{
		$this->requireRunningHttpServer();
		$d = new TStreamDownloader();
		$d->setChunkSize(512); // exercise multi-chunk loop
		$response = $d->download('GET', self::url('/large?bytes=4096'));
		$this->assertSame(200, $response->getStatusCode());
		$this->assertSame(4096, strlen($response->getBody()));
		$this->assertSame(str_repeat('A', 4096), $response->getBody());
	}

	// ── downloadTo() → file path ─────────────────────────────────────────────

	public function testDownloadToFilePathWritesFile(): void
	{
		$this->requireRunningHttpServer();
		$d = new TStreamDownloader();
		$dest = $this->tmp();

		$response = $d->downloadTo(self::url('/large?bytes=2048'), $dest);
		$this->assertSame(200, $response->getStatusCode());
		$this->assertSame('', $response->getBody()); // body went to disk
		$this->assertFileExists($dest);
		$this->assertSame(2048, filesize($dest));
		$this->assertSame(str_repeat('A', 2048), file_get_contents($dest));
	}

	public function testDownloadToFilePathOverwritesExisting(): void
	{
		$this->requireRunningHttpServer();
		$d = new TStreamDownloader();
		$dest = $this->tmp();
		file_put_contents($dest, 'old content that should be replaced');

		$d->downloadTo(self::url('/'), $dest);
		$this->assertSame('hello', file_get_contents($dest));
	}

	public function testDownloadToUnwritableDestinationThrows(): void
	{
		$this->requireRunningHttpServer();
		$d = new TStreamDownloader();
		$this->expectException(THttpClientException::class);
		// /no-such-dir/ should never exist
		$d->downloadTo(self::url('/'), '/no-such-dir-987654/dest.bin');
	}

	// ── downloadTo() → stream resource ───────────────────────────────────────

	public function testDownloadToOpenStreamResource(): void
	{
		$this->requireRunningHttpServer();
		$d = new TStreamDownloader();
		$dest = $this->tmp();
		$fp = fopen($dest, 'wb');
		try {
			$response = $d->downloadTo(self::url('/large?bytes=1024'), $fp);
			$this->assertSame(200, $response->getStatusCode());
		} finally {
			fclose($fp);
		}
		$this->assertSame(1024, filesize($dest));
	}

	public function testDownloadToRejectsInvalidDestinationType(): void
	{
		$this->requireRunningHttpServer();
		$d = new TStreamDownloader();
		$this->expectException(THttpClientException::class);
		// Integer is neither a path nor a resource
		$d->downloadTo(self::url('/'), 42);
	}

	// ── file:// fallback (non-HTTP scheme) ───────────────────────────────────

	public function testDownloadFromFileSchemeSynthesisesSuccess(): void
	{
		$src = $this->tmp('.src');
		file_put_contents($src, 'local content from disk');

		$d = new TStreamDownloader();
		$dest = $this->tmp('.dst');
		$response = $d->downloadTo('file://' . $src, $dest);

		$this->assertSame(200, $response->getStatusCode());
		$this->assertSame('local content from disk', file_get_contents($dest));
	}

	public function testDownloadFromPlainLocalPath(): void
	{
		// fopen accepts plain filesystem paths (no scheme); the HTTP stream
		// context options are simply ignored. isHttpScheme() returns false
		// because parse_url() yields a null scheme, so a 200 is synthesised.
		$src = $this->tmp('.src');
		file_put_contents($src, 'plain path content');

		$d = new TStreamDownloader();
		$dest = $this->tmp('.dst');
		$response = $d->downloadTo($src, $dest);

		$this->assertSame(200, $response->getStatusCode());
		$this->assertSame('plain path content', file_get_contents($dest));
	}

	public function testDownloadInMemoryFromPlainLocalPath(): void
	{
		$src = $this->tmp('.src');
		file_put_contents($src, 'inline body');

		$d = new TStreamDownloader();
		$response = $d->download('GET', $src);

		$this->assertSame(200, $response->getStatusCode());
		$this->assertSame('inline body', $response->getBody());
	}

	public function testDownloadFromNonExistentFileSchemeThrows(): void
	{
		$d = new TStreamDownloader();
		$this->expectException(THttpClientException::class);
		$d->download('GET', 'file:///no/such/path/never/exists');
	}

	// ── Notification wiring ───────────────────────────────────────────────────

	public function testNotificationCallbackReceivesEvents(): void
	{
		$this->requireRunningHttpServer();

		$lifecycle = [];
		$callback = new TStreamNotificationCallback();
		$callback->onConnected[] = function () use (&$lifecycle) { $lifecycle[] = 'connected'; };
		$callback->onProgress[] = function () use (&$lifecycle) { $lifecycle[] = 'progress'; };
		$callback->onCompleted[] = function () use (&$lifecycle) { $lifecycle[] = 'completed'; };
		$callback->onFileSize[] = function () use (&$lifecycle) { $lifecycle[] = 'filesize'; };

		$d = new TStreamDownloader();
		$d->setNotification($callback);
		$d->setChunkSize(256);
		$response = $d->download('GET', self::url('/large?bytes=4096'));

		$this->assertSame(200, $response->getStatusCode());
		// The HTTP wrapper fires at least one lifecycle event for any successful
		// transfer (connect/progress/filesize/completed). We don't pin a specific
		// set because different SAPIs / wrapper versions differ.
		$this->assertNotEmpty(
			$lifecycle,
			'expected at least one notification event during transfer; got none'
		);
	}

	public function testNotificationParameterCarriesBytesTransferred(): void
	{
		$this->requireRunningHttpServer();
		$callback = new TStreamNotificationCallback();
		$d = new TStreamDownloader();
		$d->setNotification($callback);
		$d->download('GET', self::url('/large?bytes=2048'));

		// After completion the callback should record at least some bytes
		$this->assertGreaterThan(0, (int) $callback->getBytesTransferred());
	}
}
