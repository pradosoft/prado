<?php

namespace Prado\Test\Unit\Web\UI\JuiControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TNotSupportedException;
use Prado\Prado;
use Prado\Test\Unit\Harness\Traits\TPageLifecycleTrait;
use Prado\Test\Unit\Harness\Web\UI\TTestLifecyclePage;
use Prado\Test\Unit\PradoUnit;
use Prado\Util\TSimpleDateFormatter;
use Prado\Web\UI\JuiControls\IJuiOptions;
use Prado\Web\UI\JuiControls\TJuiControlAdapter;
use Prado\Web\UI\JuiControls\TJuiControlOptions;
use Prado\Web\UI\JuiControls\TJuiDatePicker;
use Prado\Web\UI\TForm;
use Prado\Web\UI\WebControls\THead;
use Prado\Web\UI\WebControls\TTextBoxMode;

/**
 * Unit tests for {@see TJuiDatePicker}.
 */
class TJuiDatePickerTest extends TestCase
{
	use TPageLifecycleTrait;

	/** @var array the static state of {@see TJuiDatePicker} before the test */
	private array $_static = [];

	protected function setUp(): void
	{
		$this->resetPageState();
		$this->_static = PradoUnit::snapshotStatic(TJuiDatePicker::class);
		PradoUnit::setStaticProp(TJuiDatePicker::class, '_first', true);
	}

	protected function tearDown(): void
	{
		PradoUnit::restoreStatic(TJuiDatePicker::class, $this->_static);
		$this->resetPageState();
	}

	private function newControl(): TJuiDatePicker
	{
		$control = new TJuiDatePicker();
		$control->setID('Date');
		return $control;
	}

	/**
	 * @param string[] $cultures the Culture of each date picker, `A`, `B`, ... in the form
	 * @return TTestLifecyclePage the page
	 */
	private function newPage(array $cultures): TTestLifecyclePage
	{
		$page = new TTestLifecyclePage();
		$page->build = function (TTestLifecyclePage $page, TForm $form) use ($cultures) {
			$page->getControls()->insertAt(0, new THead());
			foreach ($cultures as $i => $culture) {
				$control = new TJuiDatePicker();
				$control->setID(chr(ord('A') + $i));
				$control->setCulture($culture);
				$form->getControls()->add($control);
			}
		};
		return $page;
	}

	public function testWidgetAndAdapter(): void
	{
		$control = $this->newControl();
		$this->assertInstanceOf(IJuiOptions::class, $control);
		$this->assertInstanceOf(TJuiControlAdapter::class, $control->getAdapter());
		$this->assertSame('datepicker', $control->getWidget());
		$this->assertSame('Date', $control->getWidgetID());
		$this->assertContains('dateFormat', $control->getValidOptions());
		$this->assertContains('onSelect', $control->getValidOptions());
		$this->assertSame([], $control->getValidEvents());
	}

	public function testOptionsAreKeptInViewState(): void
	{
		$control = $this->newControl();
		$options = $control->getOptions();
		$this->assertInstanceOf(TJuiControlOptions::class, $options);
		$this->assertSame($options, $control->getOptions());
		$this->assertSame($options, $control->getViewState('JuiOptions'));
	}

	public function testTextModeIsSingleLine(): void
	{
		$control = $this->newControl();
		$this->assertSame(TTextBoxMode::SingleLine, $control->getTextMode());
		$this->expectException(TNotSupportedException::class);
		$control->setTextMode(TTextBoxMode::MultiLine);
	}

	public function testCulture(): void
	{
		$control = $this->newControl();
		$this->assertSame('', $control->getCulture());
		$control->setCulture('de');
		$this->assertSame('de', $control->getCulture());
		$this->assertSame('de', PradoUnit::invoke($control, 'getCurrentCulture'));
	}

	public function testCurrentCultureWithoutCultureIsTheApplicationCulture(): void
	{
		$app = Prado::getApplication();
		$saved = PradoUnit::getProp($app, '_globalization');
		try {
			PradoUnit::setProp($app, '_globalization', null);
			$this->assertSame('en', PradoUnit::invoke($this->newControl(), 'getCurrentCulture'));
			$globalization = new \Prado\I18N\TGlobalization();
			$globalization->setCulture('fr');
			PradoUnit::setProp($app, '_globalization', $globalization);
			$this->assertSame('fr', PradoUnit::invoke($this->newControl(), 'getCurrentCulture'));
		} finally {
			PradoUnit::setProp($app, '_globalization', $saved);
		}
	}

