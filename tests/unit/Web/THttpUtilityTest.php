<?php

/**
 * THttpUtilityTest
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Web\THttpUtility;

/**
 * Tests for {@see THttpUtility}.
 *
 * Covers all four static helpers: {@see THttpUtility::htmlEncode()},
 * {@see THttpUtility::htmlDecode()}, {@see THttpUtility::htmlStrip()},
 * {@see THttpUtility::buildHtmlAttributes()}, {@see THttpUtility::isLocalUrl()},
 * and {@see THttpUtility::normalizeIntegrityUrl()}.
 */
class THttpUtilityTest extends PHPUnit\Framework\TestCase
{
	// =========================================================================
	// htmlEncode
	// =========================================================================

	public function testHtmlEncodeTranslatesLessThan(): void
	{
		self::assertSame('&lt;', THttpUtility::htmlEncode('<'));
	}

	public function testHtmlEncodeTranslatesGreaterThan(): void
	{
		self::assertSame('&gt;', THttpUtility::htmlEncode('>'));
	}

	public function testHtmlEncodeTranslatesDoubleQuote(): void
	{
		self::assertSame('&quot;', THttpUtility::htmlEncode('"'));
	}

	public function testHtmlEncodeTranslatesAllThreeInOneString(): void
	{
		self::assertSame(
			'&lt;tag key=&quot;value&quot;&gt;',
			THttpUtility::htmlEncode('<tag key="value">')
		);
	}

	public function testHtmlEncodeLeavesAmpersandUntouched(): void
	{
		// Intentional design: & is NOT encoded (unlike htmlspecialchars).
		self::assertSame('&amp;', THttpUtility::htmlEncode('&amp;'));
		self::assertSame('&', THttpUtility::htmlEncode('&'));
	}

	public function testHtmlEncodeLeavesApostropheUntouched(): void
	{
		self::assertSame("it's", THttpUtility::htmlEncode("it's"));
	}

	public function testHtmlEncodeLeavesAlreadyEncodedEntitiesUntouched(): void
	{
		// &lt; in input: the & is left alone, so &lt; stays &lt; (no double-encoding).
		self::assertSame('&lt;', THttpUtility::htmlEncode('&lt;'));
		self::assertSame('&gt;', THttpUtility::htmlEncode('&gt;'));
		self::assertSame('&quot;', THttpUtility::htmlEncode('&quot;'));
	}

	public function testHtmlEncodeEmptyStringReturnsEmptyString(): void
	{
		self::assertSame('', THttpUtility::htmlEncode(''));
	}

	public function testHtmlEncodePlainTextPassesThrough(): void
	{
		self::assertSame('hello world', THttpUtility::htmlEncode('hello world'));
	}

	public function testHtmlEncodeMultipleOccurrences(): void
	{
		self::assertSame('&lt;a&gt;&lt;b&gt;', THttpUtility::htmlEncode('<a><b>'));
	}

	// =========================================================================
	// htmlDecode
	// =========================================================================

	public function testHtmlDecodeTranslatesLtEntity(): void
	{
		self::assertSame('<', THttpUtility::htmlDecode('&lt;'));
	}

	public function testHtmlDecodeTranslatesGtEntity(): void
	{
		self::assertSame('>', THttpUtility::htmlDecode('&gt;'));
	}

	public function testHtmlDecodeTranslatesQuotEntity(): void
	{
		self::assertSame('"', THttpUtility::htmlDecode('&quot;'));
	}

	public function testHtmlDecodeTranslatesAllThreeInOneString(): void
	{
		self::assertSame(
			'<tag key="value">',
			THttpUtility::htmlDecode('&lt;tag key=&quot;value&quot;&gt;')
		);
	}

	public function testHtmlDecodeLeavesAmpEntityUntouched(): void
	{
		// &amp; is not in the decode table.
		self::assertSame('&amp;', THttpUtility::htmlDecode('&amp;'));
	}

	public function testHtmlDecodeEmptyStringReturnsEmptyString(): void
	{
		self::assertSame('', THttpUtility::htmlDecode(''));
	}

