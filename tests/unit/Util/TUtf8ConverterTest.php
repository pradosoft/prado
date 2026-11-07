<?php

namespace Prado\Test\Unit\Util;

use Prado\Util\TUtf8Converter;

class TUtf8ConverterTest extends \PHPUnit\Framework\TestCase
{
	public function testParseEncodingLanguageSplitsTheLanguage()
	{
		$encoding = 'ASCII.de';
		$lang = null;
		TUtf8Converter::parseEncodingLanguage($encoding, $lang);
		$this->assertSame('ASCII', $encoding);
		$this->assertSame('de', $lang);

		$encoding = 'UTF-8';
		$lang = null;
		TUtf8Converter::parseEncodingLanguage($encoding, $lang);
		$this->assertSame('UTF-8', $encoding);
		$this->assertNull($lang);
	}
}
