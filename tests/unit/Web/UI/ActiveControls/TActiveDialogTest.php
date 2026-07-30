<?php

use Prado\Web\UI\ActiveControls\TActiveDialog;
use Prado\Web\UI\ActiveControls\IActiveControl;
use Prado\Web\UI\ActiveControls\ICallbackEventHandler;
use Prado\Web\UI\ActiveControls\TCallbackEventParameter;
use Prado\Web\UI\WebControls\TDialog;
use PHPUnit\Framework\TestCase;

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

	// --- getClientClassName ---

	public function testGetClientClassName()
	{
		$control = new TActiveDialog();
		$ref = new ReflectionMethod($control, 'getClientClassName');
		$ref->setAccessible(true);
		$this->assertEquals('Prado.WebUI.TActiveDialog', $ref->invoke($control));
	}

	// --- getPostBackOptions ---

	public function testGetPostBackOptionsContainsIdAndEventTarget()
	{
		$control = new TActiveDialog();
		$ref = new ReflectionMethod($control, 'getPostBackOptions');
		$ref->setAccessible(true);
		$options = $ref->invoke($control);
		$this->assertArrayHasKey('ID', $options);
		$this->assertArrayHasKey('EventTarget', $options);
	}

	// Note: rendering TActiveDialog requires a full page context (registerCallbackClientScript
	// calls getClientScript() which needs TPage). Rendering is covered by integration tests.

	// Helper: create a TCallbackEventParameter mock
	private function createCallbackEventParameter(string $param): TCallbackEventParameter
	{
		$eventParam = $this->createMock(TCallbackEventParameter::class);
		$eventParam->method('getCallbackParameter')->willReturn($param);
		return $eventParam;
	}
}
