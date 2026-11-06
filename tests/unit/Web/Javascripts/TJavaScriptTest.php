<?php

namespace Prado\Test\Unit\Web\Javascripts;

use Prado\Prado;
use Prado\Web\Javascripts\TJavaScript;
use Prado\Test\Unit\PradoUnit;

class TJavaScriptTest extends \PHPUnit\Framework\TestCase
{
	// -----------------------------------------------------------------------
	// jsonEncode — globalization charset handling
	// -----------------------------------------------------------------------

	/**
	 * Temporarily swaps the application's globalization module for the
	 * duration of a callback. Passing `null` detaches it — `setGlobalization`
	 * itself only accepts a TGlobalization, so the swap goes through the
	 * private `_globalization` property via {@see PradoUnit::setProp()}.
	 * @param ?string $charset
	 * @param callable $fn
	 */
	private function withGlobalizationCharset(?string $charset, callable $fn): mixed
	{
		$app = \Prado::getApplication();
		$original = PradoUnit::getProp($app, '_globalization');
		try {
			if ($charset === null) {
				PradoUnit::setProp($app, '_globalization', null);
			} else {
				$g = new \Prado\I18N\TGlobalization();
				$g->setCharset($charset);
				PradoUnit::setProp($app, '_globalization', $g);
			}
			return $fn();
		} finally {
			PradoUnit::setProp($app, '_globalization', $original);
		}
	}

	public function testJsonEncodeSkipsTranscodingWhenCharsetIsNotIconvName(): void
	{
		// Regression for the Windows CI failure: 'fr' is a locale code, not an
		// iconv encoding. jsonEncode used to call iconv('fr', ...) → warning →
		// TPhpErrorException. With the guard, the charset is recognised as not
		// being an iconv encoding and the value passes through unchanged.
		$this->withGlobalizationCharset('fr', function () {
			$json = TJavaScript::jsonEncode(['status' => 404, 'title' => 'Not Found']);
			$this->assertJson($json);
			$this->assertSame(
				['status' => 404, 'title' => 'Not Found'],
				json_decode($json, true)
			);
		});
	}

	public function testJsonEncodeSkipsTranscodingForEmptyCharset(): void
	{
		$this->withGlobalizationCharset('', function () {
			$json = TJavaScript::jsonEncode(['ok' => true]);
			$this->assertSame('{"ok":true}', $json);
		});
	}

	public function testJsonEncodeWithUtf8CharsetSkipsConversion(): void
	{
		$this->withGlobalizationCharset('UTF-8', function () {
			// json_encode escapes non-ASCII by default; decode-and-compare to
			// keep the test independent of the escaping flag.
			$json = TJavaScript::jsonEncode(['n' => 'naïve']);
			$this->assertSame(['n' => 'naïve'], json_decode($json, true));
		});
	}

	public function testJsonEncodeTranscodesStringsForValidIconvCharset(): void
	{
		// 'Café' encoded in ISO-8859-1 (Latin-1).
		$latin1 = "Caf" . chr(0xE9);
		$this->withGlobalizationCharset('ISO-8859-1', function () use ($latin1) {
			$json = TJavaScript::jsonEncode(['name' => $latin1]);
			$this->assertSame(['name' => 'Café'], json_decode($json, true));
		});
	}

	public function testJsonEncodeTranscodesNestedArrayStrings(): void
	{
		$latin1 = chr(0xE9); // é in Latin-1
		$this->withGlobalizationCharset('ISO-8859-1', function () use ($latin1) {
			$json = TJavaScript::jsonEncode(['a' => ['b' => $latin1]]);
			$this->assertSame(['a' => ['b' => 'é']], json_decode($json, true));
		});
	}

	public function testJsonEncodeTranscodesStdClassProperties(): void
	{
		// New: convertToUtf8 used to skip objects entirely; it now recurses
		// through stdClass-shaped values too.
		$latin1 = chr(0xE9);
		$this->withGlobalizationCharset('ISO-8859-1', function () use ($latin1) {
			$obj = new \stdClass();
			$obj->label = $latin1;
			$json = TJavaScript::jsonEncode($obj);
			$decoded = json_decode($json, true);
			$this->assertSame('é', $decoded['label']);
		});
	}

	public function testJsonEncodeNonStdClassObjectsPassThrough(): void
	{
		// Objects of arbitrary classes are not transcoded — json_encode's own
		// rules apply. Verify the call still succeeds when globalization is
		// in a non-UTF-8 (but valid) mode.
		$this->withGlobalizationCharset('ISO-8859-1', function () {
			$obj = new class () {
				public string $field = 'plain';
			};
			$json = TJavaScript::jsonEncode($obj);
			$this->assertJson($json);
			$decoded = json_decode($json, true);
			$this->assertSame('plain', $decoded['field']);
		});
	}

	public function testJsonEncodeWithoutGlobalizationStaysQuiet(): void
	{
		// When no globalization module is attached, the charset path is not
		// consulted at all — the result is identical to a vanilla json_encode.
		$this->withGlobalizationCharset(null, function () {
			$this->assertSame('{"x":1}', TJavaScript::jsonEncode(['x' => 1]));
		});
	}

	public function testJsonEncodeWithoutApplication(): void
	{
		$app = PradoUnit::getStaticProp(Prado::class, '_application');
		try {
			PradoUnit::setStaticProp(Prado::class, '_application', null);
			$this->assertSame('{"x":1}', TJavaScript::jsonEncode(['x' => 1]));
		} finally {
			PradoUnit::setStaticProp(Prado::class, '_application', $app);
		}
	}
}
