<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use Prado\Web\UI\ActiveControls\TActiveDialog;
use Prado\Web\UI\ActiveControls\IActiveControl;
use Prado\Web\UI\ActiveControls\ICallbackEventHandler;
use Prado\Web\UI\ActiveControls\TCallbackEventParameter;
use Prado\Web\UI\WebControls\TDialog;
use PHPUnit\Framework\TestCase;
use Prado\IO\TTextWriter;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\TControl;
use Prado\Web\UI\THtmlWriter;

class TActiveDialogTest extends TestCase
{
	public function testExtendsTDialog()
	{
		$control = new TActiveDialog();
		$this->assertInstanceOf(TDialog::class, $control);
	}

	public function testImplementsIActiveControl()
	{
		$control = new TActiveDialog();
		$this->assertInstanceOf(IActiveControl::class, $control);
	}

	public function testImplementsICallbackEventHandler()
	{
		$control = new TActiveDialog();
		$this->assertInstanceOf(ICallbackEventHandler::class, $control);
	}

	public function testGetActiveControlReturnsBaseActiveCallbackControl()
	{
		$control = new TActiveDialog();
		$activeControl = $control->getActiveControl();
		$this->assertInstanceOf(
			\Prado\Web\UI\ActiveControls\TBaseActiveCallbackControl::class,
			$activeControl
		);
	}

	public function testGetClientSide()
	{
		$control = new TActiveDialog();
		$clientSide = $control->getClientSide();
		$this->assertInstanceOf(
			\Prado\Web\UI\ActiveControls\TCallbackClientSide::class,
			$clientSide
		);
	}

	// --- Inherited TDialog properties ---

	public function testOpenDefaultFalse()
	{
		$control = new TActiveDialog();
		$this->assertFalse($control->getOpen());
	}

	// --- setOpen without active page context (no canUpdateClientSide) ---

	public function testSetOpenTrueWithoutPage()
	{
		$control = new TActiveDialog();
		// canUpdateClientSide() returns false without a page, so no client update attempted
		$control->setOpen(true);
		$this->assertTrue($control->getOpen());
	}

	public function testSetOpenFalseWithoutPage()
	{
		$control = new TActiveDialog();
		$control->setOpen(true);
		$control->setOpen(false);
		$this->assertFalse($control->getOpen());
	}

	public function testSetOpenSameValueNoOp()
	{
		$control = new TActiveDialog();
		// Default is false — setting false again should be a no-op
		$control->setOpen(false);
		$this->assertFalse($control->getOpen());

		$control->setOpen(true);
		// Setting true again — same value, no-op
		$control->setOpen(true);
		$this->assertTrue($control->getOpen());
	}

	public function testSetOpenFromString()
	{
		$control = new TActiveDialog();
		$control->setOpen('true');
		$this->assertTrue($control->getOpen());

		$control->setOpen('false');
		$this->assertFalse($control->getOpen());
	}

	// --- raiseCallbackEvent ---

	public function testRaiseCallbackEventOpenSetsOpenTrue()
	{
		$control = new TActiveDialog();
		$this->assertFalse($control->getOpen());

		$param = $this->createCallbackEventParameter('open');
		$control->raiseCallbackEvent($param);

		$this->assertTrue($control->getOpen());
	}

	public function testRaiseCallbackEventCloseSetsOpenFalse()
	{
		$control = new TActiveDialog();
		$control->setOpen(true);

		$param = $this->createCallbackEventParameter('close');
		$control->raiseCallbackEvent($param);

		$this->assertFalse($control->getOpen());
	}

	public function testRaiseCallbackEventOpenRaisesOnOpenEvent()
	{
		$control = new TActiveDialog();
		$fired = false;
		$control->attachEventHandler('OnOpen', function () use (&$fired) {
			$fired = true;
		});

		$param = $this->createCallbackEventParameter('open');
		$control->raiseCallbackEvent($param);

		$this->assertTrue($fired, 'OnOpen event should have been raised');
	}

	public function testRaiseCallbackEventCloseRaisesOnCloseEvent()
	{
		$control = new TActiveDialog();
		$fired = false;
		$control->attachEventHandler('OnClose', function () use (&$fired) {
			$fired = true;
		});

		$param = $this->createCallbackEventParameter('close');
		$control->raiseCallbackEvent($param);

		$this->assertTrue($fired, 'OnClose event should have been raised');
	}

	public function testRaiseCallbackEventUnknownParameterDoesNothing()
	{
		$control = new TActiveDialog();
		$openFired = false;
		$closeFired = false;
		$control->attachEventHandler('OnOpen', function () use (&$openFired) {
			$openFired = true;
		});
		$control->attachEventHandler('OnClose', function () use (&$closeFired) {
			$closeFired = true;
		});

		$param = $this->createCallbackEventParameter('unexpected');
		$control->raiseCallbackEvent($param);

		$this->assertFalse($openFired);
		$this->assertFalse($closeFired);
	}

	public function testReturnValueDefaultsEmpty()
	{
		$this->assertSame('', (new TActiveDialog())->getReturnValue());
	}

	public function testRaiseCallbackEventCloseSetsReturnValueBeforeOnClose()
	{
		$control = new TActiveDialog();
		$control->setOpen(true);
		$seen = null;
		$control->attachEventHandler('OnClose', function ($sender) use (&$seen) {
			$seen = $sender->getReturnValue();
		});

		$control->raiseCallbackEvent($this->createCallbackEventParameter(['close', 'confirm']));

		$this->assertFalse($control->getOpen());
		$this->assertSame('confirm', $seen);
		$this->assertSame('confirm', $control->getReturnValue());
	}

