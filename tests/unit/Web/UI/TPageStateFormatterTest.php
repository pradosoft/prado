<?php

namespace Prado\Test\Unit\Web\UI;

use Prado\Web\UI\TPage;
use Prado\Web\UI\TPageStateFormatter;

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
	 * Run a callable with an error handler that records every PHP diagnostic.
	 * @param callable $callable the code under test
	 * @return array [return value of the callable, list of recorded messages]
	 */
	private function captureErrors(callable $callable): array
	{
		$errors = [];
		set_error_handler(function ($severity, $message) use (&$errors) {
			$errors[] = $message;
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
