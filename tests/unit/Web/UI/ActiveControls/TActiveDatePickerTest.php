<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use PHPUnit\Framework\TestCase;
use Prado\IO\TTextWriter;
use Prado\Test\Unit\Harness\Traits\TAssetManagerMockTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\ActiveControls\TActiveDatePicker;
use Prado\Web\UI\ActiveControls\TActiveDatePickerClientScript;
use Prado\Web\UI\ActiveControls\TCallbackEventParameter;
use Prado\Web\UI\TControl;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\WebControls\TDatePickerInputMode;

class TActiveDatePickerTest extends TestCase
{
	use TAssetManagerMockTrait;

	protected function tearDown(): void
	{
		$this->restoreAssetManager();
	}

	private function createPicker(bool $callback = true, int $stage = TControl::CS_LOADED): array
	{
		$page = new TActiveTestPage($callback);
		$picker = $page->addControl(new TActiveDatePicker(), 'Pick', $stage);
		return [$page, $picker];
	}

	public function testAutoPostBackDefaultsTrue()
	{
		$picker = new TActiveDatePicker();
		$this->assertTrue($picker->getAutoPostBack());
		$picker->setAutoPostBack('false');
		$this->assertFalse($picker->getAutoPostBack());
	}

	public function testClientSideIsAnActiveDatePickerClientScript()
	{
		$picker = new TActiveDatePicker();
		$client = $picker->getClientSide();
		$this->assertInstanceOf(TActiveDatePickerClientScript::class, $client);
		$this->assertNull($client->getOnDateChanged());
		$client->setOnDateChanged('changed()');
		$this->assertStringContainsString('changed()', (string) $client->getOnDateChanged());
	}

	public function testDatePickerOptionsCarryTheCallbackOptions()
	{
		[, $picker] = $this->createPicker();
		$picker->setCausesValidation(false);
		$picker->setValidationGroup('G');
		$picker->setShowCalendar(false);
		$picker->setAutoPostBack(false);
		$options = PradoUnit::invoke($picker, 'getDatePickerOptions');
		$this->assertSame('Pick', $options['ID']);
		$this->assertFalse($options['CausesValidation']);
		$this->assertSame('G', $options['ValidationGroup']);
		$this->assertSame('Pick', $options['EventTarget']);
		$this->assertFalse($options['ShowCalendar']);
		$this->assertFalse($options['AutoPostBack']);
	}

	public function testSetTextAfterPostDataSetsTheValueOnce()
	{
		[$page, $picker] = $this->createPicker();
		$picker->setText('05-03-2024');
		$picker->setText('05-03-2024');
		$this->assertSame([['Prado.Element.j' => ['Pick', 'val', ['05-03-2024']]]], $page->getClientFunctions());
	}

	public function testSetTextOfDropDownListsSelectsTheDayMonthAndYear()
	{
		[$page, $picker] = $this->createPicker();
		$picker->setDateInputMode(TDatePickerInputMode::DropDownList);
		$picker->setText('05-03-2024');
		$this->assertSame([
			['Prado.Element.j' => ['Pick', 'val', ['05-03-2024']]],
			['Prado.Element.select' => ['Pick_day', 'selectValue', '5', 1]],
			['Prado.Element.select' => ['Pick_month', 'selectValue', 2, 1]],
			['Prado.Element.select' => ['Pick_year', 'selectValue', '2024', 1]],
		], $page->getClientFunctions());
	}

	public function testSetTextBeforePostDataOrOutsideACallbackUpdatesNothing()
	{
		[$page, $picker] = $this->createPicker(true, TControl::CS_INITIALIZED);
		$picker->setText('05-03-2024');
		$this->assertSame([], $page->getClientFunctions());

		[$page, $picker] = $this->createPicker(false);
		$picker->setText('05-03-2024');
		$this->assertSame([], $page->getClientFunctions());
		$this->assertSame('05-03-2024', $picker->getText());
	}

	public function testRaiseCallbackEventRaisesOnCallback()
	{
		[, $picker] = $this->createPicker();
		$params = [];
		$picker->attachEventHandler('OnCallback', function ($sender, $param) use (&$params) {
			$params[] = $param->getCallbackParameter();
		});
		$picker->raiseCallbackEvent(new TCallbackEventParameter(null, 'p'));
		$this->assertSame(['p'], $params);
	}

	public function testRegisterCalendarClientScriptPreRegistersTheActiveDatePickerScript()
	{
		[$page, $picker] = $this->createPicker();
		PradoUnit::invoke($picker, 'registerCalendarClientScriptPre');
		$this->assertSame(['activedatepicker'], $page->cs->pradoScripts);
	}

	public function testRenderClientControlScriptRegistersTheSpacerOnceAndTheControl()
	{
		$this->mockAssetManager();
		[$page, $picker] = $this->createPicker();
		$writer = new THtmlWriter(new TTextWriter());
		PradoUnit::invoke($picker, 'renderClientControlScript', $writer);
		$spacer = PradoUnit::getProp($page->cs, '_endScripts')['TDatePicker.spacer'];
		$this->assertStringStartsWith("Prado.WebUI.TDatePicker.spacer = '", $spacer);
		$this->assertStringEndsWith("spacer.gif';", $spacer);
		$this->assertStringStartsWith('new Prado.WebUI.TActiveDatePicker({', PradoUnit::getProp($page->cs, '_endScripts')['prado:Pick']);

		$page->cs->registerEndScript('TDatePicker.spacer', 'kept');
		PradoUnit::invoke($picker, 'renderClientControlScript', $writer);
		$this->assertSame('kept', PradoUnit::getProp($page->cs, '_endScripts')['TDatePicker.spacer']);
	}
}
