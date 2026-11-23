<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use Prado\Exceptions\TNotSupportedException;
use Prado\IO\TTextWriter;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TDatePickerClientScript;
use Prado\Web\UI\WebControls\TPanel;
use Prado\Web\UI\WebControls\TDatePicker;
use Prado\Web\UI\WebControls\TDatePickerInputMode;
use Prado\Web\UI\WebControls\TDatePickerMode;
use Prado\Web\UI\WebControls\TDatePickerPositionMode;
use Prado\Web\UI\WebControls\TTextBox;
use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\PradoUnit;

class TDatePickerTest extends TestCase
{
	// ================================================================================
	// Class structure
	// ================================================================================

	public function testExtendsTTextBox()
	{
		$picker = new TDatePicker();
		$this->assertInstanceOf(TTextBox::class, $picker);
	}

	// ================================================================================
	// AutoPostBack
	// ================================================================================

	public function testSetAutoPostBackThrows()
	{
		$picker = new TDatePicker();
		$this->expectException(TNotSupportedException::class);
		$picker->setAutoPostBack(true);
	}

	// ================================================================================
	// DateFormat
	// ================================================================================

	public function testDateFormatDefault()
	{
		$picker = new TDatePicker();
		$this->assertEquals('dd-MM-yyyy', $picker->getDateFormat());
	}

	public function testSetDateFormat()
	{
		$picker = new TDatePicker();
		$picker->setDateFormat('MM/dd/yyyy');
		$this->assertEquals('MM/dd/yyyy', $picker->getDateFormat());
	}

	// ================================================================================
	// ShowCalendar
	// ================================================================================

	public function testShowCalendarDefault()
	{
		$picker = new TDatePicker();
		$this->assertTrue($picker->getShowCalendar());
	}

	public function testSetShowCalendarFalse()
	{
		$picker = new TDatePicker();
		$picker->setShowCalendar(false);
		$this->assertFalse($picker->getShowCalendar());
	}

	public function testSetShowCalendarStringFalse()
	{
		$picker = new TDatePicker();
		$picker->setShowCalendar('false');
		$this->assertFalse($picker->getShowCalendar());
	}

	// ================================================================================
	// Culture
	// ================================================================================

	public function testCultureDefault()
	{
		$picker = new TDatePicker();
		$this->assertEquals('', $picker->getCulture());
	}

	public function testSetCulture()
	{
		$picker = new TDatePicker();
		$picker->setCulture('en_AU');
		$this->assertEquals('en_AU', $picker->getCulture());
	}

	// ================================================================================
	// DateInputMode
	// ================================================================================

	public function testDateInputModeDefault()
	{
		$picker = new TDatePicker();
		$this->assertEquals(TDatePickerInputMode::TextBox, $picker->getDateInputMode());
	}

	public function testSetDateInputModeDropDownList()
	{
		$picker = new TDatePicker();
		$picker->setDateInputMode(TDatePickerInputMode::DropDownList);
		$this->assertEquals(TDatePickerInputMode::DropDownList, $picker->getDateInputMode());
	}

	public function testSetDateInputModeTextBox()
	{
		$picker = new TDatePicker();
		$picker->setDateInputMode(TDatePickerInputMode::DropDownList);
		$picker->setDateInputMode(TDatePickerInputMode::TextBox);
		$this->assertEquals(TDatePickerInputMode::TextBox, $picker->getDateInputMode());
	}

	public function testSetDateInputModeInvalidThrows()
	{
		$picker = new TDatePicker();
		$this->expectException(\Prado\Exceptions\TInvalidDataValueException::class);
		$picker->setDateInputMode('Invalid');
	}

	// ================================================================================
	// Mode
	// ================================================================================

	public function testModeDefault()
	{
		$picker = new TDatePicker();
		$this->assertEquals(TDatePickerMode::Basic, $picker->getMode());
	}

	public function testSetModeButton()
	{
		$picker = new TDatePicker();
		$picker->setMode(TDatePickerMode::Button);
		$this->assertEquals(TDatePickerMode::Button, $picker->getMode());
	}

	public function testSetModeClickable()
	{
		$picker = new TDatePicker();
		$picker->setMode(TDatePickerMode::Clickable);
		$this->assertEquals(TDatePickerMode::Clickable, $picker->getMode());
	}

	public function testSetModeImageButton()
	{
		$picker = new TDatePicker();
		$picker->setMode(TDatePickerMode::ImageButton);
		$this->assertEquals(TDatePickerMode::ImageButton, $picker->getMode());
	}

