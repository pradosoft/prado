<?php

namespace Prado\Test\Unit\Web\UI;

use Prado\Exceptions\TConfigurationException;
use Prado\IO\Compression\TCompression;
use Prado\IO\Compression\ICompressionConfigurable;
use Prado\IO\Compression\TCompressionConfig;
use Prado\Web\UI\IPageStatePersister;
use Prado\Web\UI\TCachePageStatePersister;
use Prado\Web\UI\TPage;
use Prado\Web\UI\TPageStateCompressionConfig;
use Prado\Web\UI\TPageStateFormatter;
use Prado\Web\UI\TPageStatePersister;
use Prado\Web\UI\TSessionPageStatePersister;

/**
 * A persister written against the pre-4.4.0 {@see IPageStatePersister}, holding no
 * compression settings of its own.
 */
class TLegacyPageStatePersister extends \Prado\TComponent implements IPageStatePersister
{
	private $_page;

	public function getPage()
	{
		return $this->_page;
	}

	public function setPage(TPage $page)
	{
		$this->_page = $page;
	}

	public function save($state)
	{
		$this->_page->setClientState(TPageStateFormatter::serialize($this->_page, $state));
	}

	public function load()
	{
		return TPageStateFormatter::unserialize($this->_page, $this->_page->getRequestClientState());
	}
}

class TPageStateFormatterTest extends \PHPUnit\Framework\TestCase
{
	/**
	 * Create a page with every state transformation disabled so that
	 * TPageStateFormatter produces a plain base64(serialize($data)) string.
	 * Each test turns back on the single feature it exercises.
	 */
	private function newPage(): TPage
	{
		$page = new TPage();
		$page->setEnableStateValidation(false);
		$page->setEnableStateEncryption(false);
		$page->setEnableStateCompression(false);
		$page->setEnableStateIGBinary(false);
		return $page;
	}

	/**
	 * @param TPage $page the page whose state settings are wanted.
	 * @return TCompressionConfig the compression settings the page state is written under.
	 */
	private function compressionOf(TPage $page): TCompressionConfig
	{
		return $page->getStateCompression();
	}

	/**
	 * Run a callable with an error handler that records the PHP diagnostics a PRADO
	 * application would report. A diagnostic raised under the `@` operator is skipped,
	 * matching the `error_reporting()` mask {@see \Prado\Prado::phpErrorHandler()} applies.
	 * @param callable $callable the code under test
	 * @return array [return value of the callable, list of recorded messages]
	 */
	private function captureErrors(callable $callable): array
	{
		$errors = [];
		set_error_handler(function ($severity, $message) use (&$errors) {
			if (error_reporting() & $severity) {
				$errors[] = $message;
			}
			return true;
		});
		try {
			$result = $callable();
		} finally {
			restore_error_handler();
		}
		return [$result, $errors];
	}

	/**
	 * Skip a test when the igbinary extension is unavailable; the IGBinary
	 * branches guard on extension_loaded() and fall back to serialize().
	 */
	private function requireIGBinary(): void
	{
		if (!extension_loaded('igbinary')) {
			$this->markTestSkipped('The igbinary extension is not loaded.');
		}
	}

	/**
	 * Skip a test when a content coding's codec cannot run here; the compression
	 * branches guard on TCompression::isAvailable() and store the state uncompressed.
	 */
	private function requireCodec(string $method): void
	{
		if (!TCompression::isAvailable($method)) {
			$this->markTestSkipped("The '$method' codec is not available.");
		}
	}

	// -----------------------------------------------------------------------
	// unserialize() — missing and unusable state
	// -----------------------------------------------------------------------

	public function testUnserializeNullReturnsNull(): void
	{
		$page = $this->newPage();

		[$result, $errors] = $this->captureErrors(fn () => TPageStateFormatter::unserialize($page, null));

		$this->assertNull($result);
		$this->assertSame([], $errors, 'A null page state must not raise a PHP diagnostic');
	}

