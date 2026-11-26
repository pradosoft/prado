<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Web\UI\ActiveControls\TActiveLinkButton;
use Prado\Web\UI\ActiveControls\TCallbackClientSide;
use Prado\Web\UI\ActiveControls\TCallbackEventParameter;
use Prado\Web\UI\TControl;

class TActiveLinkButtonTest extends TestCase
{
	use TWebControlRenderTrait;

	private function createLink(bool $callback = true, int $stage = TControl::CS_LOADED): array
	{
		$page = new TActiveTestPage($callback);
		$link = $page->addControl(new TActiveLinkButton(), 'Go', $stage);
		return [$page, $link];
	}

	public function testClientSideIsTheActiveControlClientSide()
	{
		$link = new TActiveLinkButton();
		$this->assertInstanceOf(TCallbackClientSide::class, $link->getClientSide());
		$this->assertSame($link->getActiveControl()->getClientSide(), $link->getClientSide());
	}

	public function testRaiseCallbackEventClicksAndRaisesOnCallback()
	{
		[, $link] = $this->createLink();
		$link->setCausesValidation(false);
		$events = [];
		$link->attachEventHandler('OnClick', function () use (&$events) {
			$events[] = 'click';
		});
		$link->attachEventHandler('OnCallback', function ($sender, $param) use (&$events) {
			$events[] = 'callback:' . $param->getCallbackParameter();
		});
		$link->raiseCallbackEvent(new TCallbackEventParameter(null, 'p'));
		$this->assertSame(['click', 'callback:p'], $events);
	}

	public function testSetTextUpdatesTheContentEncodedWhenEncode()
	{
		[$page, $link] = $this->createLink();
		$link->setText('<b>Go</b>');
		$link->setText('<b>Go</b>');
		$link->setEncode(true);
		$link->setText('<i>Go</i>');
		$this->assertSame([
			['Prado.Element.j' => ['Go', 'html', ['<b>Go</b>']]],
			['Prado.Element.j' => ['Go', 'html', ['&lt;i&gt;Go&lt;/i&gt;']]],
		], $page->getClientFunctions());
	}

	public function testSetTextOutsideACallbackUpdatesNothing()
	{
		[$page, $link] = $this->createLink(false);
		$link->setText('Go');
		$this->assertSame([], $page->getClientFunctions());
	}

	public function testDisableRemovesTheHrefAndEnableRestoresItAndTheCallbackControl()
	{
		[$page, $link] = $this->createLink();
		$link->setEnabled(false);
		$this->assertSame([['Prado.Element.removeAttribute' => ['Go', 'href']]], $page->getClientFunctions());
		$this->assertSame([], $page->cs->callbackControls);

		$link->setEnabled(true);
		$this->assertSame(['Prado.Element.setAttribute' => ['Go', 'href', 'javascript:;//Go']], $page->getClientFunctions()[1]);
		$this->assertSame('Prado.WebUI.TActiveLinkButton', $page->cs->callbackControls[0][0]);
	}

	public function testEnableInADisabledParentRemovesTheHref()
	{
		[$page, $link] = $this->createLink();
		$link->setEnabled(false);
		$page->form->setEnabled(false);
		$link->setEnabled(true);
		$this->assertSame(['Prado.Element.removeAttribute' => ['Go', 'href']], $page->getClientFunctions()[1]);
		$this->assertSame([], $page->cs->callbackControls);
	}

	public function testSetEnabledUnchangedOrOutsideACallbackUpdatesNothing()
	{
		[$page, $link] = $this->createLink();
		$link->setEnabled(true);
		$this->assertSame([], $page->getClientFunctions());

		[$page, $link] = $this->createLink(false);
		$link->setEnabled(false);
		$this->assertSame([], $page->getClientFunctions());
		$this->assertFalse($link->getEnabled());
	}

	public function testRenderRegistersTheCallbackControlWhenEnabled()
	{
		[$page, $link] = $this->createLink();
		$link->setText('Go');
		$html = $this->render($link);
		$this->assertStringContainsString('id="Go"', $html);
		$this->assertStringNotContainsString('onclick', $html);
		$this->assertCount(1, $page->cs->callbackControls);
		[$class, $options] = $page->cs->callbackControls[0];
		$this->assertSame('Prado.WebUI.TActiveLinkButton', $class);
		$this->assertTrue($options['StopEvent']);
	}

	public function testRenderOfADisabledLinkRegistersNoCallbackControl()
	{
		[$page, $link] = $this->createLink(true, TControl::CS_INITIALIZED);
		$link->setEnabled(false);
		$html = $this->render($link);
		$this->assertStringContainsString('id="Go"', $html);
		$this->assertSame([], $page->cs->callbackControls);
	}
}
