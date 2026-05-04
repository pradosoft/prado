<?php

use Prado\I18N\TNumberFormat;
use Prado\Web\UI\THtmlWriter;
use Prado\IO\TTextWriter;
use PHPUnit\Framework\TestCase;

class TNumberFormatTest extends TestCase
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
		$control = new TNumberFormat();
		$this->assertInstanceOf(\Prado\I18N\TI18NControl::class, $control);
	}

	public function testImplementsIDataRenderer()
	{
		$control = new TNumberFormat();
		$this->assertInstanceOf(\Prado\IDataRenderer::class, $control);
	}

	// --- Pattern Property ---

	public function testPatternDefaultEmpty()
	{
		$control = new TNumberFormat();
		$this->assertSame('', $control->getPattern());
	}

	public function testSetPattern()
	{
		$control = new TNumberFormat();
		$control->setPattern('0.00');
		$this->assertSame('0.00', $control->getPattern());
	}

	// --- Value Property ---

	public function testValueDefaultEmpty()
	{
		$control = new TNumberFormat();
		$this->assertSame('', $control->getValue());
	}

	public function testSetValueWithInteger()
	{
		$control = new TNumberFormat();
		$control->setValue(1234);
		$this->assertSame(1234, $control->getValue());
	}

	public function testSetValueWithFloat()
	{
		$control = new TNumberFormat();
		$control->setValue(1234.56);
		$this->assertSame(1234.56, $control->getValue());
	}

	public function testSetValueWithNumericString()
	{
		$control = new TNumberFormat();
		$control->setValue('1234.56');
		$this->assertSame('1234.56', $control->getValue());
	}

	// --- DefaultText Property ---

	public function testDefaultTextDefaultEmpty()
	{
		$control = new TNumberFormat();
		$this->assertSame('', $control->getDefaultText());
	}

	public function testSetDefaultText()
	{
		$control = new TNumberFormat();
		$control->setDefaultText('N/A');
		$this->assertSame('N/A', $control->getDefaultText());
	}

	public function testDefaultTextReturnedWhenValueEmpty()
	{
		$control = new TNumberFormat();
		$control->setDefaultText('N/A');
		$control->setPattern('0.00');
		$output = $this->render($control);
		$this->assertStringContainsString('N/A', $output);
	}

	public function testDefaultTextNotReturnedWhenValueSet()
	{
		$control = new TNumberFormat();
		$control->setDefaultText('N/A');
		$control->setValue(1234.56);
		$output = $this->render($control);
		$this->assertStringNotContainsString('N/A', $output);
	}

	// --- Type Property ---

	public function testTypeDefaultDecimal()
	{
		$control = new TNumberFormat();
		$this->assertSame(\NumberFormatter::DECIMAL, $control->getType());
	}

	public function testSetTypeDecimal()
	{
		$control = new TNumberFormat();
		$control->setType('decimal');
		$this->assertSame(\NumberFormatter::DECIMAL, $control->getType());
	}

	public function testSetTypeCurrency()
	{
		$control = new TNumberFormat();
		$control->setType('currency');
		$this->assertSame(\NumberFormatter::CURRENCY, $control->getType());
	}

	public function testSetTypePercentage()
	{
		$control = new TNumberFormat();
		$control->setType('percentage');
		$this->assertSame(\NumberFormatter::PERCENT, $control->getType());
	}

	public function testSetTypeScientific()
	{
		$control = new TNumberFormat();
		$control->setType('scientific');
		$this->assertSame(\NumberFormatter::SCIENTIFIC, $control->getType());
	}

	public function testSetTypeSpellout()
	{
		$control = new TNumberFormat();
		$control->setType('spellout');
		$this->assertSame(\NumberFormatter::SPELLOUT, $control->getType());
	}

	public function testSetTypeOrdinal()
	{
		$control = new TNumberFormat();
		$control->setType('ordinal');
		$this->assertSame(\NumberFormatter::ORDINAL, $control->getType());
	}

	public function testSetTypeDuration()
	{
		$control = new TNumberFormat();
		$control->setType('duration');
		$this->assertSame(\NumberFormatter::DURATION, $control->getType());
	}

	public function testSetTypeRulebased()
	{
		$control = new TNumberFormat();
		$control->setType('rulebased');
		$this->assertSame(\NumberFormatter::PATTERN_RULEBASED, $control->getType());
	}

	public function testSetTypeAccounting()
	{
		$control = new TNumberFormat();
		$control->setType('accounting');
		$this->assertSame(\NumberFormatter::CURRENCY_ACCOUNTING, $control->getType());
	}

	public function testSetTypeInvalidThrowsException()
	{
		$this->expectException(\Prado\Exceptions\TInvalidDataValueException::class);
		$control = new TNumberFormat();
		$control->setType('invalid');
	}

	// --- Currency Property ---

	public function testCurrencyDefaultUsd()
	{
		$control = new TNumberFormat();
		$this->assertSame('USD', $control->getCurrency());
	}

	public function testSetCurrencyEur()
	{
		$control = new TNumberFormat();
		$control->setCurrency('EUR');
		$this->assertSame('EUR', $control->getCurrency());
	}

	public function testSetCurrencyGbp()
	{
		$control = new TNumberFormat();
		$control->setCurrency('GBP');
		$this->assertSame('GBP', $control->getCurrency());
	}

	public function testSetCurrencyJpy()
	{
		$control = new TNumberFormat();
		$control->setCurrency('JPY');
		$this->assertSame('JPY', $control->getCurrency());
	}

	// --- Data Methods (IDataRenderer) ---

	public function testGetDataReturnsValue()
	{
		$control = new TNumberFormat();
		$control->setValue(1234.56);
		$this->assertSame(1234.56, $control->getData());
	}

	public function testSetDataSetsValue()
	{
		$control = new TNumberFormat();
		$control->setData(1234.56);
		$this->assertSame(1234.56, $control->getValue());
	}

	// --- Decimal Formatting ---

	public function testDecimalFormattingEnUs()
	{
		$control = new TNumberFormat();
		$control->setCulture('en_US');
		$control->setPattern('#,##0.00');
		$control->setValue(1234.56);
		$output = $this->render($control);
		$this->assertStringContainsString('1,234.56', $output);
	}

	public function testDecimalFormattingDeDe()
	{
		$control = new TNumberFormat();
		$control->setCulture('de_DE');
		$control->setPattern('#,##0.00');
		$control->setValue(1234.56);
		$output = $this->render($control);
		$this->assertStringContainsString('1.234,56', $output);
	}

	public function testDecimalFormattingFrFr()
	{
		$control = new TNumberFormat();
		$control->setCulture('fr_FR');
		$control->setPattern('#,##0.00');
		$control->setValue(1234.56);
		$output = $this->render($control);
		$this->assertSame("1\xE2\x80\xAF234,56", $output);
	}

	public function testDecimalFormattingItIt()
	{
		$control = new TNumberFormat();
		$control->setCulture('it_IT');
		$control->setPattern('#,##0.00');
		$control->setValue(1234.56);
		$output = $this->render($control);
		$this->assertSame('1.234,56', $output);
	}

	public function testDecimalFormattingRuRu()
	{
		$control = new TNumberFormat();
		$control->setCulture('ru_RU');
		$control->setPattern('#,##0.00');
		$control->setValue(1234.56);
		$output = $this->render($control);
		$this->assertSame("1\xC2\xA0234,56", $output);
	}

	public function testDecimalFormattingPlPl()
	{
		$control = new TNumberFormat();
		$control->setCulture('pl_PL');
		$control->setPattern('#,##0.00');
		$control->setValue(1234.56);
		$output = $this->render($control);
		$this->assertSame("1\xC2\xA0234,56", $output);
	}

	public function testDecimalFormattingNlNl()
	{
		$control = new TNumberFormat();
		$control->setCulture('nl_NL');
		$control->setPattern('#,##0.00');
		$control->setValue(1234.56);
		$output = $this->render($control);
		$this->assertSame('1.234,56', $output);
	}

	public function testDecimalFormattingJaJp()
	{
		$control = new TNumberFormat();
		$control->setCulture('ja_JP');
		$control->setPattern('#,##0');
		$control->setValue(1234);
		$output = $this->render($control);
		$this->assertSame('1,234', $output);
	}

	public function testDecimalFormattingZhCn()
	{
		$control = new TNumberFormat();
		$control->setCulture('zh_CN');
		$control->setPattern('#,##0.00');
		$control->setValue(1234.56);
		$output = $this->render($control);
		$this->assertSame('1,234.56', $output);
	}

	public function testDecimalFormattingZero()
	{
		$control = new TNumberFormat();
		$control->setCulture('en_US');
		$control->setPattern('0');
		$control->setValue(1);
		$output = $this->render($control);
		$this->assertSame('1', $output);
	}

	public function testDecimalFormattingNegative()
	{
		$control = new TNumberFormat();
		$control->setCulture('en_US');
		$control->setValue(-1234.56);
		$output = $this->render($control);
		$this->assertSame('-1,234.56', $output);
	}

	public function testDecimalFormattingLargeNumber()
	{
		$control = new TNumberFormat();
		$control->setCulture('en_US');
		$control->setPattern('#,##0');
		$control->setValue(1234567890);
		$output = $this->render($control);
		$this->assertSame('1,234,567,890', $output);
	}

	public function testDecimalFormattingNoPattern()
	{
		$control = new TNumberFormat();
		$control->setCulture('en_US');
		$control->setValue(1234.56);
		$output = $this->render($control);
		$this->assertSame('1,234.56', $output);
	}

	// --- Currency Formatting ---

	public function testCurrencyFormattingEnUsUsd()
	{
		$control = new TNumberFormat();
		$control->setType('currency');
		$control->setCulture('en_US');
		$control->setCurrency('USD');
		$control->setValue(1234.56);
		$output = $this->render($control);
		$this->assertStringContainsString('$', $output);
		$this->assertStringContainsString('1,234.56', $output);
	}

	public function testCurrencyFormattingDeDeEur()
	{
		$control = new TNumberFormat();
		$control->setType('currency');
		$control->setCulture('de_DE');
		$control->setCurrency('EUR');
		$control->setValue(1234.56);
		$output = $this->render($control);
		$this->assertStringContainsString('€', $output);
		$this->assertStringContainsString('1.234,56', $output);
	}

	public function testCurrencyFormattingFrFrEur()
	{
		$control = new TNumberFormat();
		$control->setType('currency');
		$control->setCulture('fr_FR');
		$control->setCurrency('EUR');
		$control->setValue(1234.56);
		$output = $this->render($control);
		$this->assertEquals("1\xE2\x80\xAF234,56\xC2\xA0€", $output);
	}

	public function testCurrencyFormattingItItEur()
	{
		$control = new TNumberFormat();
		$control->setType('currency');
		$control->setCulture('it_IT');
		$control->setCurrency('EUR');
		$control->setValue(1234.56);
		$output = $this->render($control);
		$this->assertEquals('1.234,56 €', $output);
	}

	public function testCurrencyFormattingGbGBP()
	{
		$control = new TNumberFormat();
		$control->setType('currency');
		$control->setCulture('en_GB');
		$control->setCurrency('GBP');
		$control->setValue(1234.56);
		$output = $this->render($control);
		$this->assertEquals('£1,234.56', $output);
	}

	public function testCurrencyFormattingJaJpJpy()
	{
		$control = new TNumberFormat();
		$control->setType('currency');
		$control->setCulture('ja_JP');
		$control->setCurrency('JPY');
		$control->setValue(1234);
		$output = $this->render($control);
		$this->assertEquals('￥1,234', $output);
	}

	public function testCurrencyFormattingRuRuRub()
	{
		$control = new TNumberFormat();
		$control->setType('currency');
		$control->setCulture('ru_RU');
		$control->setCurrency('RUB');
		$control->setValue(1234.56);
		$output = $this->render($control);
		$this->assertEquals('1 234,56 ₽', $output);
	}

	public function testCurrencyFormattingPtBrBrl()
	{
		$control = new TNumberFormat();
		$control->setType('currency');
		$control->setCulture('pt_BR');
		$control->setCurrency('BRL');
		$control->setValue(1234.56);
		$output = $this->render($control);
		$this->assertEquals('R$ 1.234,56', $output);
	}

	public function testCurrencyFormattingZero()
	{
		$control = new TNumberFormat();
		$control->setType('currency');
		$control->setCulture('en_US');
		$control->setCurrency('USD');
		$control->setValue(0);
		$output = $this->render($control);
		$this->assertSame('$0.00', $output);
	}

	public function testCurrencyFormattingNegative()
	{
		$control = new TNumberFormat();
		$control->setType('currency');
		$control->setCulture('en_US');
		$control->setCurrency('USD');
		$control->setValue(-1234.56);
		$output = $this->render($control);
		$this->assertSame('-$1,234.56', $output);
	}

	public function testCurrencyAccountingEnUs()
	{
		$control = new TNumberFormat();
		$control->setType('accounting');
		$control->setCulture('en_US');
		$control->setCurrency('USD');
		$control->setValue(1234.56);
		$output = $this->render($control);
		$this->assertSame('$1,234.56', $output);
	}

	// --- Percentage Formatting ---

	public function testPercentageFormattingEnUs()
	{
		$control = new TNumberFormat();
		$control->setType('percentage');
		$control->setCulture('en_US');
		$control->setValue(0.5);
		$output = $this->render($control);
		$this->assertSame('50%', $output);
	}

	public function testPercentageFormattingWholeNumber()
	{
		$control = new TNumberFormat();
		$control->setType('percentage');
		$control->setCulture('en_US');
		$control->setValue(1);
		$output = $this->render($control);
		$this->assertSame('100%', $output);
	}

	public function testPercentageFormattingSmallNumber()
	{
		$control = new TNumberFormat();
		$control->setType('percentage');
		$control->setCulture('en_US');
		$control->setValue(0.123);
		$output = $this->render($control);
		$this->assertSame('12%', $output);
	}

	// --- Scientific Formatting ---

	public function testScientificFormatting()
	{
		$control = new TNumberFormat();
		$control->setType('scientific');
		$control->setCulture('en_US');
		$control->setValue(1234.56);
		$output = $this->render($control);
		$this->assertSame('1.23456E3', $output);
	}

	public function testScientificFormattingSmall()
	{
		$control = new TNumberFormat();
		$control->setType('scientific');
		$control->setCulture('en_US');
		$control->setValue(0.0000123);
		$output = $this->render($control);
		$this->assertSame('1.23E-5', $output);
	}

	// --- Spellout Formatting ---

	public function testSpelloutFormattingEnUs()
	{
		$control = new TNumberFormat();
		$control->setType('spellout');
		$control->setCulture('en_US');
		$control->setValue(123);
		$output = $this->render($control);
		$this->assertSame('one hundred twenty-three', $output);
	}

	public function testSpelloutFormattingDeDe()
	{
		$control = new TNumberFormat();
		$control->setType('spellout');
		$control->setCulture('de_DE');
		$control->setValue(123);
		$output = $this->render($control);
		$this->assertSame("ein\xC2\xADhundert\xC2\xADdrei\xC2\xADund\xC2\xADzwanzig", $output);
	}

	public function testSpelloutFormattingZero()
	{
		$control = new TNumberFormat();
		$control->setType('spellout');
		$control->setCulture('en_US');
		$control->setValue(0);
		$output = $this->render($control);
		$this->assertSame('zero', $output);
	}

	public function testSpelloutFormattingNegative()
	{
		$control = new TNumberFormat();
		$control->setType('spellout');
		$control->setCulture('en_US');
		$control->setValue(-123);
		$output = $this->render($control);
		$this->assertSame('minus one hundred twenty-three', $output);
	}

	// --- Ordinal Formatting ---

	public function testOrdinalFormattingEnUs()
	{
		$control = new TNumberFormat();
		$control->setType('ordinal');
		$control->setCulture('en_US');
		$control->setValue(1);
		$output = $this->render($control);
		$this->assertNotEmpty($output);
		$this->assertStringContainsString('1st', $output);
	}

	public function testOrdinalFormattingEnUsSecond()
	{
		$control = new TNumberFormat();
		$control->setType('ordinal');
		$control->setCulture('en_US');
		$control->setValue(2);
		$output = $this->render($control);
		$this->assertStringContainsString('2nd', $output);
	}

	public function testOrdinalFormattingEnUsThird()
	{
		$control = new TNumberFormat();
		$control->setType('ordinal');
		$control->setCulture('en_US');
		$control->setValue(3);
		$output = $this->render($control);
		$this->assertStringContainsString('3rd', $output);
	}

	public function testOrdinalFormattingEnUsFourth()
	{
		$control = new TNumberFormat();
		$control->setType('ordinal');
		$control->setCulture('en_US');
		$control->setValue(4);
		$output = $this->render($control);
		$this->assertStringContainsString('4th', $output);
	}

	public function testOrdinalFormattingDeDe()
	{
		$control = new TNumberFormat();
		$control->setType('ordinal');
		$control->setCulture('de_DE');
		$control->setValue(1);
		$output = $this->render($control);
		$this->assertSame('1.', $output);
	}

	// --- Duration Formatting ---

	public function testDurationFormattingEnUs()
	{
		$control = new TNumberFormat();
		$control->setType('duration');
		$control->setCulture('en_US');
		$control->setValue(3661);
		$output = $this->render($control);
		$this->assertSame('1:01:01', $output);
	}

	public function testDurationFormattingZero()
	{
		$control = new TNumberFormat();
		$control->setType('duration');
		$control->setCulture('en_US');
		$control->setValue(0);
		$output = $this->render($control);
		$this->assertSame('0 sec.', $output);
	}

	public function testDurationFormattingLarge()
	{
		$control = new TNumberFormat();
		$control->setType('duration');
		$control->setCulture('en_US');
		$control->setValue(90061);
		$output = $this->render($control);
		$this->assertSame('25:01:01', $output);
	}

	// --- Multiple Cultures ---

	public function testCultureRuRu()
	{
		$control = new TNumberFormat();
		$control->setCulture('ru_RU');
		$control->setPattern('#,##0.00');
		$control->setValue(1234.56);
		$output = $this->render($control);
		$this->assertSame("1\xC2\xA0234,56", $output);
	}

	public function testCultureItIt()
	{
		$control = new TNumberFormat();
		$control->setCulture('it_IT');
		$control->setPattern('#,##0.00');
		$control->setValue(1234.56);
		$output = $this->render($control);
		$this->assertSame('1.234,56', $output);
	}

	public function testCultureEsEs()
	{
		$control = new TNumberFormat();
		$control->setCulture('es_ES');
		$control->setPattern('#,##0.00');
		$control->setValue(1234.56);
		$output = $this->render($control);
		$this->assertSame('1.234,56', $output);
	}

	public function testCultureJaJp()
	{
		$control = new TNumberFormat();
		$control->setCulture('ja_JP');
		$control->setPattern('#,##0');
		$control->setValue(1234);
		$output = $this->render($control);
		$this->assertSame('1,234', $output);
	}

	public function testCultureKoKr()
	{
		$control = new TNumberFormat();
		$control->setCulture('ko_KR');
		$control->setPattern('#,##0');
		$control->setValue(1234);
		$output = $this->render($control);
		$this->assertSame('1,234', $output);
	}

	public function testCultureZhCn()
	{
		$control = new TNumberFormat();
		$control->setCulture('zh_CN');
		$control->setPattern('#,##0.00');
		$control->setValue(1234.56);
		$output = $this->render($control);
		$this->assertSame('1,234.56', $output);
	}

	public function testCultureThTh()
	{
		$control = new TNumberFormat();
		$control->setCulture('th_TH');
		$control->setPattern('#,##0.00');
		$control->setValue(1234.56);
		$output = $this->render($control);
		$this->assertSame('1,234.56', $output);
	}

	public function testCultureArSa()
	{
		$control = new TNumberFormat();
		$control->setCulture('ar_SA');
		$control->setPattern('#,##0.00');
		$control->setValue(1234.56);
		$output = $this->render($control);
		$this->assertSame("١٬٢٣٤٫٥٦", $output);
	}

	public function testCultureHiIn()
	{
		$control = new TNumberFormat();
		$control->setCulture('hi_IN');
		$control->setPattern('#,##0.00');
		$control->setValue(1234.56);
		$output = $this->render($control);
		$this->assertSame('1,234.56', $output);
	}

	public function testCulturePtBr()
	{
		$control = new TNumberFormat();
		$control->setCulture('pt_BR');
		$control->setPattern('#,##0.00');
		$control->setValue(1234.56);
		$output = $this->render($control);
		$this->assertSame('1.234,56', $output);
	}

	public function testCultureNlNl()
	{
		$control = new TNumberFormat();
		$control->setCulture('nl_NL');
		$control->setPattern('#,##0.00');
		$control->setValue(1234.56);
		$output = $this->render($control);
		$this->assertSame('1.234,56', $output);
	}

	public function testCulturePlPl()
	{
		$control = new TNumberFormat();
		$control->setCulture('pl_PL');
		$control->setPattern('#,##0.00');
		$control->setValue(1234.56);
		$output = $this->render($control);
		$this->assertSame("1\xC2\xA0234,56", $output);
	}

	// --- Charset Property ---

	public function testCharsetDefault()
	{
		$control = new TNumberFormat();
		$this->assertIsString($control->getCharset());
	}

	public function testSetCharset()
	{
		$control = new TNumberFormat();
		$control->setCharset('UTF-8');
		$this->assertSame('UTF-8', $control->getCharset());
	}

	public function testCharsetAffectsOutput()
	{
		$control = new TNumberFormat();
		$control->setCulture('de_DE');
		$control->setType('currency');
		$control->setCurrency('EUR');
		$control->setValue(1234.56);
		$output = $this->render($control);
		$this->assertSame("1.234,56\xC2\xA0€", $output);
	}

	// --- Edge Cases ---

	public function testRenderWritesToWriter()
	{
		$control = new TNumberFormat();
		$control->setPattern('#,##0.00');
		$control->setValue(1234.56);
		$output = $this->render($control);
		$this->assertSame('1,234.56', $output);
	}

	public function testEmptyValueWithDefaultText()
	{
		$control = new TNumberFormat();
		$control->setDefaultText('N/A');
		$output = $this->render($control);
		$this->assertStringContainsString('N/A', $output);
	}

	public function testNullValueWithDefaultText()
	{
		$control = new TNumberFormat();
		$control->setValue(null);
		$control->setDefaultText('N/A');
		$output = $this->render($control);
		$this->assertStringContainsString('N/A', $output);
	}

	public function testVeryLargeNumber()
	{
		$control = new TNumberFormat();
		$control->setCulture('en_US');
		$control->setPattern('#,##0');
		$control->setValue(1234567890123456);
		$output = $this->render($control);
		$this->assertSame('1,234,567,890,123,456', $output);
	}

	public function testVerySmallNumber()
	{
		$control = new TNumberFormat();
		$control->setCulture('en_US');
		$control->setPattern('0.############');
		$control->setValue(0.000000001234);
		$output = $this->render($control);
		$this->assertSame('0.000000001234', $output);
	}

	public function testIntegerValue()
	{
		$control = new TNumberFormat();
		$control->setCulture('en_US');
		$control->setPattern('#,##0');
		$control->setValue(1234567);
		$output = $this->render($control);
		$this->assertStringContainsString('1,234,567', $output);
	}

	public function testCustomPatternDecimalPlaces()
	{
		$control = new TNumberFormat();
		$control->setCulture('en_US');
		$control->setPattern('0.0000');
		$control->setValue(1.23456);
		$output = $this->render($control);
		$this->assertStringContainsString('1.2346', $output);
	}

	public function testCustomPatternNoDecimal()
	{
		$control = new TNumberFormat();
		$control->setCulture('en_US');
		$control->setPattern('#,##0');
		$control->setValue(1234.56);
		$output = $this->render($control);
		$this->assertStringContainsString('1,235', $output);
	}

	public function testPatternWithPrefix()
	{
		$control = new TNumberFormat();
		$control->setCulture('en_US');
		$control->setPattern('€#,##0.00');
		$control->setValue(1234.56);
		$output = $this->render($control);
		$this->assertStringContainsString('€', $output);
	}

	public function testPatternWithSuffix()
	{
		$control = new TNumberFormat();
		$control->setCulture('en_US');
		$control->setPattern('#,##0.00" units"');
		$control->setValue(1234.56);
		$output = $this->render($control);
		$this->assertStringContainsString('units', $output);
	}

	// --- RuleBased Type ---

	public function testRulebasedFormatting()
	{
		$control = new TNumberFormat();
		$control->setType('rulebased');
		$control->setCulture('en_US');
		$control->setPattern('%digits-verbose');
		$control->setValue(123);
		$output = $this->render($control);
		$this->assertNotEmpty($output);
	}

	// --- Case Insensitivity of Type ---

	public function testTypeCaseInsensitiveDecimal()
	{
		$control = new TNumberFormat();
		$control->setType('DECIMAL');
		$this->assertSame(\NumberFormatter::DECIMAL, $control->getType());
	}

	public function testTypeCaseInsensitiveCurrency()
	{
		$control = new TNumberFormat();
		$control->setType('CURRENCY');
		$this->assertSame(\NumberFormatter::CURRENCY, $control->getType());
	}

	public function testTypeCaseInsensitivePercentage()
	{
		$control = new TNumberFormat();
		$control->setType('PERCENTAGE');
		$this->assertSame(\NumberFormatter::PERCENT, $control->getType());
	}
}