<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TConfigurationException;
use Prado\IO\TTextWriter;
use Prado\Prado;
use Prado\Test\Unit\Harness\Web\TTestUrlAssetManager;
use Prado\Test\Unit\Harness\Web\UI\TTestCallbackPage;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\ActiveControls\TTimeTriggeredCallback;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\TControl;
use Prado\Web\UI\THtmlWriter;

/**
 * TTimeTriggeredCallback: its interval, its timer functions, the PauseWhenHidden property,
 * and the client script it renders.
 */
class TTimeTriggeredCallbackTest extends TestCase
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
	 * @return array{TTestCallbackPage, TTimeTriggeredCallback} a timer with the ID `timer` in a callback page
	 *   with a client script manager, at the stage
	 */
	private function createTimer(int $stage = TControl::CS_LOADED): array
	{
		Prado::getApplication()->setAssetManager(new TTestUrlAssetManager());
		$page = new TTestCallbackPage();
		PradoUnit::setProp($page, '_clientScript', new TClientScriptManager($page));
		$timer = new TTimeTriggeredCallback();
		$timer->setID('timer');
		$page->getControls()->add($timer);
		PradoUnit::invoke($timer, 'setControlStage', $stage);
		return [$page, $timer];
	}

	public function testIntervalMustBePositive()
	{
		$this->expectException(TConfigurationException::class);
		(new TTimeTriggeredCallback())->setInterval('0');
	}

	public function testIntervalBeforeInitializationSendsNothing()
	{
		[$page, $timer] = $this->createTimer(TControl::CS_CONSTRUCTED);
		$timer->setInterval(3);
		$this->assertSame(3.0, $timer->getInterval());
		$this->assertSame([], $page->getClientFunctions());
	}

	public function testStartAndStopTimerCallTheClient()
	{
		[$page, $timer] = $this->createTimer();
		$timer->startTimer();
		$timer->stopTimer();
		$this->assertSame([
			['Prado.WebUI.TTimeTriggeredCallback.start' => ['timer']],
			['Prado.WebUI.TTimeTriggeredCallback.stop' => ['timer']],
		], $page->getClientFunctions());
	}

	public function testRenderRegistersTheTimerAndStartsItOnLoad()
	{
		[$page, $timer] = $this->createTimer();
		$this->assertFalse($timer->getStartTimerOnLoad());
		$timer->setStartTimerOnLoad('true');
		$this->assertTrue($timer->getStartTimerOnLoad());
		$timer->setInterval(2);
		$timer->render(new THtmlWriter(new TTextWriter()));
		$scripts = PradoUnit::getProp($page->getClientScript(), '_endScripts');
		$this->assertContains("new Prado.WebUI.TTimeTriggeredCallback({'EventTarget':\"timer\",'ID':\"timer\",'Interval':2});", $scripts);
		$this->assertSame("Prado.WebUI.TTimeTriggeredCallback.start('timer');", $scripts['timer:start']);
	}

	public function testRenderWithoutStartTimerOnLoadOnlyRegistersTheTimer()
	{
		[$page, $timer] = $this->createTimer();
		$timer->render(new THtmlWriter(new TTextWriter()));
		$scripts = PradoUnit::getProp($page->getClientScript(), '_endScripts');
		$this->assertCount(1, $scripts);
		$this->assertArrayNotHasKey('timer:start', $scripts);
	}

	public function testPauseWhenHiddenDefaultsToTrueAndIsNotSentToTheClient()
	{
		$timer = new TTimeTriggeredCallback();
		$timer->setID('timer');
		$this->assertTrue($timer->getPauseWhenHidden());
		$options = PradoUnit::invoke($timer, 'getTriggerOptions');
		$this->assertArrayNotHasKey('PauseWhenHidden', $options);
		$this->assertSame(1, $options['Interval']);
	}

	public function testPauseWhenHiddenFalseIsSentToTheClient()
	{
		$timer = new TTimeTriggeredCallback();
		$timer->setID('timer');
		$timer->setPauseWhenHidden('false');
		$this->assertFalse($timer->getPauseWhenHidden());
		$options = PradoUnit::invoke($timer, 'getTriggerOptions');
		$this->assertFalse($options['PauseWhenHidden']);
		$timer->setPauseWhenHidden(1);
		$this->assertTrue($timer->getPauseWhenHidden());
	}
}