	public function testUnserializeEmptyStringReturnsNull(): void
	{
		$page = $this->newPage();

		[$result, $errors] = $this->captureErrors(fn () => TPageStateFormatter::unserialize($page, ''));

		$this->assertNull($result);
		$this->assertSame([], $errors, 'An empty page state must not raise a PHP diagnostic');
	}

	public function testUnserializeNonBase64ReturnsNull(): void
	{
		$page = $this->newPage();

		// base64_decode() drops the invalid characters, leaving an empty string.
		[$result, $errors] = $this->captureErrors(fn () => TPageStateFormatter::unserialize($page, '!!!'));

		$this->assertNull($result);
		$this->assertSame([], $errors);
	}

	public function testUnserializeNullWithAllFeaturesEnabledReturnsNull(): void
	{
		$page = new TPage();
		$page->setEnableStateEncryption(true);

		// Default page settings (validation, compression, IGBinary) plus encryption:
		// the guard returns before any security manager or extension call.
		[$result, $errors] = $this->captureErrors(fn () => TPageStateFormatter::unserialize($page, null));

		$this->assertNull($result);
		$this->assertSame([], $errors);
	}

	// -----------------------------------------------------------------------
	// Plain state — no validation, compression, encryption, or IGBinary
	// -----------------------------------------------------------------------

	public function testSerializeProducesBase64OfSerializedData(): void
	{
		$page = $this->newPage();
		$data = ['test' => 'value', 'num' => 42];

		$this->assertEquals(base64_encode(serialize($data)), TPageStateFormatter::serialize($page, $data));
	}

	public function testUnserializeRestoresSerializedData(): void
	{
		$page = $this->newPage();
		$data = ['hello' => 'world', 'nested' => ['a', 'b', 3]];

		$state = TPageStateFormatter::serialize($page, $data);

		$this->assertEquals($data, TPageStateFormatter::unserialize($page, $state));
	}

	public function testUnserializeRestoresSerializedFalse(): void
	{
		$page = $this->newPage();

		$state = TPageStateFormatter::serialize($page, false);

		$this->assertFalse(TPageStateFormatter::unserialize($page, $state));
	}

	// -----------------------------------------------------------------------
	// EnableStateValidation
	// -----------------------------------------------------------------------

	public function testSerializeWithValidationSignsTheData(): void
	{
		$page = $this->newPage();
		$page->setEnableStateValidation(true);
		$data = ['signed' => true];

		$state = TPageStateFormatter::serialize($page, $data);

		$this->assertStringEndsWith(serialize($data), base64_decode($state));
		$this->assertNotEquals(base64_encode(serialize($data)), $state, 'Validation must prepend an HMAC');
	}

	public function testUnserializeWithValidationRestoresData(): void
	{
		$page = $this->newPage();
		$page->setEnableStateValidation(true);
		$data = ['user' => 'bob', 'count' => 7];

		$state = TPageStateFormatter::serialize($page, $data);

		$this->assertEquals($data, TPageStateFormatter::unserialize($page, $state));
	}

	public function testUnserializeWithValidationRejectsTamperedData(): void
	{
		$page = $this->newPage();
		$page->setEnableStateValidation(true);

		$raw = base64_decode(TPageStateFormatter::serialize($page, ['user' => 'bob']));
		$raw[strlen($raw) - 1] = ($raw[strlen($raw) - 1] === 'A') ? 'B' : 'A';

		$this->assertNull(TPageStateFormatter::unserialize($page, base64_encode($raw)));
	}

	public function testUnserializeWithValidationRejectsUnsignedData(): void
	{
		$page = $this->newPage();
		$page->setEnableStateValidation(true);

		$state = base64_encode(serialize(['user' => 'bob']));

		$this->assertNull(TPageStateFormatter::unserialize($page, $state));
	}

	// -----------------------------------------------------------------------
	// EnableStateCompression
	// -----------------------------------------------------------------------

	public function testSerializeWithCompressionCompressesTheData(): void
	{
		$page = $this->newPage();
		$page->setEnableStateCompression(true);
		$data = array_fill(0, 100, 'repeated value');

		$state = TPageStateFormatter::serialize($page, $data);

		$this->assertEquals(serialize($data), gzuncompress(base64_decode($state)));
	}

