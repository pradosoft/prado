<?php

namespace Prado\Test\Unit\Util\Email\UI\WebControls;

class TEmailTrackerImageTest extends \PHPUnit\Framework\TestCase
{
	public function testConstruct()
	{
		$image = new \Prado\Util\Email\UI\WebControls\TEmailTrackerImage();
		$this->assertInstanceOf(\Prado\Web\UI\WebControls\TImage::class, $image);
	}
}