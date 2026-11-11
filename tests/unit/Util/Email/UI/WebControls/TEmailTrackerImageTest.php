<?php

/**
 * @package Prado\Util\Email\Tests
 */
class TEmailTrackerImageTest extends \PHPUnit\Framework\TestCase
{
	public function testConstruct()
	{
		$image = new \Prado\Util\Email\UI\WebControls\TEmailTrackerImage();
		$this->assertInstanceOf(\Prado\Web\UI\WebControls\TImage::class, $image);
	}
}