	public function testHtmlDecodePlainTextPassesThrough(): void
	{
		self::assertSame('hello world', THttpUtility::htmlDecode('hello world'));
	}

	public function testHtmlDecodeIsInverseOfHtmlEncode(): void
	{
		$original = '<input type="text">';
		self::assertSame($original, THttpUtility::htmlDecode(THttpUtility::htmlEncode($original)));
	}

	public function testHtmlEncodeDecodeRoundTrip(): void
	{
		$originals = [
			'',
			'plain text',
			'<b>bold</b>',
			'"quoted"',
			'<a href="url">link</a>',
			'<>&"',
		];
		foreach ($originals as $s) {
			self::assertSame(
				$s,
				THttpUtility::htmlDecode(THttpUtility::htmlEncode($s)),
				"Round-trip failed for: $s"
			);
		}
	}

	// =========================================================================
	// htmlStrip
	// =========================================================================

	public function testHtmlStripRemovesLtEntity(): void
	{
		self::assertSame('', THttpUtility::htmlStrip('&lt;'));
	}

	public function testHtmlStripRemovesGtEntity(): void
	{
		self::assertSame('', THttpUtility::htmlStrip('&gt;'));
	}

	public function testHtmlStripRemovesQuotEntity(): void
	{
		self::assertSame('', THttpUtility::htmlStrip('&quot;'));
	}

	public function testHtmlStripRemovesAllThreeEntities(): void
	{
		self::assertSame(
			'tag key=value',
			THttpUtility::htmlStrip('&lt;tag key=&quot;value&quot;&gt;')
		);
	}

	public function testHtmlStripLeavesLiteralLessThanUntouched(): void
	{
		// Strips entity forms only, NOT the literal characters.
		self::assertSame('<', THttpUtility::htmlStrip('<'));
	}

	public function testHtmlStripLeavesLiteralGreaterThanUntouched(): void
	{
		self::assertSame('>', THttpUtility::htmlStrip('>'));
	}

	public function testHtmlStripLeavesLiteralDoubleQuoteUntouched(): void
	{
		self::assertSame('"', THttpUtility::htmlStrip('"'));
	}

	public function testHtmlStripLeavesAmpEntityUntouched(): void
	{
		// &amp; is not in the strip table.
		self::assertSame('&amp;', THttpUtility::htmlStrip('&amp;'));
	}

	public function testHtmlStripLeavesAmpersandUntouched(): void
	{
		self::assertSame('&', THttpUtility::htmlStrip('&'));
	}

	public function testHtmlStripEmptyStringReturnsEmptyString(): void
	{
		self::assertSame('', THttpUtility::htmlStrip(''));
	}

	public function testHtmlStripPlainTextPassesThrough(): void
	{
		self::assertSame('hello world', THttpUtility::htmlStrip('hello world'));
	}

	public function testHtmlStripComposedWithHtmlEncode(): void
	{
		// Encoding then stripping removes the entity forms of <, >, and ".
		$encoded = THttpUtility::htmlEncode('<b>bold</b>');
		// &lt;b&gt;bold&lt;/b&gt; → strip &lt;/&gt; → 'bbold/b'
		self::assertSame('bbold/b', THttpUtility::htmlStrip($encoded));
	}

	public function testHtmlStripMultipleOccurrences(): void
	{
		self::assertSame('ab', THttpUtility::htmlStrip('&lt;a&gt;&lt;b&gt;'));
	}

	// =========================================================================
	// buildHtmlAttributes
	// =========================================================================

	public function testBuildHtmlAttributesEmptyArrayReturnsEmptyString(): void
	{
		self::assertSame('', THttpUtility::buildHtmlAttributes([]));
	}

	public function testBuildHtmlAttributesSingleStringAttribute(): void
	{
		// No leading space — the caller inserts the separator.
		self::assertSame('type="text"', THttpUtility::buildHtmlAttributes(['type' => 'text']));
	}

	public function testBuildHtmlAttributesMultipleAttributesSpaceSeparated(): void
	{
		self::assertSame(
			'type="text" name="field"',
			THttpUtility::buildHtmlAttributes(['type' => 'text', 'name' => 'field'])
		);
	}