	public function testSetModeInvalidThrows()
	{
		$picker = new TDatePicker();
		$this->expectException(\Prado\Exceptions\TInvalidDataValueException::class);
		$picker->setMode('Invalid');
	}

	// ================================================================================
	// ButtonImageUrl
	// ================================================================================

	public function testButtonImageUrlDefault()
	{
		$picker = new TDatePicker();
		$this->assertEquals('', $picker->getButtonImageUrl());
	}

	public function testSetButtonImageUrl()
	{
		$picker = new TDatePicker();
		$picker->setButtonImageUrl('/images/calendar.png');
		$this->assertEquals('/images/calendar.png', $picker->getButtonImageUrl());
	}

	// ================================================================================
	// CalendarStyle
	// ================================================================================

	public function testCalendarStyleDefault()
	{
		$picker = new TDatePicker();
		$this->assertEquals('default', $picker->getCalendarStyle());
	}

	public function testSetCalendarStyle()
	{
		$picker = new TDatePicker();
		$picker->setCalendarStyle('custom');
		$this->assertEquals('custom', $picker->getCalendarStyle());
	}

	// ================================================================================
	// DropDownCssClass
	// ================================================================================

	public function testDropDownCssClassDefault()
	{
		$picker = new TDatePicker();
		$this->assertNull($picker->getDropDownCssClass());
	}

	public function testSetDropDownCssClass()
	{
		$picker = new TDatePicker();
		$picker->setDropDownCssClass('my-dropdown');
		$this->assertEquals('my-dropdown', $picker->getDropDownCssClass());
	}

	// ================================================================================
	// FirstDayOfWeek
	// ================================================================================

	public function testFirstDayOfWeekDefault()
	{
		$picker = new TDatePicker();
		$this->assertEquals(1, $picker->getFirstDayOfWeek());
	}

	public function testSetFirstDayOfWeekSunday()
	{
		$picker = new TDatePicker();
		$picker->setFirstDayOfWeek(0);
		$this->assertEquals(0, $picker->getFirstDayOfWeek());
	}

	public function testSetFirstDayOfWeekSaturday()
	{
		$picker = new TDatePicker();
		$picker->setFirstDayOfWeek(6);
		$this->assertEquals(6, $picker->getFirstDayOfWeek());
	}

	// ================================================================================
	// ButtonText
	// ================================================================================

	public function testButtonTextDefault()
	{
		$picker = new TDatePicker();
		$this->assertEquals('...', $picker->getButtonText());
	}

	public function testSetButtonText()
	{
		$picker = new TDatePicker();
		$picker->setButtonText('Pick');
		$this->assertEquals('Pick', $picker->getButtonText());
	}

	// ================================================================================
	// FromYear / UpToYear
	// ================================================================================

	public function testSetFromYear()
	{
		$picker = new TDatePicker();
		$picker->setFromYear(2000);
		$this->assertEquals(2000, $picker->getFromYear());
	}

	public function testSetUpToYear()
	{
		$picker = new TDatePicker();
		$picker->setUpToYear(2050);
		$this->assertEquals(2050, $picker->getUpToYear());
	}

	// ================================================================================
	// PositionMode
	// ================================================================================

	public function testPositionModeDefault()
	{
		$picker = new TDatePicker();
		$this->assertEquals(TDatePickerPositionMode::Bottom, $picker->getPositionMode());
	}

	public function testSetPositionModeTop()
	{
		$picker = new TDatePicker();
		$picker->setPositionMode(TDatePickerPositionMode::Top);
		$this->assertEquals(TDatePickerPositionMode::Top, $picker->getPositionMode());
	}

	public function testSetPositionModeInvalidThrows()
	{
		$picker = new TDatePicker();
		$this->expectException(\Prado\Exceptions\TInvalidDataValueException::class);
		$picker->setPositionMode('Invalid');
	}

	// ================================================================================
	// Date / getText alias
	// ================================================================================

	public function testDateDefaultEmpty()
	{
		$picker = new TDatePicker();
		$this->assertEquals('', $picker->getDate());
	}

	public function testSetDate()
	{
		$picker = new TDatePicker();
		$picker->setDate('25-12-2024');
		$this->assertEquals('25-12-2024', $picker->getDate());
	}

	// ================================================================================
	// TimeStamp
	// ================================================================================