	public function testDateFormat(): void
	{
		$control = $this->newControl();
		$this->assertSame('dd-MM-yyyy', $control->getDateFormat());
		$control->setDateFormat('yyyy/MM/dd');
		$this->assertSame('yyyy/MM/dd', $control->getDateFormat());
	}

	public function testDateIsTheText(): void
	{
		$control = $this->newControl();
		$control->setDate('02-03-2024');
		$this->assertSame('02-03-2024', $control->getText());
		$this->assertSame('02-03-2024', $control->getDate());
	}

	public function testTimeStampAndDataRoundTrip(): void
	{
		$control = $this->newControl();
		$stamp = (new TSimpleDateFormatter('dd-MM-yyyy'))->parse('02-03-2024');
		$control->setTimeStamp($stamp);
		$this->assertSame('02-03-2024', $control->getText());
		$this->assertSame($stamp, $control->getTimeStamp());
		$control->setData((string) $stamp);
		$this->assertSame($stamp, $control->getData());
	}

	public function testEmptyTimeStampClearsTheText(): void
	{
		$control = $this->newControl();
		foreach ([null, ' '] as $empty) {
			$control->setText('02-03-2024');
			$control->setTimeStamp($empty);
			$this->assertSame('', $control->getText());
			$this->assertNull($control->getTimeStamp());
		}
		$control->setText('  ');
		$this->assertNull($control->getTimeStamp());
	}

	/**
	 * A long month name in the format is parsed as the month number.
	 */
	public function testTimeStampFromTextReadsALongMonthAsANumber(): void
	{
		$control = $this->newControl();
		$control->setDateFormat('dd-MMMM-yyyy');
		$control->setText('02-03-2024');
		$this->assertSame((new TSimpleDateFormatter('dd-MM-yyyy'))->parse('02-03-2024'), $control->getTimeStamp());
	}

	public function testValidationPropertyValue(): void
	{
		$control = $this->newControl();
		$this->assertSame('', $control->getValidationPropertyValue());
		$control->setText('02-03-2024');
		$this->assertSame((new TSimpleDateFormatter('dd-MM-yyyy'))->parse('02-03-2024'), $control->getValidationPropertyValue());
		$control->setText('not a date');
		$this->assertSame('not a date', $control->getValidationPropertyValue());
	}

	/**
	 * A date that parses to the timestamp 0 is a valid date.
	 */
	public function testValidationPropertyValueOfTheTimestampZero(): void
	{
		$zone = date_default_timezone_get();
		date_default_timezone_set('UTC');
		try {
			$control = $this->newControl();
			$control->setText('01-01-1970');
			$this->assertSame(0, $control->getValidationPropertyValue());
		} finally {
			date_default_timezone_set($zone);
		}
	}

	/**
	 * The first date picker of a page sets the regional defaults of the date pickers; a
	 * culture other than `en` loads its translation.
	 */
	public function testRenderSetsTheRegionalDefaultsOnce(): void
	{
		$output = $this->runPage($this->newPage(['de', 'fr']));
		$this->assertStringContainsString('/ui/i18n/datepicker-de.js', $output);
		$this->assertStringNotContainsString('datepicker-fr.js', $output);
		$this->assertSame(1, substr_count($output, 'jQuery.datepicker.setDefaults('));
		$this->assertStringContainsString("jQuery(document).ready(function(){jQuery.datepicker.setDefaults(jQuery.datepicker.regional['de']);});", $output);
		$this->assertStringContainsString("jQuery('#A').datepicker([]);", $output);
		$this->assertStringContainsString("jQuery('#B').datepicker([]);", $output);
	}

	public function testRenderInEnglishLoadsNoTranslation(): void
	{
		$output = $this->runPage($this->newPage(['en']));
		$this->assertStringNotContainsString('/ui/i18n/', $output);
		$this->assertStringContainsString("jQuery.datepicker.regional['en']", $output);
	}

	public function testRenderPassesTheOptions(): void
	{
		$page = $this->newPage(['en']);
		$page->load = fn ($page) => $page->findControl('A')->getOptions()->dateFormat = 'dd-mm-yy';
		$output = $this->runPage($page);
		$this->assertStringContainsString("jQuery('#A').datepicker({'dateFormat':\"dd-mm-yy\"});", $output);
	}
}