	public function testBuildHtmlAttributesBooleanTrueRendersBareAttributeName(): void
	{
		self::assertSame('disabled', THttpUtility::buildHtmlAttributes(['disabled' => true]));
	}

	public function testBuildHtmlAttributesBooleanFalseOmitsAttribute(): void
	{
		self::assertSame('', THttpUtility::buildHtmlAttributes(['disabled' => false]));
	}

	public function testBuildHtmlAttributesNullOmitsAttribute(): void
	{
		self::assertSame('', THttpUtility::buildHtmlAttributes(['id' => null]));
	}

	public function testBuildHtmlAttributesAllNullFalseProducesEmptyString(): void
	{
		self::assertSame(
			'',
			THttpUtility::buildHtmlAttributes(['a' => null, 'b' => false, 'c' => null])
		);
	}

	public function testBuildHtmlAttributesMixOfNullFalseAndValue(): void
	{
		self::assertSame(
			'class="foo"',
			THttpUtility::buildHtmlAttributes(['id' => null, 'hidden' => false, 'class' => 'foo'])
		);
	}

	public function testBuildHtmlAttributesIntegerValueCastToString(): void
	{
		self::assertSame('tabindex="3"', THttpUtility::buildHtmlAttributes(['tabindex' => 3]));
	}

	public function testBuildHtmlAttributesFloatValueCastToString(): void
	{
		self::assertSame('step="0.5"', THttpUtility::buildHtmlAttributes(['step' => 0.5]));
	}

	public function testBuildHtmlAttributesSpecialCharsAreHtmlEncoded(): void
	{
		self::assertSame(
			'title="&quot;hello&quot;"',
			THttpUtility::buildHtmlAttributes(['title' => '"hello"'])
		);
	}

	public function testBuildHtmlAttributesApostropheIsEncoded(): void
	{
		self::assertSame(
			'title="it&#039;s"',
			THttpUtility::buildHtmlAttributes(['title' => "it's"])
		);
	}

	public function testBuildHtmlAttributesAmpersandIsEncoded(): void
	{
		self::assertSame(
			'href="page.php?a=1&amp;b=2"',
			THttpUtility::buildHtmlAttributes(['href' => 'page.php?a=1&b=2'])
		);
	}

	public function testBuildHtmlAttributesMixedSpecialChars(): void
	{
		self::assertSame(
			'data-val="&lt;b&gt;&amp;quot;&amp;"',
			THttpUtility::buildHtmlAttributes(['data-val' => '<b>&quot;&'])
		);
	}

	public function testBuildHtmlAttributesBangPrefixWritesValueRaw(): void
	{
		// ! prefix: value is written verbatim — no HTML encoding.
		self::assertSame(
			'data-html="&amp;"',
			THttpUtility::buildHtmlAttributes(['!data-html' => '&amp;'])
		);
	}

	public function testBuildHtmlAttributesWithoutBangPrefixDoubleEncodesAmpEntity(): void
	{
		// Without !, &amp; is encoded a second time to &amp;amp;.
		self::assertSame(
			'data-html="&amp;amp;"',
			THttpUtility::buildHtmlAttributes(['data-html' => '&amp;'])
		);
	}

	public function testBuildHtmlAttributesBangPrefixWithAngleBracketsWrittenAsIs(): void
	{
		self::assertSame(
			'data-raw="<b>"',
			THttpUtility::buildHtmlAttributes(['!data-raw' => '<b>'])
		);
	}

	public function testBuildHtmlAttributesBangPrefixWithBooleanTrueRendersBareNameWithoutBang(): void
	{
		self::assertSame('readonly', THttpUtility::buildHtmlAttributes(['!readonly' => true]));
	}

	public function testBuildHtmlAttributesBangPrefixWithNullOmitsAttribute(): void
	{
		self::assertSame('', THttpUtility::buildHtmlAttributes(['!omit' => null]));
	}

	public function testBuildHtmlAttributesBangPrefixWithFalseOmitsAttribute(): void
	{
		self::assertSame('', THttpUtility::buildHtmlAttributes(['!omit' => false]));
	}