	public function testRaiseCallbackEventCloseWithoutReturnValue()
	{
		$control = new TActiveDialog();
		$control->raiseCallbackEvent($this->createCallbackEventParameter(['close', 'confirm']));

		$control->raiseCallbackEvent($this->createCallbackEventParameter('close'));
		$this->assertSame('', $control->getReturnValue());

		$control->raiseCallbackEvent($this->createCallbackEventParameter(['close', ['nested']]));
		$this->assertSame('', $control->getReturnValue());
	}

	public function testOpeningResetsReturnValue()
	{
		$control = new TActiveDialog();
		$control->raiseCallbackEvent($this->createCallbackEventParameter(['close', 'confirm']));
		$control->raiseCallbackEvent($this->createCallbackEventParameter('open'));
		$this->assertSame('', $control->getReturnValue());

		$control->raiseCallbackEvent($this->createCallbackEventParameter(['close', 'cancel']));
		$control->setOpen(true);
		$this->assertSame('', $control->getReturnValue());
	}

	public function testRaiseCallbackEventArrayOpen()
	{
		$control = new TActiveDialog();
		$control->raiseCallbackEvent($this->createCallbackEventParameter(['open']));
		$this->assertTrue($control->getOpen());
	}

	// --- getClientClassName ---

	public function testGetClientClassName()
	{
		$control = new TActiveDialog();
		$ref = new \ReflectionMethod($control, 'getClientClassName');
		$this->assertEquals('Prado.WebUI.TActiveDialog', $ref->invoke($control));
	}

	// --- getPostBackOptions ---

	public function testGetPostBackOptionsContainsIdAndEventTarget()
	{
		$control = new TActiveDialog();
		$ref = new \ReflectionMethod($control, 'getPostBackOptions');
		$options = $ref->invoke($control);
		$this->assertArrayHasKey('ID', $options);
		$this->assertArrayHasKey('EventTarget', $options);
		$this->assertFalse($options['ShowModal']);
	}

	public function testPostBackOptionsShowModalWhenOpenAndModal()
	{
		$control = new TActiveDialog();
		$ref = new \ReflectionMethod($control, 'getPostBackOptions');
		$control->setModal(true);
		$this->assertFalse($ref->invoke($control)['ShowModal']);

		$control->setOpen(true);
		$this->assertTrue($ref->invoke($control)['ShowModal']);

		$control->setModal(false);
		$this->assertFalse($ref->invoke($control)['ShowModal']);
	}

	public static function callbackParameterProvider(): array
	{
		return [
			'open' => ['open', true, ''],
			'array open' => [['open'], true, ''],
			'close' => ['close', false, ''],
			'array close' => [['close'], false, ''],
			'array close with value' => [['close', 'yes'], false, 'yes'],
			'array close with a number' => [['close', 2], false, '2'],
			'array close with an array' => [['close', ['x']], false, ''],
			'array unknown' => [['toggle'], null, 'kept'],
			'empty array' => [[], null, 'kept'],
			'unknown' => ['toggle', null, 'kept'],
		];
	}

	/**
	 * @dataProvider callbackParameterProvider
	 */
	public function testRaiseCallbackEventActionsAndReturnValue($parameter, ?bool $open, string $returnValue)
	{
		$control = new TActiveDialog();
		$control->setViewState('ReturnValue', 'kept', '');
		$control->raiseCallbackEvent(new TCallbackEventParameter(null, $parameter));
		if ($open !== null) {
			$this->assertSame($open, $control->getOpen());
		} else {
			$this->assertFalse($control->getOpen());
		}
		$this->assertSame($returnValue, $control->getReturnValue());
	}

	public function testSetOpenUpdatesTheClientInACallback()
	{
		$page = new TActiveTestPage();
		$dialog = $page->addControl(new TActiveDialog(), 'Dlg');
		$dialog->setModal(true);
		$dialog->setOpen(true);
		$dialog->setOpen(false);
		$this->assertSame([
			['Prado.WebUI.TActiveDialog.setOpen' => ['Dlg', true, true]],
			['Prado.WebUI.TActiveDialog.setOpen' => ['Dlg', false, true]],
		], $page->getClientFunctions());

		$page = new TActiveTestPage(false);
		$dialog = $page->addControl(new TActiveDialog(), 'Dlg');
		$dialog->setOpen(true);
		$this->assertSame([], $page->getClientFunctions());
	}

	public function testAddAttributesToRenderRegistersTheCallbackControlAndNoModalScript()
	{
		$page = new TActiveTestPage();
		$dialog = $page->addControl(new TActiveDialog(), 'Dlg', TControl::CS_CONSTRUCTED);
		$dialog->setOpen(true);
		$dialog->setModal(true);
		$writer = new THtmlWriter(new TTextWriter());
		PradoUnit::invoke($dialog, 'addAttributesToRender', $writer);
		$writer->renderBeginTag('dialog');
		$this->assertStringContainsString('id="Dlg"', $writer->flush());
		$this->assertSame('Prado.WebUI.TActiveDialog', $page->cs->callbackControls[0][0]);
		$this->assertTrue($page->cs->callbackControls[0][1]['ShowModal']);
		$this->assertArrayNotHasKey('prado:dialog:Dlg', PradoUnit::getProp($page->cs, '_endScripts'));
		PradoUnit::invoke($dialog, 'registerShowModalScript');
		$this->assertArrayNotHasKey('prado:dialog:Dlg', PradoUnit::getProp($page->cs, '_endScripts'));
	}

	// Helper: create a TCallbackEventParameter mock
	private function createCallbackEventParameter(string|array $param): TCallbackEventParameter
	{
		$eventParam = $this->createMock(TCallbackEventParameter::class);
		$eventParam->method('getCallbackParameter')->willReturn($param);
		return $eventParam;
	}
}
