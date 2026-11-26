<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\ActiveControls\TActiveButton;
use Prado\Web\UI\ActiveControls\TCallbackClientSide;
use Prado\Web\UI\ActiveControls\TCallbackEventParameter;
use Prado\Web\UI\TControl;
use Prado\Web\UI\WebControls\TButtonTag;

class TActiveButtonTest extends TestCase
{
	use TWebControlRenderTrait;

	private function createButton(bool $callback = true, int $stage = TControl::CS_LOADED): array
	{
		$page = new TActiveTestPage($callback);
		$button = $page->addControl(new TActiveButton(), 'Go', $stage);
		return [$page, $button];
	}

	public function testClientSideIsTheActiveControlClientSide()
	{
		$button = new TActiveButton();
		$this->assertInstanceOf(TCallbackClientSide::class, $button->getClientSide());
		$this->assertSame($button->getActiveControl()->getClientSide(), $button->getClientSide());
	}

	public function testRaiseCallbackEventClicksAndRaisesOnCallback()
	{
		[$page, $button] = $this->createButton();
		$button->setCausesValidation(false);
		$events = [];
		$button->attachEventHandler('OnClick', function ($sender, $param) use (&$events) {
			$events[] = 'click';
		});
		$button->attachEventHandler('OnCallback', function ($sender, $param) use (&$events) {
			$events[] = 'callback:' . $param->getCallbackParameter();
		});
		$button->raiseCallbackEvent(new TCallbackEventParameter(null, 'p'));
		$this->assertSame(['click', 'callback:p'], $events);
	}

	public function testSetTextOfAnInputSetsTheValueAttribute()
	{
		[$page, $button] = $this->createButton();
		$button->setText('Save');
		$this->assertSame([['Prado.Element.setAttribute' => ['Go', 'value', 'Save']]], $page->getClientFunctions());
	}

	public function testSetTextOfAButtonTagUpdatesTheContent()
	{
		[$page, $button] = $this->createButton();
		$button->setButtonTag(TButtonTag::Button);
		$button->setText('<b>Save</b>');
		$this->assertSame([['Prado.Element.j' => ['Go', 'html', ['<b>Save</b>']]]], $page->getClientFunctions());
	}

	public function testSetTextUnchangedOrOutsideACallbackUpdatesNothing()
	{
		[$page, $button] = $this->createButton();
		$button->setText('Save');
		$button->setText('Save');
		$this->assertCount(1, $page->getClientFunctions());

		[$page, $button] = $this->createButton(false);
		$button->setText('Save');
		$this->assertSame([], $page->getClientFunctions());
		$this->assertSame('Save', $button->getText());
	}

	public function testRenderRegistersTheCallbackControlWithoutThePostBackScript()
	{
		[$page, $button] = $this->createButton();
		$html = $this->render($button);
		$this->assertStringContainsString('id="Go"', $html);
		$this->assertStringNotContainsString('onclick', $html);
		$this->assertCount(1, $page->cs->callbackControls);
		[$class, $options] = $page->cs->callbackControls[0];
		$this->assertSame('Prado.WebUI.TActiveButton', $class);
		$this->assertSame('Go', $options['ID']);
		$this->assertSame('Go', $options['EventTarget']);
	}

	public function testRenderOfADefaultButtonRendersNoPostBackScript()
	{
		[$page, $button] = $this->createButton();
		$button->setIsDefaultButton(true);
		$html = $this->render($button);
		$this->assertStringNotContainsString('onclick', $html);
		$this->assertSame([], PradoUnit::getProp($page->cs, '_endScripts'));
		$this->assertCount(1, $page->cs->callbackControls);
	}
}