	public function testTimeStampNullWhenEmpty()
	{
		$picker = new TDatePicker();
		$this->assertNull($picker->getTimeStamp());
	}

	public function testSetTimeStampNullClearsText()
	{
		$picker = new TDatePicker();
		$picker->setDate('25-12-2024');
		$picker->setTimeStamp(null);
		$this->assertEquals('', $picker->getText());
	}

	public function testSetTimeStampEmptyStringClearsText()
	{
		$picker = new TDatePicker();
		$picker->setDate('25-12-2024');
		$picker->setTimeStamp('');
		$this->assertEquals('', $picker->getText());
	}

	public function testSetTimeStampFormatsDate()
	{
		$picker = new TDatePicker();
		$picker->setDateFormat('dd-MM-yyyy');
		$ts = mktime(0, 0, 0, 12, 25, 2024);
		$picker->setTimeStamp($ts);
		$this->assertEquals('25-12-2024', $picker->getText());
	}

	public function testGetTimeStampRoundTrip()
	{
		$picker = new TDatePicker();
		$picker->setDateFormat('dd-MM-yyyy');
		$ts = mktime(0, 0, 0, 6, 15, 2023);
		$picker->setTimeStamp($ts);
		$retrieved = $picker->getTimeStamp();
		$this->assertEquals(date('Y-m-d', $ts), date('Y-m-d', $retrieved));
	}

	// ================================================================================
	// Data (IDataRenderer alias)
	// ================================================================================

	public function testGetDataEqualsGetTimeStamp()
	{
		$picker = new TDatePicker();
		$picker->setDateFormat('dd-MM-yyyy');
		$ts = mktime(0, 0, 0, 3, 1, 2020);
		$picker->setTimeStamp($ts);
		$this->assertEquals($picker->getTimeStamp(), $picker->getData());
	}

	public function testSetDataEqualsSetTimeStamp()
	{
		$picker = new TDatePicker();
		$picker->setDateFormat('dd-MM-yyyy');
		$ts = mktime(0, 0, 0, 3, 1, 2020);
		$picker->setData($ts);
		$this->assertEquals('01-03-2020', $picker->getText());
	}

	// ================================================================================
	// ValidationPropertyValue
	// ================================================================================

	public function testValidationPropertyValueEmptyText()
	{
		$picker = new TDatePicker();
		$this->assertEquals('', $picker->getValidationPropertyValue());
	}

	public function testValidationPropertyValueValidDate()
	{
		$picker = new TDatePicker();
		$picker->setDateFormat('dd-MM-yyyy');
		$picker->setDate('25-12-2024');
		$result = $picker->getValidationPropertyValue();
		$this->assertIsInt($result);
		$this->assertEquals('25-12-2024', date('d-m-Y', $result));
	}

	public function testValidationPropertyValueInvalidDateReturnsText()
	{
		$picker = new TDatePicker();
		$picker->setDateFormat('dd-MM-yyyy');
		$picker->setDate('not-a-date');
		$result = $picker->getValidationPropertyValue();
		$this->assertEquals('not-a-date', $result);
	}

	public function testTriggerAccessibleNameDefaultsToLocalizedChooseDate()
	{
		// Without a translation module, Prado::localize() returns the literal
		$picker = new TDatePicker();
		$this->assertSame('Choose date', PradoUnit::invoke($picker, 'getTriggerAccessibleName'));
	}

	public function testTriggerAccessibleNamePrefersToolTip()
	{
		$picker = new TDatePicker();
		$picker->setToolTip('Pick a delivery date');
		$this->assertSame('Pick a delivery date', PradoUnit::invoke($picker, 'getTriggerAccessibleName'));
	}

	public function testAccessibleTextOptionsOmitUntranslatedLabels()
	{
		// The client-side class carries the English defaults; the server sends a
		// label only when a translation changes it, which none does here
		$picker = new TDatePicker();
		$this->assertSame([], PradoUnit::invoke($picker, 'getAccessibleTextOptions'));
	}

	// ================================================================================
	// Rendering and scripts
	// ================================================================================

