<?php

use Prado\Web\UI\ActiveControls\TActiveDetails;
use Prado\Web\UI\ActiveControls\IActiveControl;
use Prado\Web\UI\ActiveControls\ICallbackEventHandler;
use Prado\Web\UI\ActiveControls\TCallbackEventParameter;
use Prado\Web\UI\WebControls\TDetails;
use PHPUnit\Framework\TestCase;

class TActiveDetailsTest extends TestCase
{
	public function testExtendsTDetails()
	{
		$control = new TActiveDetails();
		$this->assertInstanceOf(TDetails::class, $control);
	}

	public function testImplementsIActiveControl()
	{
		$control = new TActiveDetails();
		$this->assertInstanceOf(IActiveControl::class, $control);
	}

	public function testImplementsICallbackEventHandler()
	{
		$control = new TActiveDetails();
		$this->assertInstanceOf(ICallbackEventHandler::class, $control);
	}

	public function testGetActiveControlReturnsBaseActiveCallbackControl()
	{
		$control = new TActiveDetails();
		$activeControl = $control->getActiveControl();
		$this->assertInstanceOf(
			\Prado\Web\UI\ActiveControls\TBaseActiveCallbackControl::class,
			$activeControl
		);
	}

	public function testGetClientSide()
	{
		$control = new TActiveDetails();
		$clientSide = $control->getClientSide();
		$this->assertInstanceOf(
			\Prado\Web\UI\ActiveControls\TCallbackClientSide::class,
			$clientSide
		);
	}

	// --- Inherited TDetails properties ---

	public function testSummaryDefaultEmpty()
	{
		$control = new TActiveDetails();
		$this->assertEquals('', $control->getSummary());
	}

	public function testSetSummary()
	{
		$control = new TActiveDetails();
		$control->setSummary('Details summary');
		$this->assertEquals('Details summary', $control->getSummary());
	}

	public function testOpenDefaultFalse()
	{
		$control = new TActiveDetails();
		$this->assertFalse($control->getOpen());
	}

	public function testGroupDefaultEmpty()
	{
		$control = new TActiveDetails();
		$this->assertEquals('', $control->getGroup());
	}

	public function testSetGroup()
	{
		$control = new TActiveDetails();
		$control->setGroup('accordion');
		$this->assertEquals('accordion', $control->getGroup());
	}

	// --- setOpen without active page context (no canUpdateClientSide) ---

	public function testSetOpenTrueWithoutPage()
	{
		$control = new TActiveDetails();
		// canUpdateClientSide() returns false without a page, so no client update attempted
		$control->setOpen(true);
		$this->assertTrue($control->getOpen());
	}

	public function testSetOpenFalseWithoutPage()
	{
		$control = new TActiveDetails();
		$control->setOpen(true);
		$control->setOpen(false);
		$this->assertFalse($control->getOpen());
	}

	public function testSetOpenSameValueNoOp()
	{
		$control = new TActiveDetails();
		// Default is false — setting false again should not change state
		$control->setOpen(false);
		$this->assertFalse($control->getOpen());

		$control->setOpen(true);
		// Setting true again — same value, no-op
		$control->setOpen(true);
		$this->assertTrue($control->getOpen());
	}

	// --- raiseCallbackEvent ---

	public function testRaiseCallbackEventOpenSetsOpenTrue()
	{
		$control = new TActiveDetails();
		$this->assertFalse($control->getOpen());

		$param = $this->createCallbackEventParameter('open');
		$control->raiseCallbackEvent($param);

		$this->assertTrue($control->getOpen());
	}

	public function testRaiseCallbackEventCloseSetsOpenFalse()
	{
		$control = new TActiveDetails();
		// Start open by raising the 'open' callback first
		$openParam = $this->createCallbackEventParameter('open');
		$control->raiseCallbackEvent($openParam);
		$this->assertTrue($control->getOpen());

		$param = $this->createCallbackEventParameter('close');
		$control->raiseCallbackEvent($param);

		$this->assertFalse($control->getOpen());
	}

	public function testRaiseCallbackEventOpenRaisesOnOpenEvent()
	{
		$control = new TActiveDetails();
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
		$control = new TActiveDetails();
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
		$control = new TActiveDetails();
		$openFired = false;
		$closeFired = false;
		$control->attachEventHandler('OnOpen', function () use (&$openFired) {
			$openFired = true;
		});
		$control->attachEventHandler('OnClose', function () use (&$closeFired) {
			$closeFired = true;
		});

		$param = $this->createCallbackEventParameter('unknown');
		$control->raiseCallbackEvent($param);

		$this->assertFalse($openFired);
		$this->assertFalse($closeFired);
	}

	// --- getClientClassName ---

	public function testGetClientClassName()
	{
		$control = new TActiveDetails();
		$ref = new ReflectionMethod($control, 'getClientClassName');
		$ref->setAccessible(true);
		$this->assertEquals('Prado.WebUI.TActiveDetails', $ref->invoke($control));
	}

	// --- getPostBackOptions ---

	public function testGetPostBackOptionsContainsId()
	{
		$control = new TActiveDetails();
		$ref = new ReflectionMethod($control, 'getPostBackOptions');
		$ref->setAccessible(true);
		$options = $ref->invoke($control);
		$this->assertArrayHasKey('ID', $options);
		$this->assertArrayHasKey('EventTarget', $options);
	}

	// Note: rendering TActiveDetails requires a full page context (registerCallbackClientScript
	// calls getClientScript() which needs TPage). Rendering is covered by integration tests.

	// Helper: create a TCallbackEventParameter-like mock
	private function createCallbackEventParameter(string $param): TCallbackEventParameter
	{
		$eventParam = $this->createMock(TCallbackEventParameter::class);
		$eventParam->method('getCallbackParameter')->willReturn($param);
		return $eventParam;
	}
}
