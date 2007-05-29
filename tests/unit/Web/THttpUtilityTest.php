<?php
require_once dirname(__FILE__).'/../phpunit.php';

Prado::using('System.Web.THttpUtility');

/**
 * @package System.Web
 */
class THttpUtilityTest extends PHPUnit_Framework_TestCase {

	public function testHtmlEncode() {
		$html = THttpUtility::htmlEncode('<tag key="value">');
		self::assertEquals('&lt;tag key=&quot;value&quot;&gt;', $html);
		$html = THttpUtility::htmlEncode('&lt;');
		self::assertEquals('&lt;', $html);
	}

	public function testHtmlDecode() {
    	$html = THttpUtility::htmlDecode('&lt;tag key=&quot;value&quot;&gt;');
		self::assertEquals('<tag key="value">', $html);
	}
}
?>