	public function testBuildHtmlAttributesMixedBangAndNormalAttributes(): void
	{
		self::assertSame(
			'src="page.php?a=1&amp;b=2" nonce="&amp;raw&amp;"',
			THttpUtility::buildHtmlAttributes([
				'src'    => 'page.php?a=1&b=2',
				'!nonce' => '&amp;raw&amp;',
			])
		);
	}

	public function testBuildHtmlAttributesCallerSideInsertionPatternNonEmpty(): void
	{
		// Documented usage: non-empty result gets a single leading space from caller.
		$attrs = THttpUtility::buildHtmlAttributes(['type' => 'text', 'disabled' => true]);
		self::assertSame(
			'<input type="text" disabled>',
			'<input' . ($attrs !== '' ? ' ' . $attrs : '') . '>'
		);
	}

	public function testBuildHtmlAttributesCallerSideInsertionPatternEmpty(): void
	{
		// When all attributes are omitted the caller's pattern adds no space.
		$attrs = THttpUtility::buildHtmlAttributes([]);
		self::assertSame('<input>', '<input' . ($attrs !== '' ? ' ' . $attrs : '') . '>');
	}

	// =========================================================================
	// isLocalUrl
	// =========================================================================

	public function testIsLocalUrlRelativePathIsAlwaysLocal(): void
	{
		self::assertTrue(THttpUtility::isLocalUrl('assets/app.js', false, 'mysite.com'));
		self::assertTrue(THttpUtility::isLocalUrl('assets/app.js', true, 'mysite.com'));
	}

	public function testIsLocalUrlRootRelativePathIsAlwaysLocal(): void
	{
		self::assertTrue(THttpUtility::isLocalUrl('/css/main.css', false, 'mysite.com'));
		self::assertTrue(THttpUtility::isLocalUrl('/css/main.css', true, 'mysite.com'));
	}

	public function testIsLocalUrlEmptyStringIsLocal(): void
	{
		self::assertTrue(THttpUtility::isLocalUrl('', false, 'mysite.com'));
	}

	public function testIsLocalUrlProtocolRelativeUrlTreatedAsRelative(): void
	{
		// Protocol-relative URLs (//host/path) contain no "://" so isLocalUrl treats
		// them as relative (local). Callers that need strict host matching should
		// expand //host/path to https://host/path first.
		self::assertTrue(THttpUtility::isLocalUrl('//evil.com/attack.js', false, 'mysite.com'));
		self::assertTrue(THttpUtility::isLocalUrl('//evil.com/attack.js', true, 'mysite.com'));
	}

	public function testIsLocalUrlExactHostMatchIsLocal(): void
	{
		self::assertTrue(THttpUtility::isLocalUrl('https://mysite.com/app.js', false, 'mysite.com'));
		self::assertTrue(THttpUtility::isLocalUrl('https://mysite.com/app.js', true, 'mysite.com'));
		self::assertTrue(THttpUtility::isLocalUrl('http://mysite.com/app.js', false, 'mysite.com'));
	}

	public function testIsLocalUrlHostComparisonIsCaseInsensitive(): void
	{
		self::assertTrue(THttpUtility::isLocalUrl('https://MYSITE.COM/app.js', false, 'mysite.com'));
		self::assertTrue(THttpUtility::isLocalUrl('https://MySite.Com/app.js', false, 'mysite.com'));
		self::assertTrue(THttpUtility::isLocalUrl('https://mysite.com/app.js', false, 'MySite.COM'));
	}

	public function testIsLocalUrlSubdomainIsRemoteByDefault(): void
	{
		self::assertFalse(THttpUtility::isLocalUrl('https://assets.mysite.com/app.js', false, 'mysite.com'));
		self::assertFalse(THttpUtility::isLocalUrl('https://app.mysite.com/app.js', false, 'mysite.com'));
	}

	public function testIsLocalUrlSubdomainIsLocalWhenMatchSubdomainsTrue(): void
	{
		self::assertTrue(THttpUtility::isLocalUrl('https://assets.mysite.com/app.js', true, 'mysite.com'));
		self::assertTrue(THttpUtility::isLocalUrl('https://app.mysite.com/app.js', true, 'mysite.com'));
	}

