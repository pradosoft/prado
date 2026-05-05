<?php

use Prado\Web\THttpUtility;

class THttpUtilityTest extends PHPUnit\Framework\TestCase
{
	public function testHtmlEncode()
	{
		$html = THttpUtility::htmlEncode('<tag key="value">');
		self::assertEquals('&lt;tag key=&quot;value&quot;&gt;', $html);
		$html = THttpUtility::htmlEncode('&lt;');
		self::assertEquals('&lt;', $html);
	}

	public function testHtmlDecode()
	{
		$html = THttpUtility::htmlDecode('&lt;tag key=&quot;value&quot;&gt;');
		self::assertEquals('<tag key="value">', $html);
	}

	public function testBuildHtmlAttributes()
	{
		// empty array produces empty string
		self::assertEquals('', THttpUtility::buildHtmlAttributes([]));

		// single attribute
		self::assertEquals(' type="text"', THttpUtility::buildHtmlAttributes(['type' => 'text']));

		// multiple attributes are space-prefixed and concatenated
		self::assertEquals(
			' type="text" name="field"',
			THttpUtility::buildHtmlAttributes(['type' => 'text', 'name' => 'field'])
		);

		// boolean true renders as bare attribute name
		self::assertEquals(' disabled', THttpUtility::buildHtmlAttributes(['disabled' => true]));

		// boolean false omits the attribute
		self::assertEquals('', THttpUtility::buildHtmlAttributes(['disabled' => false]));

		// null omits the attribute
		self::assertEquals('', THttpUtility::buildHtmlAttributes(['id' => null]));

		// mix of null/false/value: only non-null/non-false rendered
		self::assertEquals(
			' class="foo"',
			THttpUtility::buildHtmlAttributes(['id' => null, 'hidden' => false, 'class' => 'foo'])
		);

		// integer and float values are cast to string
		self::assertEquals(' tabindex="3"', THttpUtility::buildHtmlAttributes(['tabindex' => 3]));
		self::assertEquals(' step="0.5"', THttpUtility::buildHtmlAttributes(['step' => 0.5]));

		// HTML special characters are encoded in normal values
		self::assertEquals(
			' data-val="&lt;b&gt;&amp;quot;&amp;"',
			THttpUtility::buildHtmlAttributes(['data-val' => '<b>&quot;&'])
		);
		self::assertEquals(
			' title="&quot;hello&quot;"',
			THttpUtility::buildHtmlAttributes(['title' => '"hello"'])
		);
		self::assertEquals(
			' title="it&#039;s"',
			THttpUtility::buildHtmlAttributes(['title' => "it's"])
		);

		// ! prefix passes value through without HTML encoding (raw / pre-encoded)
		self::assertEquals(
			' data-html="&amp;"',
			THttpUtility::buildHtmlAttributes(['!data-html' => '&amp;'])
		);

		// without ! prefix the same value is double-encoded
		self::assertEquals(
			' data-html="&amp;amp;"',
			THttpUtility::buildHtmlAttributes(['data-html' => '&amp;'])
		);

		// ! prefix with a value containing quotes is written as-is
		self::assertEquals(
			' data-raw="<b>"',
			THttpUtility::buildHtmlAttributes(['!data-raw' => '<b>'])
		);

		// ! prefix with boolean true still renders as bare attribute name (prefix stripped)
		self::assertEquals(' readonly', THttpUtility::buildHtmlAttributes(['!readonly' => true]));

		// ! prefix with null still omits the attribute
		self::assertEquals('', THttpUtility::buildHtmlAttributes(['!omit' => null]));

		// ! prefix with false still omits the attribute
		self::assertEquals('', THttpUtility::buildHtmlAttributes(['!omit' => false]));

		// mix of ! prefixed and normal attributes in one call
		self::assertEquals(
			' src="page.php?a=1&amp;b=2" nonce="&amp;raw&amp;"',
			THttpUtility::buildHtmlAttributes([
				'src'    => 'page.php?a=1&b=2',
				'!nonce' => '&amp;raw&amp;',
			])
		);
	}
}
