<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Web\UI\WebControls\TLinkButton;

class TLinkButtonTest extends TestCase
{
	use TWebControlRenderTrait;

	public function testRenderContentsEncodesTextWhenEncode()
	{
		$button = new TLinkButton();
		$this->assertFalse($button->getEncode());
		$button->setText('<b>a & b</b>');
		$this->assertSame('<b>a & b</b>', $this->renderContents($button));

		$button->setEncode(true);
		$this->assertTrue($button->getEncode());
		$this->assertSame('&lt;b&gt;a & b&lt;/b&gt;', $this->renderContents($button));
	}
}
