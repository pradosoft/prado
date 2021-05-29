<?php


use Prado\Web\UI\WebControls\TLabel;

use Prado\IO\TTextWriter;
use Prado\Web\UI\WebControls\TPageLoadTime;

class TPageLoadTimeTest extends PHPUnit\Framework\TestCase
{
	public function testSecondSuffix()
	{
		$pageloadtime = new TPageLoadTime();
		$pageloadtime->setSecondSuffix('t');
		self::assertEquals('t', $pageloadtime->getSecondSuffix());
	}
	
	public function testRenderContents()
	{
		$writer = new TTextWriter;
		$pageloadtime = new TPageLoadTime();
		
		$pageloadtime->setSecondSuffix('test');
		$_SERVER["REQUEST_TIME_FLOAT"] = microtime(true) - 1.0;
		$pageloadtime->renderContents($writer);
		
		self::assertEquals(1, preg_match('/[\d\.]+test/', $output = $writer->flush()));
		
	}
}