	public function testIsLocalUrlDeeperSubdomainIsLocalWhenMatchSubdomainsTrue(): void
	{
		self::assertTrue(THttpUtility::isLocalUrl('https://a.b.mysite.com/app.js', true, 'mysite.com'));
	}

	public function testIsLocalUrlDifferentHostIsRemote(): void
	{
		self::assertFalse(THttpUtility::isLocalUrl('https://othersite.com/app.js', false, 'mysite.com'));
		self::assertFalse(THttpUtility::isLocalUrl('https://othersite.com/app.js', true, 'mysite.com'));
	}

	public function testIsLocalUrlSuffixAttackIsRejected(): void
	{
		// "evil-mysite.com" must NOT match "mysite.com" — suffix alone is not enough;
		// the host must end with ".<serverName>".
		self::assertFalse(THttpUtility::isLocalUrl('https://evil-mysite.com/x.js', false, 'mysite.com'));
		self::assertFalse(THttpUtility::isLocalUrl('https://evil-mysite.com/x.js', true, 'mysite.com'));
		// "notmysite.com" likewise must not match "mysite.com".
		self::assertFalse(THttpUtility::isLocalUrl('https://notmysite.com/x.js', true, 'mysite.com'));
	}

	public function testIsLocalUrlNoServerNameAbsoluteUrlIsRemote(): void
	{
		self::assertFalse(THttpUtility::isLocalUrl('https://mysite.com/app.js', false, null));
		self::assertFalse(THttpUtility::isLocalUrl('https://anything.com/app.js', true, null));
	}

	public function testIsLocalUrlNoServerNameRelativeUrlIsStillLocal(): void
	{
		// Relative URLs bypass host comparison and are always local.
		self::assertTrue(THttpUtility::isLocalUrl('assets/app.js', false, null));
	}

	public function testIsLocalUrlUrlWithPortExtractsHostCorrectly(): void
	{
		// parse_url strips the port when PHP_URL_HOST is requested.
		self::assertTrue(THttpUtility::isLocalUrl('https://mysite.com:8080/app.js', false, 'mysite.com'));
		self::assertTrue(THttpUtility::isLocalUrl('https://assets.mysite.com:8080/app.js', true, 'mysite.com'));
		self::assertFalse(THttpUtility::isLocalUrl('https://assets.mysite.com:8080/app.js', false, 'mysite.com'));
	}

	public function testIsLocalUrlServerNameWithMixedCaseComparedCaseInsensitively(): void
	{
		self::assertTrue(THttpUtility::isLocalUrl('https://mysite.com/app.js', false, 'MySite.COM'));
		self::assertTrue(THttpUtility::isLocalUrl('https://sub.mysite.com/app.js', true, 'MySite.COM'));
	}

	// =========================================================================
	// normalizeIntegrityUrl
	// =========================================================================

	public function testNormalizeIntegrityUrlRelativePathPassesThrough(): void
	{
		self::assertSame('assets/app.js', THttpUtility::normalizeIntegrityUrl('assets/app.js'));
	}

	public function testNormalizeIntegrityUrlRootRelativePathPassesThrough(): void
	{
		self::assertSame('/js/app.js', THttpUtility::normalizeIntegrityUrl('/js/app.js'));
	}

	public function testNormalizeIntegrityUrlEmptyStringPassesThrough(): void
	{
		self::assertSame('', THttpUtility::normalizeIntegrityUrl(''));
	}

	public function testNormalizeIntegrityUrlProtocolRelativeExpandsToHttps(): void
	{
		self::assertSame(
			'https://cdn.example.com/script.js',
			THttpUtility::normalizeIntegrityUrl('//cdn.example.com/script.js')
		);
	}

	public function testNormalizeIntegrityUrlSchemeIsLowercased(): void
	{
		self::assertSame(
			'https://cdn.example.com/script.js',
			THttpUtility::normalizeIntegrityUrl('HTTPS://cdn.example.com/script.js')
		);
	}

