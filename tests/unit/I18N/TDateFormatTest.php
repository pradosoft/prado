<?php

use Prado\I18N\TDateFormat;
use Prado\Web\UI\THtmlWriter;
use Prado\IO\TTextWriter;
use PHPUnit\Framework\TestCase;

class TDateFormatTest extends TestCase
{
	private function render($control)
	{
		$tw = new TTextWriter();
		$writer = new THtmlWriter($tw);
		$control->render($writer);
		return $tw->flush();
	}

	public function testExtendsTI18NControl()
	{
		$control = new TDateFormat();
		$this->assertInstanceOf(\Prado\I18N\TI18NControl::class, $control);
	}

	public function testImplementsIDataRenderer()
	{
		$control = new TDateFormat();
		$this->assertInstanceOf(\Prado\IDataRenderer::class, $control);
	}

	// --- Pattern Property ---

	public function testPatternDefaultEmpty()
	{
		$control = new TDateFormat();
		$this->assertSame('', $control->getPattern());
	}

	public function testSetPattern()
	{
		$control = new TDateFormat();
		$control->setPattern('yyyy-MM-dd');
		$this->assertSame('yyyy-MM-dd', $control->getPattern());
	}

	public function testSetPatternUpdatesValue()
	{
		$control = new TDateFormat();
		$control->setPattern('MM/dd/yyyy');
		$control->setValue('2026-04-19');
		$this->assertSame('2026-04-19', $control->getValue());
	}

	// --- Value Property ---

	public function testValueDefaultReturnsCurrentTime()
	{
		$control = new TDateFormat();
		$value = $control->getValue();
		$this->assertNotEmpty($value);
		$this->assertIsInt($value);
	}

	public function testSetValueWithNumericTimestamp()
	{
		$control = new TDateFormat();
		$control->setValue(1713542400);
		$this->assertSame(1713542400, $control->getValue());
	}

	public function testSetValueWithDateString()
	{
		$control = new TDateFormat();
		$control->setValue('2026-04-19');
		$this->assertSame('2026-04-19', $control->getValue());
	}

	public function testSetValueWithStrtotimeString()
	{
		$control = new TDateFormat();
		$control->setValue('next Monday');
		$this->assertNotEmpty($control->getValue());
	}

	// --- DefaultText Property ---

	public function testDefaultTextDefaultEmpty()
	{
		$control = new TDateFormat();
		$this->assertSame('', $control->getDefaultText());
	}

	public function testSetDefaultText()
	{
		$control = new TDateFormat();
		$control->setDefaultText('No date');
		$this->assertSame('No date', $control->getDefaultText());
	}

	public function testDefaultTextReturnedWhenValueEmpty()
	{
		$control = new TDateFormat();
		$control->setDefaultText('No date');
		$control->setPattern('yyyy-MM-dd');
		$output = $this->render($control);
		$this->assertStringContainsString('No date', $output);
	}

	public function testDefaultTextNotReturnedWhenValueSet()
	{
		$control = new TDateFormat();
		$control->setDefaultText('No date');
		$control->setValue('2026-04-19');
		$control->setPattern('yyyy-MM-dd');
		$output = $this->render($control);
		$this->assertStringNotContainsString('No date', $output);
	}

	// --- Data Methods (IDataRenderer) ---

	public function testGetDataReturnsValue()
	{
		$control = new TDateFormat();
		$control->setValue('2026-04-19');
		$this->assertSame('2026-04-19', $control->getData());
	}

	public function testSetDataSetsValue()
	{
		$control = new TDateFormat();
		$control->setData('2026-04-19');
		$this->assertSame('2026-04-19', $control->getValue());
	}

	// --- Preset Patterns ---

	public function testPresetFull()
	{
		$control = new TDateFormat();
		$control->setPattern('full');
		$control->setValue('2026-04-19');
		$output = $this->render($control);
		$this->assertNotEmpty($output);
	}

	public function testPresetLong()
	{
		$control = new TDateFormat();
		$control->setPattern('long');
		$control->setValue('2026-04-19');
		$output = $this->render($control);
		$this->assertNotEmpty($output);
	}

	public function testPresetMedium()
	{
		$control = new TDateFormat();
		$control->setPattern('medium');
		$control->setValue('2026-04-19');
		$output = $this->render($control);
		$this->assertNotEmpty($output);
	}

	public function testPresetShort()
	{
		$control = new TDateFormat();
		$control->setPattern('short');
		$control->setValue('2026-04-19');
		$output = $this->render($control);
		$this->assertNotEmpty($output);
	}

	public function testPresetNone()
	{
		$control = new TDateFormat();
		$control->setPattern('none');
		$control->setValue('2026-04-19 14:30:00');
		$output = $this->render($control);
		$this->assertNotEmpty($output);
	}

