<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TConfigurationException;
use Prado\IO\TTextWriter;
use Prado\Prado;
use Prado\Test\Unit\Harness\Web\TTestUrlAssetManager;
use Prado\Test\Unit\Harness\Web\UI\TTestCallbackPage;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\ActiveControls\TEventTriggeredCallback;
use Prado\Web\UI\ActiveControls\TTriggeredCallback;
use Prado\Web\UI\ActiveControls\TValueTriggeredCallback;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\WebControls\TTextBox;

/**
 * The callbacks a client-side trigger raises: {@see TEventTriggeredCallback} on a DOM event
 * and {@see TValueTriggeredCallback} on a changed property, through their base
 * {@see TTriggeredCallback}, which names the control the trigger watches.
 */
class TTriggeredCallbackTest extends TestCase
{
	private array $snapshot = [];

	protected function setUp(): void
	{
		$this->snapshot = PradoUnit::snapshot(Prado::getApplication(), ['_assetManager']);
	}

	protected function tearDown(): void
	{
		PradoUnit::restore(Prado::getApplication(), $this->snapshot);
	}

	/**
	 * Places the trigger with the ID `trigger` and a text box `Name` in a callback page with a client script manager.
	 * @return TTestCallbackPage the page
	 */
	private function place(TTriggeredCallback $trigger): TTestCallbackPage
	{
		Prado::getApplication()->setAssetManager(new TTestUrlAssetManager());
		$page = new TTestCallbackPage();
		PradoUnit::setProp($page, '_clientScript', new TClientScriptManager($page));
		$box = new TTextBox();
		$box->setID('Name');
		$page->getControls()->add($box);
		$trigger->setID('trigger');
		$page->getControls()->add($trigger);
		return $page;
	}

	/**
	 * @return array the end scripts of the page after the trigger renders
	 */
	private function renderScripts(TTestCallbackPage $page, TTriggeredCallback $trigger): array
	{
		$trigger->render(new THtmlWriter(new TTextWriter()));
		return array_values(PradoUnit::getProp($page->getClientScript(), '_endScripts'));
	}

	public function testControlIDNamesAControlByItsClientID()
	{
		$trigger = new TEventTriggeredCallback();
		$this->place($trigger);
		$this->assertSame('', $trigger->getControlID());
		$trigger->setControlID('Name');
		$this->assertSame('Name', $trigger->getControlID());
		$this->assertSame('Name', PradoUnit::invoke($trigger, 'getTargetControl'));
	}

	public function testControlIDOfNoControlIsSentAsAClientID()
	{
		$trigger = new TEventTriggeredCallback();
		$this->place($trigger);
		$trigger->setControlID('client-element');
		$this->assertSame('client-element', PradoUnit::invoke($trigger, 'getTargetControl'));
	}

	public function testEmptyControlIDThrows()
	{
		$trigger = new TEventTriggeredCallback();
		$this->place($trigger);
		$this->expectException(TConfigurationException::class);
		PradoUnit::invoke($trigger, 'getTargetControl');
	}

	public function testValueTriggerSendsThePropertyIntervalAndDecay()
	{
		$trigger = new TValueTriggeredCallback();
		$page = $this->place($trigger);
		$trigger->setControlID('Name');
		$this->assertSame('', $trigger->getPropertyName());
		$this->assertSame(1, $trigger->getInterval());
		$this->assertSame(0, $trigger->getDecayRate());
		$trigger->setPropertyName('value');
		$trigger->setInterval('0.5');
		$trigger->setDecayRate('0.25');
		$this->assertSame('value', $trigger->getPropertyName());
		$this->assertSame(0.5, $trigger->getInterval());
		$this->assertSame(0.25, $trigger->getDecayRate());
		$this->assertSame(
			["new Prado.WebUI.TValueTriggeredCallback({'EventTarget':\"trigger\",'ID':\"trigger\",'ControlID':\"Name\",'PropertyName':\"value\",'Interval':0.5,'Decay':0.25});"],
			$this->renderScripts($page, $trigger)
		);
	}

	public function testValueTriggerRejectsANegativeDecayRate()
	{
		$this->expectException(TConfigurationException::class);
		(new TValueTriggeredCallback())->setDecayRate(-1);
	}

	public function testEventTriggerSendsTheEventNameWithoutItsOnPrefix()
	{
		$trigger = new TEventTriggeredCallback();
		$page = $this->place($trigger);
		$trigger->setControlID('Name');
		$this->assertSame('', $trigger->getEventName());
		$this->assertFalse($trigger->getPreventDefaultAction());
		$trigger->setEventName('OnKeyUp');
		$trigger->setPreventDefaultAction('true');
		$this->assertSame('OnKeyUp', $trigger->getEventName());
		$this->assertTrue($trigger->getPreventDefaultAction());
		$this->assertSame(
			["new Prado.WebUI.TEventTriggeredCallback({'EventTarget':\"trigger\",'ID':\"trigger\",'ControlID':\"Name\",'EventName':\"keyup\",'StopEvent':true});"],
			$this->renderScripts($page, $trigger)
		);
	}

	public function testEventTriggerWithoutAnEventNameLeavesTheEventToTheClient()
	{
		$trigger = new TEventTriggeredCallback();
		$this->place($trigger);
		$trigger->setControlID('Name');
		$options = PradoUnit::invoke($trigger, 'getTriggerOptions');
		$this->assertArrayNotHasKey('EventName', $options);
		$trigger->setEventName('onclick');
		$this->assertSame('click', PradoUnit::invoke($trigger, 'getTriggerOptions')['EventName']);
	}
}