	public function testNormalizeIntegrityUrlHostIsLowercased(): void
	{
		self::assertSame(
			'https://cdn.example.com/script.js',
			THttpUtility::normalizeIntegrityUrl('https://CDN.EXAMPLE.COM/script.js')
		);
	}

	public function testNormalizeIntegrityUrlSchemeAndHostBothLowercased(): void
	{
		self::assertSame(
			'https://cdn.example.com/script.js',
			THttpUtility::normalizeIntegrityUrl('HTTPS://CDN.EXAMPLE.COM/script.js')
		);
		self::assertSame(
			'https://cdn.example.com/script.js',
			THttpUtility::normalizeIntegrityUrl('Https://Cdn.Example.Com/script.js')
		);
	}

	public function testNormalizeIntegrityUrlStripsDefaultHttpsPort(): void
	{
		self::assertSame(
			'https://cdn.example.com/script.js',
			THttpUtility::normalizeIntegrityUrl('https://cdn.example.com:443/script.js')
		);
	}

	public function testNormalizeIntegrityUrlStripsDefaultHttpPort(): void
	{
		self::assertSame(
			'http://cdn.example.com/script.js',
			THttpUtility::normalizeIntegrityUrl('http://cdn.example.com:80/script.js')
		);
	}

	public function testNormalizeIntegrityUrlPreservesNonDefaultHttpsPort(): void
	{
		self::assertSame(
			'https://cdn.example.com:8443/script.js',
			THttpUtility::normalizeIntegrityUrl('https://cdn.example.com:8443/script.js')
		);
	}

	public function testNormalizeIntegrityUrlPreservesNonDefaultHttpPort(): void
	{
		self::assertSame(
			'http://cdn.example.com:8080/script.js',
			THttpUtility::normalizeIntegrityUrl('http://cdn.example.com:8080/script.js')
		);
	}

	public function testNormalizeIntegrityUrlStripsFragment(): void
	{
		self::assertSame(
			'https://cdn.example.com/script.js',
			THttpUtility::normalizeIntegrityUrl('https://cdn.example.com/script.js#anchor')
		);
	}

	public function testNormalizeIntegrityUrlPreservesQueryString(): void
	{
		self::assertSame(
			'https://cdn.example.com/script.js?v=3.7.1',
			THttpUtility::normalizeIntegrityUrl('https://cdn.example.com/script.js?v=3.7.1')
		);
	}

	public function testNormalizeIntegrityUrlPreservesQueryAndStripsFragment(): void
	{
		self::assertSame(
			'https://cdn.example.com/script.js?v=3.7.1',
			THttpUtility::normalizeIntegrityUrl('https://cdn.example.com/script.js?v=3.7.1#fragment')
		);
	}

	public function testNormalizeIntegrityUrlAppliesAllTransformationsTogether(): void
	{
		self::assertSame(
			'https://cdn.example.com/script.js?v=1',
			THttpUtility::normalizeIntegrityUrl('HTTPS://CDN.EXAMPLE.COM:443/script.js?v=1#section')
		);
	}

	public function testNormalizeIntegrityUrlHttpSchemePreservedNotPromotedToHttps(): void
	{
		self::assertSame(
			'http://cdn.example.com/script.js',
			THttpUtility::normalizeIntegrityUrl('http://cdn.example.com/script.js')
		);
	}

	public function testNormalizeIntegrityUrlIsIdempotent(): void
	{
		$url = 'https://cdn.example.com/script.js?v=3.7.1';
		self::assertSame($url, THttpUtility::normalizeIntegrityUrl($url));

		$url2 = 'http://cdn.example.com:8080/script.js';
		self::assertSame($url2, THttpUtility::normalizeIntegrityUrl($url2));
	}

	public function testNormalizeIntegrityUrlPathDefaultsToSlashWhenAbsent(): void
	{
		// parse_url returns no 'path' key for bare-authority URLs; the method falls back to '/'.
		self::assertSame(
			'https://cdn.example.com/',
			THttpUtility::normalizeIntegrityUrl('https://cdn.example.com')
		);
	}
}
