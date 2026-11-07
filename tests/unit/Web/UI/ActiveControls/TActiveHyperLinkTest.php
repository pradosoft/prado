<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Web\UI\TTestCallbackPage;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\ActiveControls\TActiveHyperLink;
use Prado\Web\UI\TControl;

/**
 * The callback update of Text is encoded, as the THyperLink render is.
 */
class TActiveHyperLinkTest extends TestCase
{
	public function testSetTextUpdatesTheClientEncoded()
	{
		$page = new TTestCallbackPage();
		$link = new TActiveHyperLink();
		$link->setID('Target');
		$page->getControls()->add($link);
		PradoUnit::invoke($link, 'setControlStage', TControl::CS_LOADED);

		$link->setText('<b>a & b</b>');

		$functions = json_encode($page->getClientFunctions());
		$this->assertStringContainsString(json_encode('&lt;b&gt;a & b&lt;/b&gt;'), $functions);
		$this->assertStringNotContainsString(json_encode('<b>a & b</b>'), $functions);
		$this->assertSame('<b>a & b</b>', $link->getText());
	}
}