	public function testUnserializeWithCompressionRestoresData(): void
	{
		$page = $this->newPage();
		$page->setEnableStateCompression(true);
		$data = ['compressed' => str_repeat('x', 500)];

		$state = TPageStateFormatter::serialize($page, $data);

		$this->assertEquals($data, TPageStateFormatter::unserialize($page, $state));
	}

	// -----------------------------------------------------------------------
	// StatePersister.Compression
	// -----------------------------------------------------------------------

	public function testPersisterCompressionDefaults(): void
	{
		$compression = $this->compressionOf(new TPage());

		$this->assertInstanceOf(TPageStateCompressionConfig::class, $compression);
		$this->assertTrue($compression->getEnabled(), 'page state has compressed by default since 3.1.6');
		$this->assertEquals('deflate', $compression->getMethod());
		$this->assertEquals(0, $compression->getThreshold(), 'every state compresses, whatever its length');
	}

	public function testEnableStateCompressionReadsAndWritesThePersister(): void
	{
		$page = new TPage();

		$this->assertTrue($page->getEnableStateCompression());

		$page->setEnableStateCompression(false);
		$this->assertFalse($this->compressionOf($page)->getEnabled());

		$this->compressionOf($page)->setEnabled(true);
		$this->assertTrue($page->getEnableStateCompression());
	}

	public function testCompressionIsReachedAsAPageSubProperty(): void
	{
		$page = $this->newPage();

		$page->setSubProperty('StatePersister.Compression.Method', 'GZIP');
		$page->setSubProperty('StatePersister.Compression.Level', '9');

		$this->assertEquals('gzip', $this->compressionOf($page)->getMethod());
		$this->assertEquals(9, $this->compressionOf($page)->getLevel());
	}

	public function testSetCompressionMethodRejectsAnUnknownCoding(): void
	{
		$page = $this->newPage();

		$this->expectException(TConfigurationException::class);
		$this->compressionOf($page)->setMethod('lzma');
	}

	public function testStateCompressionIsThePersistersOwnWhenItHoldsOne(): void
	{
		$page = new TPage();
		$persister = $page->getStatePersister();

		$this->assertInstanceOf(ICompressionConfigurable::class, $persister);
		$this->assertSame($persister->getCompression(), $page->getStateCompression());
	}

	/**
	 * @return array<string, array{class-string<IPageStatePersister>}> the built-in persisters
	 */
	public static function builtInPersisters(): array
	{
		return [
			'hidden field' => [TPageStatePersister::class],
			'session' => [TSessionPageStatePersister::class],
			'cache' => [TCachePageStatePersister::class],
		];
	}

	/**
	 * @dataProvider builtInPersisters
	 */
	public function testEachBuiltInPersisterHoldsItsOwnSettings(string $class): void
	{
		$page = new TPage();
		$page->setStatePersisterClass($class);
		$persister = $page->getStatePersister();

		$this->assertInstanceOf(ICompressionConfigurable::class, $persister);
		$this->assertInstanceOf(TPageStateCompressionConfig::class, $persister->getCompression());
		$this->assertSame($persister->getCompression(), $page->getStateCompression());
	}

	public function testStateCompressionIsKeptByThePageForAPersisterWithoutOne(): void
	{
		$page = new TPage();
		$page->setStatePersisterClass(TLegacyPageStatePersister::class);

		$this->assertNotInstanceOf(ICompressionConfigurable::class, $page->getStatePersister());
		$compression = $page->getStateCompression();
		$this->assertInstanceOf(TPageStateCompressionConfig::class, $compression);
		$this->assertSame($compression, $page->getStateCompression());

		$page->setEnableStateCompression(false);
		$this->assertFalse($compression->getEnabled());
	}

