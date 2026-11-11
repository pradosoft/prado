<?php

namespace Prado\Test\Unit\Util\Email\UI\WebControls;

class TEmailViewLinkTest extends \PHPUnit\Framework\TestCase
{
	public function testConstruct()
	{
		$link = new \Prado\Util\Email\UI\WebControls\TEmailViewLink();
		$this->assertInstanceOf(\Prado\Web\UI\WebControls\TLinkButton::class, $link);
	}
}