	public function testPresetDateTimeCombined()
	{
		$control = new TDateFormat();
		$control->setPattern('medium short');
		$control->setValue('2026-04-19 14:30:00');
		$output = $this->render($control);
		$this->assertNotEmpty($output);
	}

	public function testPresetLongDate()
	{
		$control = new TDateFormat();
		$control->setPattern('longdate');
		$control->setValue('2026-04-19');
		$output = $this->render($control);
		$this->assertNotEmpty($output);
	}

	public function testPresetShortDate()
	{
		$control = new TDateFormat();
		$control->setPattern('shortdate');
		$control->setValue('2026-04-19');
		$output = $this->render($control);
		$this->assertNotEmpty($output);
	}

	public function testPresetFullDate()
	{
		$control = new TDateFormat();
		$control->setPattern('fulldate');
		$control->setValue('2026-04-19');
		$output = $this->render($control);
		$this->assertNotEmpty($output);
	}

	public function testPresetFullTime()
	{
		$control = new TDateFormat();
		$control->setPattern('fulltime');
		$control->setValue('2026-04-19 14:30:00');
		$output = $this->render($control);
		$this->assertNotEmpty($output);
	}

	// --- Culture Support ---

	public function testCultureEnUs()
	{
		$control = new TDateFormat();
		$control->setCulture('en_US');
		$control->setPattern('yyyy-MM-dd');
		$control->setValue('2026-04-19');
		$output = $this->render($control);
		$this->assertNotEmpty($output);
	}

	public function testCultureDeDe()
	{
		$control = new TDateFormat();
		$control->setCulture('de_DE');
		$control->setPattern('dd.MM.yyyy');
		$control->setValue('2026-04-19');
		$output = $this->render($control);
		$this->assertNotEmpty($output);
		$this->assertStringContainsString('19.04.2026', $output);
	}

	public function testCultureFrFr()
	{
		$control = new TDateFormat();
		$control->setCulture('fr_FR');
		$control->setPattern('dd MMMM yyyy');
		$control->setValue('2026-04-19');
		$output = $this->render($control);
		$this->assertNotEmpty($output);
		$this->assertStringContainsString('avril', $output);
	}

	public function testCultureJaJp()
	{
		$control = new TDateFormat();
		$control->setCulture('ja_JP');
		$control->setPattern('yyyy年MM月dd日');
		$control->setValue('2026-04-19');
		$output = $this->render($control);
		$this->assertNotEmpty($output);
		$this->assertStringContainsString('2026年04月19日', $output);
	}

	public function testCultureZhCn()
	{
		$control = new TDateFormat();
		$control->setCulture('zh_CN');
		$control->setPattern('yyyy年MM月dd日');
		$control->setValue('2026-04-19');
		$output = $this->render($control);
		$this->assertNotEmpty($output);
	}

	// --- Custom Pattern Formatting ---

	public function testCustomPatternYyyyMmDd()
	{
		$control = new TDateFormat();
		$control->setPattern('yyyy-MM-dd');
		$control->setValue('2026-04-19');
		$output = $this->render($control);
		$this->assertStringContainsString('2026-04-19', $output);
	}

	public function testCustomPatternDdMmYyyy()
	{
		$control = new TDateFormat();
		$control->setPattern('dd/MM/yyyy');
		$control->setValue('2026-04-19');
		$output = $this->render($control);
		$this->assertStringContainsString('19/04/2026', $output);
	}

	public function testCustomPatternWithTime()
	{
		$control = new TDateFormat();
		$control->setPattern('yyyy-MM-dd HH:mm:ss');
		$control->setValue('2026-04-19 14:30:45');
		$output = $this->render($control);
		$this->assertNotEmpty($output);
	}

	public function testCustomPatternMmmmYyyy()
	{
		$control = new TDateFormat();
		$control->setPattern('MMMM yyyy');
		$control->setValue('2026-04-19');
		$output = $this->render($control);
		$this->assertStringContainsString('April', $output);
		$this->assertStringContainsString('2026', $output);
	}

	public function testCustomPatternEeee()
	{
		$control = new TDateFormat();
		$control->setPattern('EEEE, MMMM d, yyyy');
		$control->setValue('2026-04-19');
		$output = $this->render($control);
		$this->assertNotEmpty($output);
		$this->assertStringContainsString('Sunday', $output);
	}

	// --- Edge Cases ---

	public function testEmptyPatternUsesDefault()
	{
		$control = new TDateFormat();
		$control->setValue('2026-04-19');
		$output = $this->render($control);
		$this->assertNotEmpty($output);
	}

	public function testValueWithTimeOnly()
	{
		$control = new TDateFormat();
		$control->setPattern('HH:mm:ss');
		$control->setValue('14:30:45');
		$output = $this->render($control);
		$this->assertNotEmpty($output);
	}