	public function testPersisterWrittenAgainstTheOldInterfaceStillCompresses(): void
	{
		$this->requireCodec('deflate');
		$page = $this->newPage();
		$page->setStatePersisterClass(TLegacyPageStatePersister::class);
		$page->setEnableStateCompression(true);
		$data = array_fill(0, 100, 'repeated value');

		$state = TPageStateFormatter::serialize($page, $data);

		$this->assertEquals(serialize($data), gzuncompress(base64_decode($state)), 'compressed as before 4.4.0');
		$this->assertEquals($data, TPageStateFormatter::unserialize($page, $state));
	}

	public function testStatePersisterClassChangeCarriesThePageSettingsToAPersisterThatHoldsThem(): void
	{
		$page = $this->newPage();
		$page->setStatePersisterClass(TLegacyPageStatePersister::class);
		$this->compressionOf($page)->setMethod('gzip');

		$page->setStatePersisterClass(TPageStatePersister::class);

		$this->assertEquals('gzip', $page->getStatePersister()->getCompression()->getMethod());
	}

	public function testStatePersisterClassChangeCarriesTheCompressionOver(): void
	{
		$page = $this->newPage();
		$this->compressionOf($page)->setMethod('gzip');

		$page->setStatePersisterClass(\Prado\Web\UI\TSessionPageStatePersister::class);

		$this->assertInstanceOf(\Prado\Web\UI\TSessionPageStatePersister::class, $page->getStatePersister());
		$this->assertEquals('gzip', $this->compressionOf($page)->getMethod());
	}

	public function testDefaultCompressionMethodProducesZlibData(): void
	{
		$this->requireCodec('deflate');
		$page = $this->newPage();
		$page->setEnableStateCompression(true);
		$data = array_fill(0, 100, 'repeated value');

		// 'deflate' is the zlib format, which is what gzcompress() wrote before the
		// formatter routed compression through TCompression.
		$state = TPageStateFormatter::serialize($page, $data);

		$this->assertEquals(serialize($data), gzuncompress(base64_decode($state)));
	}

	public function testSerializeWithGzipMethodProducesGzipData(): void
	{
		$this->requireCodec('gzip');
		$page = $this->newPage();
		$page->setEnableStateCompression(true);
		$this->compressionOf($page)->setMethod('gzip');
		$data = array_fill(0, 100, 'repeated value');

		$state = TPageStateFormatter::serialize($page, $data);

		$this->assertEquals(serialize($data), gzdecode(base64_decode($state)));
	}

	/**
	 * @dataProvider methodProvider
	 */
	public function testRoundTripUnderEachContentCoding(string $method): void
	{
		$this->requireCodec($method);
		$page = $this->newPage();
		$page->setEnableStateCompression(true);
		$this->compressionOf($page)->setMethod($method);
		$data = ['coding' => $method, 'body' => str_repeat('compress me ', 100)];

		$state = TPageStateFormatter::serialize($page, $data);

		$this->assertEquals($data, TPageStateFormatter::unserialize($page, $state));
	}

	public static function methodProvider(): array
	{
		return array_map(fn ($method) => [$method], TCompression::getMethods());
	}

	public function testUnserializeUnderAnotherCodingReturnsNull(): void
	{
		$this->requireCodec('deflate');
		$this->requireCodec('gzip');
		$page = $this->newPage();
		$page->setEnableStateCompression(true);
		$state = TPageStateFormatter::serialize($page, ['written' => 'as deflate']);

		$this->compressionOf($page)->setMethod('gzip');

		[$result, $errors] = $this->captureErrors(fn () => TPageStateFormatter::unserialize($page, $state));
		$this->assertNull($result, 'A state written under another coding is corrupted');
		$this->assertSame([], $errors);
	}

	public function testUnserializeCorruptCompressedStateReturnsNull(): void
	{
		$this->requireCodec('deflate');
		$page = $this->newPage();
		$page->setEnableStateCompression(true);

		[$result, $errors] = $this->captureErrors(
			fn () => TPageStateFormatter::unserialize($page, base64_encode('not compressed data'))
		);

		$this->assertNull($result);
		$this->assertSame([], $errors, 'A corrupt compressed state must not raise a PHP diagnostic');
	}

	// -----------------------------------------------------------------------
	// EnableStateEncryption
	// -----------------------------------------------------------------------

