<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Test\Unit\Harness\Web\UI\TTestRecordingClientScript;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TButton;
use Prado\Web\UI\WebControls\TContentDirection;
use Prado\Web\UI\WebControls\THorizontalAlign;
use Prado\Web\UI\WebControls\TPanel;
use Prado\Web\UI\WebControls\TPanelStyle;
use Prado\Web\UI\WebControls\TScrollBars;

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

	public function testStylePropertiesDelegateToThePanelStyle()
	{
		$panel = new TPanel();
		$this->assertInstanceOf(TPanelStyle::class, $panel->getStyle());
		$this->assertTrue($panel->getWrap());
		$this->assertSame(THorizontalAlign::NotSet, $panel->getHorizontalAlign());
		$this->assertSame('', $panel->getBackImageUrl());
		$this->assertSame(TContentDirection::NotSet, $panel->getDirection());
		$this->assertSame(TScrollBars::None, $panel->getScrollBars());

		$panel->setWrap(false);
		$panel->setHorizontalAlign(THorizontalAlign::Center);
		$panel->setBackImageUrl('bg.png');
		$panel->setDirection(TContentDirection::RightToLeft);
		$panel->setScrollBars(TScrollBars::Auto);
		$this->assertFalse($panel->getWrap());
		$this->assertSame(THorizontalAlign::Center, $panel->getHorizontalAlign());
		$this->assertSame('bg.png', $panel->getBackImageUrl());
		$this->assertSame(TContentDirection::RightToLeft, $panel->getDirection());
		$this->assertSame(TScrollBars::Auto, $panel->getScrollBars());
		$this->assertSame(
			'<div style="background-image:url(bg.png);overflow:auto;text-align:center;white-space:nowrap;direction:rtl;"></div>',
			$this->render($panel)
		);
	}

	public function testGroupingTextWrapsTheBodyInAFieldset()
	{
		$panel = new TPanel();
		$this->assertSame('', $panel->getGroupingText());
		$panel->setGroupingText('Address');
		$panel->getControls()->add('body');
		$this->assertSame('<div><fieldset><legend>Address</legend>body</fieldset></div>', $this->render($panel));
	}

	/**
	 * Creates a panel without an ID and a button on a page with a recording client script manager.
	 * @return array{TPanel, TButton, TTestRecordingClientScript} the panel, its button, and the client script
	 */
	private function panelWithButton(): array
	{
		$page = new TPage();
		$cs = TTestRecordingClientScript::attachTo($page);
		$panel = new TPanel();
		$page->getControls()->add($panel);
		// a hidden button is found without rendering, which needs a form
		$button = new TButton();
		$button->setID('save');
		$button->setVisible(false);
		$panel->getControls()->add($button);
		return [$panel, $button, $cs];
	}

	public function testDefaultButtonRendersTheIdAndRegistersTheButton()
	{
		[$panel, $button, $cs] = $this->panelWithButton();
		$this->assertSame('', $panel->getDefaultButton());
		$panel->setDefaultButton('save');
		$this->assertSame('save', $panel->getDefaultButton());
		$this->assertStringStartsWith('<div id="' . $panel->getClientID() . '">', $this->render($panel));
		$this->assertSame([[$panel, $button]], $cs->defaultButtons);
	}

	public function testWithoutDefaultButtonRegistersNone()
	{
		[$panel, , $cs] = $this->panelWithButton();
		$this->assertStringStartsWith('<div>', $this->render($panel));
		$this->assertSame([], $cs->defaultButtons);
	}

	public function testMissingDefaultButtonThrows()
	{
		[$panel] = $this->panelWithButton();
		$panel->setDefaultButton('missing');
		try {
			$this->render($panel);
			$this->fail('Expected TInvalidDataValueException');
		} catch (TInvalidDataValueException $e) {
			$this->assertSame('panel_defaultbutton_invalid', $e->getErrorCode());
		}
	}
}
