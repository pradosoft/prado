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
}