	public function testSerializeWithEncryptionHidesTheData(): void
	{
		$page = $this->newPage();
		$page->setEnableStateEncryption(true);
		$data = ['secret' => 'value'];

		$state = TPageStateFormatter::serialize($page, $data);

		$this->assertStringNotContainsString('secret', base64_decode($state));
	}

	public function testUnserializeWithEncryptionRestoresData(): void
	{
		$page = $this->newPage();
		$page->setEnableStateEncryption(true);
		$data = ['secret' => 'value', 'n' => 3];

		$state = TPageStateFormatter::serialize($page, $data);

		$this->assertEquals($data, TPageStateFormatter::unserialize($page, $state));
	}

	// -----------------------------------------------------------------------
	// EnableStateIGBinary
	// -----------------------------------------------------------------------

	public function testSerializeWithIGBinaryProducesIGBinaryData(): void
	{
		$this->requireIGBinary();
		$page = $this->newPage();
		$page->setEnableStateIGBinary(true);
		$data = ['igbinary' => true];

		$state = TPageStateFormatter::serialize($page, $data);

		$this->assertEquals(igbinary_serialize($data), base64_decode($state));
	}

	public function testUnserializeWithIGBinaryRestoresData(): void
	{
		$this->requireIGBinary();
		$page = $this->newPage();
		$page->setEnableStateIGBinary(true);
		$data = ['igbinary' => true, 'list' => [1, 2, 3]];

		$state = TPageStateFormatter::serialize($page, $data);

		$this->assertEquals($data, TPageStateFormatter::unserialize($page, $state));
	}

	public function testUnserializeWithIGBinaryAndValidationRestoresData(): void
	{
		$this->requireIGBinary();
		$page = $this->newPage();
		$page->setEnableStateIGBinary(true);
		$page->setEnableStateValidation(true);
		$data = ['igbinary' => true, 'signed' => true];

		$state = TPageStateFormatter::serialize($page, $data);

		$this->assertEquals($data, TPageStateFormatter::unserialize($page, $state));
	}

	public function testUnserializeWithIGBinaryAndValidationRejectsTamperedData(): void
	{
		$this->requireIGBinary();
		$page = $this->newPage();
		$page->setEnableStateIGBinary(true);
		$page->setEnableStateValidation(true);

		$raw = base64_decode(TPageStateFormatter::serialize($page, ['igbinary' => true]));
		$raw[0] = ($raw[0] === 'a') ? 'b' : 'a';

		$this->assertNull(TPageStateFormatter::unserialize($page, base64_encode($raw)));
	}

	// -----------------------------------------------------------------------
	// Combined settings
	// -----------------------------------------------------------------------

	public function testRoundTripWithDefaultPageSettings(): void
	{
		$page = new TPage();
		$data = ['default' => 'settings', 'items' => range(1, 20)];

		$state = TPageStateFormatter::serialize($page, $data);

		$this->assertEquals($data, TPageStateFormatter::unserialize($page, $state));
	}

	public function testRoundTripWithAllFeaturesEnabled(): void
	{
		$page = new TPage();
		$page->setEnableStateValidation(true);
		$page->setEnableStateEncryption(true);
		$page->setEnableStateCompression(true);
		$page->setEnableStateIGBinary(true);
		$data = ['all' => 'features', 'items' => range(1, 20)];

		$state = TPageStateFormatter::serialize($page, $data);

		$this->assertEquals($data, TPageStateFormatter::unserialize($page, $state));
	}

	public function testUnserializeWithAllFeaturesEnabledRejectsTamperedData(): void
	{
		$page = new TPage();
		$page->setEnableStateValidation(true);
		$page->setEnableStateEncryption(true);
		$page->setEnableStateCompression(true);
		$page->setEnableStateIGBinary(true);

		$raw = base64_decode(TPageStateFormatter::serialize($page, ['all' => 'features']));
		$raw[strlen($raw) - 1] = ($raw[strlen($raw) - 1] === 'A') ? 'B' : 'A';

		$this->assertNull(TPageStateFormatter::unserialize($page, base64_encode($raw)));
	}
}