	private function createPicker(?TPanel $parent = null): array
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_inFormRender', true);
		$cs = $this->getMockBuilder(TClientScriptManager::class)->setConstructorArgs([$page])
			->onlyMethods(['getPradoScriptAssetUrl', 'registerPradoScript', 'registerEndScript', 'isEndScriptRegistered', 'isStyleSheetFileRegistered', 'registerStyleSheetFile', 'registerPostBackControl'])->getMock();
		$cs->method('getPradoScriptAssetUrl')->willReturn('/js');
		PradoUnit::setProp($page, '_clientScript', $cs);
		$picker = new TDatePicker();
		$picker->setID('d');
		$picker->setEnableClientScript(false);
		if ($parent !== null) {
			$page->getControls()->add($parent);
			$parent->getControls()->add($picker);
		} else {
			$page->getControls()->add($picker);
		}
		return [$picker, $cs];
	}

	private function renderPicker(TDatePicker $picker): string
	{
		$tw = new TTextWriter();
		$picker->render(new THtmlWriter($tw));
		return $tw->flush();
	}

	public function testClientSideIsCreatedOnce()
	{
		$picker = new TDatePicker();
		$client = $picker->getClientSide();
		$this->assertInstanceOf(TDatePickerClientScript::class, $client);
		$this->assertSame($client, $picker->getClientSide());
		$client->setOnDateChanged('changed();');
		$this->assertStringContainsString('changed();', (string) $client->getOnDateChanged());
	}

	public function testOnPreRenderRegistersTheStyleSheetAndScript()
	{
		[$picker, $cs] = $this->createPicker();
		$picker->setCalendarStyle('blue');
		$cs->method('isStyleSheetFileRegistered')->with('/js/datepicker/blue.css')->willReturn(false);
		$cs->expects($this->once())->method('registerStyleSheetFile')->with('/js/datepicker/blue.css', '/js/datepicker/blue.css');
		$cs->expects($this->once())->method('registerPradoScript')->with('datepicker');
		$picker->onPreRender(null);
	}

	public function testOnPreRenderWithoutTheCalendar()
	{
		[$picker, $cs] = $this->createPicker();
		$picker->setShowCalendar(false);
		$cs->method('isStyleSheetFileRegistered')->willReturn(true);
		$cs->expects($this->never())->method('registerStyleSheetFile');
		$cs->expects($this->never())->method('registerPradoScript');
		$picker->onPreRender(null);
	}

	public function testRenderTextBoxWithAButton()
	{
		[$picker] = $this->createPicker();
		$picker->setMode(TDatePickerMode::Button);
		$picker->setButtonText('Pick');
		$picker->setCssClass('date');
		$this->assertSame('<input name="d" type="text" id="d" class="date" />'
			. '<input id="dbutton" type="button" class="date TDatePickerButton" value="Pick" aria-label="Choose date" aria-haspopup="dialog" aria-expanded="false" />',
			$this->renderPicker($picker));
	}

	public function testRenderTextBoxWithAnImageButton()
	{
		[$picker] = $this->createPicker();
		$picker->setMode(TDatePickerMode::ImageButton);
		$this->assertStringEndsWith('<input id="dbutton" src="/js/datepicker/calendar.png" alt="Choose date" aria-haspopup="dialog" aria-expanded="false" class=" TDatePickerImageButton" type="image" onclick="return false;" />',
			$this->renderPicker($picker));
		$picker->setButtonImageUrl('/cal.png');
		$this->assertStringContainsString('src="/cal.png"', $this->renderPicker($picker));
	}

	public function testDisabledButtonsRenderDisabled()
	{
		$panel = new TPanel();
		$panel->setEnabled(false);
		[$picker] = $this->createPicker($panel);
		$picker->setMode(TDatePickerMode::Button);
		$this->assertMatchesRegularExpression('/<input id="[^"]*button" type="button"[^>]* disabled="disabled" \/>$/', $this->renderPicker($picker));
		$picker->setMode(TDatePickerMode::ImageButton);
		$this->assertMatchesRegularExpression('/TDatePickerImageButton" disabled="disabled" type="image"/', $this->renderPicker($picker));
	}

	public static function noButtonProvider(): array
	{
		return [
			'basic' => [TDatePickerMode::Basic, true],
			'clickable' => [TDatePickerMode::Clickable, true],
			'no calendar' => [TDatePickerMode::Button, false],
		];
	}

	/**
	 * @dataProvider noButtonProvider
	 */
	public function testRenderTextBoxWithoutAButton(string $mode, bool $showCalendar)
	{
		[$picker] = $this->createPicker();
		$picker->setMode($mode);
		$picker->setShowCalendar($showCalendar);
		$this->assertSame('<input name="d" type="text" id="d" />', $this->renderPicker($picker));
	}

	public function testRenderDropDownListsSelectTheDate()
	{
		[$picker, $cs] = $this->createPicker();
		$picker->setDateInputMode(TDatePickerInputMode::DropDownList);
		$picker->setDateFormat('dd-MM-yyyy');
		$picker->setFromYear(2025);
		$picker->setUpToYear(2027);
		$picker->setDropDownCssClass('dd');
		$picker->setText('09-03-2026');
		$cs->method('isEndScriptRegistered')->willReturn(false);
		$cs->expects($this->exactly(2))->method('registerEndScript');
		$output = $this->renderPicker($picker);
		$this->assertStringContainsString('<select id="d_day" name="d$day" class="datepicker_day_options dd" aria-label="Day">', $output);
		$this->assertStringContainsString('<option value="9" selected="selected">09</option>', $output);
		$this->assertStringContainsString('<select id="d_month" name="d$month" class="datepicker_month_options dd" aria-label="Month"><option value="0">01</option>', $output);
		$this->assertStringContainsString('<option value="2" selected="selected">03</option>', $output);
		$this->assertStringContainsString('<select id="d_year" name="d$year" class="datepicker_year_options dd" aria-label="Year"><option value="2025">2025</option><option value="2026" selected="selected">2026</option><option value="2027">2027</option></select>', $output);
		$this->assertStringContainsString('<input name="d" type="hidden" value="09-03-2026" /></span>', $output);
		$this->assertSame(TDatePickerMode::ImageButton, $picker->getMode());
		$this->assertStringEndsWith('TDatePickerImageButton" type="image" onclick="return false;" />', $output);
	}

	public function testRenderDropDownListsWithoutADayPattern()
	{
		[$picker, $cs] = $this->createPicker();
		$picker->setDateInputMode(TDatePickerInputMode::DropDownList);
		$picker->setDateFormat('MMMM yyyy');
		$picker->setText('March 2026');
		$output = $this->renderPicker($picker);
		$this->assertStringNotContainsString('d_day', $output);
		$this->assertStringContainsString('<option value="' . (date('n') - 1) . '" selected="selected">' . date('F') . '</option>', $output);
		$picker->setText('03 2026');
		$this->assertStringContainsString('<option value="2" selected="selected">March</option>', $this->renderPicker($picker));
	}

	public function testReadOnlyDropDownListsAreDisabled()
	{
		[$picker] = $this->createPicker();
		$picker->setDateInputMode(TDatePickerInputMode::DropDownList);
		$picker->setDateFormat('d/M/yyyy');
		$picker->setText('9/3/2026');
		$picker->setReadOnly(true);
		$picker->setShowCalendar(false);
		$output = $this->renderPicker($picker);
		$this->assertSame(3, preg_match_all('/<select [^>]*disabled="disabled"/', $output));
		$this->assertStringContainsString('<option value="9" selected="selected">9</option>', $output);
		$this->assertStringContainsString('<option value="2" selected="selected">3</option>', $output);
	}

	public function testDisabledDropDownListsAreDisabled()
	{
		$panel = new TPanel();
		$panel->setEnabled(false);
		[$picker] = $this->createPicker($panel);
		$picker->setDateInputMode(TDatePickerInputMode::DropDownList);
		$picker->setText('09-03-2026');
		$picker->setShowCalendar(false);
		$this->assertSame(3, preg_match_all('/<select [^>]*disabled="disabled"/', $this->renderPicker($picker)));
	}

	public function testAbbreviatedMonthNames()
	{
		[$picker] = $this->createPicker();
		$picker->setDateFormat('dd MMM yyyy');
		$info = PradoUnit::invoke($picker, 'getLocalizedCalendarInfo');
		$this->assertSame('Mar', PradoUnit::invoke($picker, 'getLocalizedMonthNames', $info)[2]);
	}

	public function testClientScriptRegistersTheSpacerOnce()
	{
		[$picker, $cs] = $this->createPicker();
		$cs->method('isEndScriptRegistered')->with('TDatePicker.spacer')->willReturn(true);
		$cs->expects($this->once())->method('registerEndScript')->with('prado:d', $this->stringStartsWith('new Prado.WebUI.TDatePicker('));
		PradoUnit::invoke($picker, 'renderClientControlScript', new THtmlWriter(new TTextWriter()));
	}

	public function testClientScriptWithoutTheCalendar()
	{
		[$picker, $cs] = $this->createPicker();
		$picker->setShowCalendar(false);
		$cs->expects($this->never())->method('registerEndScript');
		PradoUnit::invoke($picker, 'renderClientControlScript', new THtmlWriter(new TTextWriter()));
	}

	public static function triggerProvider(): array
	{
		return [
			'basic' => [TDatePickerMode::Basic, null, null],
			'clickable' => [TDatePickerMode::Clickable, null, 'click'],
			'button' => [TDatePickerMode::Button, 'dbutton', null],
		];
	}

	/**
	 * @dataProvider triggerProvider
	 */
	public function testDatePickerOptions(string $mode, ?string $trigger, ?string $event)
	{
		[$picker] = $this->createPicker();
		$picker->setMode($mode);
		$picker->setFromYear(2020);
		$picker->setUpToYear(2030);
		$options = PradoUnit::invoke($picker, 'getDatePickerOptions');
		$this->assertSame('d', $options['ID']);
		$this->assertSame(TDatePickerInputMode::TextBox, $options['InputMode']);
		$this->assertSame('dd-MM-yyyy', $options['Format']);
		$this->assertSame(2020, $options['FromYear']);
		$this->assertSame(2030, $options['UpToYear']);
		$this->assertSame($trigger, $options['Trigger'] ?? null);
		$this->assertSame($event, $options['TriggerEvent'] ?? null);
		$this->assertArrayNotHasKey('ClassName', $options);
		$this->assertArrayNotHasKey('MonthNames', $options);
	}

	public function testDatePickerOptionsAddTheClassAndClientSideOptions()
	{
		[$picker] = $this->createPicker();
		$picker->setCssClass('date');
		$picker->getClientSide()->setOnDateChanged('changed();');
		$options = PradoUnit::invoke($picker, 'getDatePickerOptions');
		$this->assertSame('date', $options['ClassName']);
		$this->assertArrayHasKey('OnDateChanged', $options);
	}

	public function testCulturalOptionsForAnotherCulture()
	{
		[$picker] = $this->createPicker();
		$picker->setCulture('fr');
		$options = PradoUnit::invoke($picker, 'getCulturalOptions');
		$this->assertSame('mars', $options['MonthNames'][2]);
		$this->assertArrayHasKey('AbbreviatedMonthNames', $options);
		$this->assertArrayHasKey('ShortWeekDayNames', $options);
	}

	// ================================================================================
	// Post data
	// ================================================================================

	public function testLoadPostDataInTextBoxMode()
	{
		$picker = new TDatePicker();
		$this->assertTrue($picker->loadPostData('d', ['d' => '09-03-2026']));
		$this->assertSame('09-03-2026', $picker->getText());
	}

	public function testLoadPostDataFromTheDropDownLists()
	{
		$picker = new TDatePicker();
		$picker->setDateInputMode(TDatePickerInputMode::DropDownList);
		$values = ['d$day' => '9', 'd$month' => '2', 'd$year' => '2026'];
		$this->assertTrue($picker->loadPostData('d', $values));
		$this->assertSame('09-03-2026', $picker->getText());
		$this->assertFalse($picker->loadPostData('d', $values));
	}

	public function testLoadPostDataIgnoresAReadOnlyPicker()
	{
		$picker = new TDatePicker();
		$picker->setDateInputMode(TDatePickerInputMode::DropDownList);
		$picker->setReadOnly(true);
		$this->assertFalse($picker->loadPostData('d', ['d$day' => '9', 'd$month' => '2', 'd$year' => '2026']));
		$this->assertSame('', $picker->getText());
	}

	public function testLoadPostDataWithoutADayPatternUsesTheFirstDay()
	{
		$picker = new TDatePicker();
		$picker->setDateInputMode(TDatePickerInputMode::DropDownList);
		$picker->setDateFormat('MMMM yyyy');
		$picker->loadPostData('d', ['d$month' => '2', 'd$year' => '2026']);
		$this->assertSame('03 2026', $picker->getText());
	}

	public function testLoadPostDataWithoutFieldsUsesToday()
	{
		$picker = new TDatePicker();
		$picker->setDateInputMode(TDatePickerInputMode::DropDownList);
		$picker->setDateFormat('yyyy-MM-dd');
		$picker->loadPostData('d', []);
		$this->assertSame(date('Y-m-d'), $picker->getText());
	}

	// ================================================================================
	// Paths
	// ================================================================================

	public static function postedFieldsProvider(): array
	{
		// format, posted fields => expected text with today's parts as Y, m, d
		$rows = [];
		foreach (['day' => '9', 'today' => null, 'first' => null] as $dayCase => $day) {
			foreach ([true, false] as $month) {
				foreach ([true, false] as $year) {
					$rows["$dayCase month:" . ($month ? 'y' : 'n') . ' year:' . ($year ? 'y' : 'n')] = [$dayCase, $month, $year];
				}
			}
		}
		return $rows;
	}

	/**
	 * @dataProvider postedFieldsProvider
	 */
	public function testDateFromPostDataFillsMissingFields(string $dayCase, bool $month, bool $year)
	{
		$picker = new TDatePicker();
		$picker->setDateFormat($dayCase === 'first' ? 'MM yyyy' : 'yyyy-MM-dd');
		$values = [];
		if ($dayCase === 'day') {
			$values['d$day'] = '9';
		}
		if ($month) {
			$values['d$month'] = '0';
		}
		if ($year) {
			$values['d$year'] = '2026';
		}
		$text = PradoUnit::invoke($picker, 'getDateFromPostData', 'd', $values);
		$y = $year ? '2026' : date('Y');
		$m = $month ? '01' : date('m');
		if ($dayCase === 'first') {
			$this->assertSame("$m $y", $text);
		} else {
			$d = $dayCase === 'day' ? '09' : date('d');
			$this->assertSame((new \DateTime())->setDate((int) $y, (int) $m, (int) $d)->format('Y-m-d'), $text);
		}
	}

	public static function dropDownCalendarProvider(): array
	{
		// mode, cssClass, text parses
		return [
			'basic class parses' => [TDatePickerMode::Basic, 'cal', true],
			'basic class unparsable' => [TDatePickerMode::Basic, 'cal', false],
			'button class parses' => [TDatePickerMode::Button, 'cal', true],
			'button class unparsable' => [TDatePickerMode::Button, 'cal', false],
			'button no class unparsable' => [TDatePickerMode::Button, '', false],
		];
	}

	/**
	 * @dataProvider dropDownCalendarProvider
	 */
	public function testDropDownCalendarWrapper(string $mode, string $cssClass, bool $parses)
	{
		[$picker] = $this->createPicker();
		$picker->setDateInputMode(TDatePickerInputMode::DropDownList);
		$picker->setMode($mode);
		$picker->setCssClass($cssClass);
		$picker->setShowCalendar(false);
		$picker->setText($parses ? '09-03-2026' : 'soon');
		$tw = new TTextWriter();
		PradoUnit::invoke($picker, 'renderDropDownListCalendar', new THtmlWriter($tw));
		$output = $tw->flush();
		$this->assertSame($cssClass !== '', str_contains(substr($output, 0, strpos($output, '>')), 'class="cal"'));
		$this->assertStringContainsString($parses ? '<option value="2026" selected="selected">' : '<option value="' . date('Y') . '" selected="selected">', $output);
		$this->assertSame($mode === TDatePickerMode::Basic ? TDatePickerMode::ImageButton : $mode, $picker->getMode());
	}

	public static function calendarSelectionProvider(): array
	{
		return [
			'day only' => ['dd', ['d_day']],
			'month only' => ['MM', ['d_month']],
			'year only' => ['yyyy', ['d_year']],
			'none' => ['HH', []],
		];
	}

	/**
	 * @dataProvider calendarSelectionProvider
	 */
	public function testCalendarSelectionsFollowTheFormat(string $format, array $ids)
	{
		[$picker] = $this->createPicker();
		$picker->setDateFormat($format);
		$tw = new TTextWriter();
		PradoUnit::invoke($picker, 'renderCalendarSelections', new THtmlWriter($tw), new \DateTime('2026-03-09'));
		preg_match_all('/<select id="([^"]+)"/', $tw->flush(), $matches);
		$this->assertSame($ids, $matches[1]);
	}

	public static function dropDownOptionsProvider(): array
	{
		return [
			'selected' => [[3 => 'c'], 3, '<option value="3" selected="selected">c</option>'],
			'not selected' => [[3 => 'c'], 4, '<option value="3">c</option>'],
			'empty' => [[], 4, ''],
		];
	}

	/**
	 * @dataProvider dropDownOptionsProvider
	 */
	public function testDropDownListOptions(array $options, int $selected, string $expected)
	{
		$tw = new TTextWriter();
		PradoUnit::invoke(new TDatePicker(), 'renderDropDownListOptions', new THtmlWriter($tw), $options, $selected);
		$this->assertSame($expected, $tw->flush());
	}

	public static function emptyYearRangeProvider(): array
	{
		// readOnly, parentDisabled => disabled
		return [
			'enabled' => [false, false, false],
			'read only' => [true, false, true],
			'disabled' => [false, true, true],
		];
	}

	/**
	 * @dataProvider emptyYearRangeProvider
	 */
	public function testYearOptionsWithAnEmptyRange(bool $readOnly, bool $parentDisabled, bool $disabled)
	{
		$panel = new TPanel();
		$panel->setEnabled(!$parentDisabled);
		[$picker] = $this->createPicker($panel);
		$picker->setFromYear(2030);
		$picker->setUpToYear(2020);
		$picker->setReadOnly($readOnly);
		$tw = new TTextWriter();
		PradoUnit::invoke($picker, 'renderCalendarYearOptions', new THtmlWriter($tw), 2026);
		$output = $tw->flush();
		$this->assertStringNotContainsString('<option', $output);
		$this->assertSame($disabled, str_contains($output, 'disabled="disabled"'));
	}

	public function testSetTimeStampFromANumericString()
	{
		$picker = new TDatePicker();
		$picker->setDateFormat('yyyy-MM-dd');
		$picker->setTimeStamp((string) mktime(0, 0, 0, 3, 9, 2026));
		$this->assertSame('2026-03-09', $picker->getText());
	}

	public function testDisabledImageButtonWithACustomImage()
	{
		$panel = new TPanel();
		$panel->setEnabled(false);
		[$picker] = $this->createPicker($panel);
		$picker->setButtonImageUrl('/cal.png');
		$tw = new TTextWriter();
		PradoUnit::invoke($picker, 'renderImageButtonDatePicker', new THtmlWriter($tw));
		$output = $tw->flush();
		$this->assertStringContainsString('src="/cal.png"', $output);
		$this->assertStringContainsString('disabled="disabled"', $output);
	}

	public function testCurrentCultureWithoutGlobalizationIsEnglish()
	{
		$app = \Prado\Prado::getApplication();
		$saved = PradoUnit::getProp($app, '_globalization');
		try {
			PradoUnit::setProp($app, '_globalization', null);
			$this->assertSame('en', PradoUnit::invoke(new TDatePicker(), 'getCurrentCulture'));
		} finally {
			PradoUnit::setProp($app, '_globalization', $saved);
		}
	}

	public function testCurrentCultureFollowsTheGlobalization()
	{
		$app = \Prado\Prado::getApplication();
		$saved = PradoUnit::getProp($app, '_globalization');
		try {
			$globalization = $this->getMockBuilder(\Prado\I18N\TGlobalization::class)->onlyMethods(['getCulture'])->getMock();
			$globalization->method('getCulture')->willReturn('de');
			PradoUnit::setProp($app, '_globalization', $globalization);
			$this->assertSame('de', PradoUnit::invoke(new TDatePicker(), 'getCurrentCulture'));
		} finally {
			PradoUnit::setProp($app, '_globalization', $saved);
		}
	}

	public function testAccessibleTextOptionsCarryTranslatedLabels()
	{
		$app = \Prado\Prado::getApplication();
		$savedGlobalization = PradoUnit::getProp($app, '_globalization');
		$formatters = PradoUnit::getStaticProp(\Prado\I18N\Translation::class, 'formatters');
		$formatter = new class () extends \Prado\I18N\core\MessageFormat {
			public function __construct()
			{
			}

			public function format($string, $args = [], $catalogue = null, $charset = null)
			{
				return $string === 'Calendar' ? 'Kalender' : $string;
			}
		};
		PradoUnit::setStaticProp(\Prado\I18N\Translation::class, 'formatters', ['tdatepicker_test' => $formatter] + $formatters);
		try {
			$app->setGlobalization(new class () extends \Prado\I18N\TGlobalization {
				public function getTranslationConfiguration()
				{
					return ['catalogue' => 'tdatepicker_test'];
				}

				public function getCharset()
				{
					return 'UTF-8';
				}
			});
			$this->assertSame(['CalendarLabel' => 'Kalender'], PradoUnit::invoke(new TDatePicker(), 'getAccessibleTextOptions'));
		} finally {
			PradoUnit::setStaticProp(\Prado\I18N\Translation::class, 'formatters', $formatters);
			PradoUnit::setProp($app, '_globalization', $savedGlobalization);
		}
	}
}
