<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Web\UI\WebControls\TPanel;

class TPanelTest extends TestCase
{
	use TWebControlRenderTrait;

	public function testGroupingTextEncodedWhenEncode()
	{
		$panel = new TPanel();
		$panel->setGroupingText('<b>a & b</b>');
		$this->assertFalse($panel->getEncode());
		$this->assertStringContainsString('<legend><b>a & b</b></legend>', $this->renderBeginTag($panel));

		$panel->setEncode(true);
		$this->assertTrue($panel->getEncode());
		$this->assertStringContainsString('<legend>&lt;b&gt;a & b&lt;/b&gt;</legend>', $this->renderBeginTag($panel));
	}
}