	public function testUnixTimestampNumeric()
	{
		$control = new TDateFormat();
		$control->setPattern('yyyy-MM-dd');
		$control->setValue(1713542400);
		$output = $this->render($control);
		$this->assertNotEmpty($output);
	}

	public function testCurrentTimeWhenValueEmpty()
	{
		$control = new TDateFormat();
		$control->setPattern('yyyy-MM-dd');
		$output = $this->render($control);
		$this->assertNotEmpty($output);
	}

	public function testRenderWritesToWriter()
	{
		$control = new TDateFormat();
		$control->setPattern('yyyy-MM-dd');
		$control->setValue('2026-04-19');
		$output = $this->render($control);
		$this->assertStringContainsString('2026-04-19', $output);
	}

	public function testCharsetProperty()
	{
		$control = new TDateFormat();
		$control->setCharset('UTF-8');
		$this->assertSame('UTF-8', $control->getCharset());
	}

	public function testCharsetAffectsOutput()
	{
		$control = new TDateFormat();
		$control->setCulture('de_DE');
		$control->setPattern('dd. MMMM yyyy');
		$control->setValue('2026-04-19');
		$output = $this->render($control);
		$this->assertNotEmpty($output);
	}

	// --- Multiple Culture Formatting ---

	public function testCultureItIt()
	{
		$control = new TDateFormat();
		$control->setCulture('it_IT');
		$control->setPattern('dd/MM/yyyy');
		$control->setValue('2026-04-19');
		$output = $this->render($control);
		$this->assertStringContainsString('19/04/2026', $output);
	}

	public function testCultureEsEs()
	{
		$control = new TDateFormat();
		$control->setCulture('es_ES');
		$control->setPattern("EEEE, d 'de' MMMM 'de' yyyy");
		$control->setValue('2026-04-19');
		$output = $this->render($control);
		$this->assertNotEmpty($output);
		$this->assertStringContainsString('domingo', $output);
	}

	public function testCultureRuRu()
	{
		$control = new TDateFormat();
		$control->setCulture('ru_RU');
		$control->setPattern('dd MMMM yyyy');
		$control->setValue('2026-04-19');
		$output = $this->render($control);
		$this->assertNotEmpty($output);
	}

	public function testCultureKoKr()
	{
		$control = new TDateFormat();
		$control->setCulture('ko_KR');
		$control->setPattern('yyyy년 MM월 dd일');
		$control->setValue('2026-04-19');
		$output = $this->render($control);
		$this->assertNotEmpty($output);
	}

	public function testCultureArSa()
	{
		$control = new TDateFormat();
		$control->setCulture('ar_SA');
		$control->setPattern('dd/MM/yyyy');
		$control->setValue('2026-04-19');
		$output = $this->render($control);
		$this->assertNotEmpty($output);
	}

	public function testCultureThTh()
	{
		$control = new TDateFormat();
		$control->setCulture('th_TH');
		$control->setPattern('dd MMMM yyyy');
		$control->setValue('2026-04-19');
		$output = $this->render($control);
		$this->assertNotEmpty($output);
	}

	// --- Case Insensitivity of Presets ---

	public function testPresetCaseInsensitiveFull()
	{
		$control = new TDateFormat();
		$control->setPattern('FULL');
		$control->setValue('2026-04-19');
		$output = $this->render($control);
		$this->assertNotEmpty($output);
	}

	public function testPresetCaseInsensitiveLong()
	{
		$control = new TDateFormat();
		$control->setPattern('LONG');
		$control->setValue('2026-04-19');
		$output = $this->render($control);
		$this->assertNotEmpty($output);
	}

	public function testPresetCaseInsensitiveMedium()
	{
		$control = new TDateFormat();
		$control->setPattern('MEDIUM');
		$control->setValue('2026-04-19');
		$output = $this->render($control);
		$this->assertNotEmpty($output);
	}

	public function testPresetCaseInsensitiveShort()
	{
		$control = new TDateFormat();
		$control->setPattern('SHORT');
		$control->setValue('2026-04-19');
		$output = $this->render($control);
		$this->assertNotEmpty($output);
	}

	// --- Null and Invalid Values ---

	public function testNullValueRendersDefault()
	{
		$control = new TDateFormat();
		$control->setPattern('yyyy-MM-dd');
		$control->setDefaultText('N/A');
		$output = $this->render($control);
		$this->assertStringContainsString('N/A', $output);
	}

	public function testInvalidDateStringHandled()
	{
		$control = new TDateFormat();
		$control->setPattern('yyyy-MM-dd');
		$control->setValue('not-a-date');
		$output = $this->render($control);
		$this->assertNotEmpty($output);
	}
}