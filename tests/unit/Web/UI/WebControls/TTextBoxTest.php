<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Prado;
use Prado\Test\Unit\Harness\Traits\TTempDirectoryTrait;
use Prado\Web\UI\ActiveControls\TActiveSuggestionList;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TSuggestionList;
use Prado\Web\UI\WebControls\TTextBox;
use Prado\Web\UI\WebControls\TTextBoxAutoCompleteType;
use Prado\Web\UI\WebControls\TTextBoxMode;
use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;

class TTextBoxTest extends TestCase
{
	use TTempDirectoryTrait;
	use TWebControlRenderTrait;

	private mixed $_savedParser;

	protected function setUp(): void
	{
		parent::setUp();
		$this->_savedParser = PradoUnit::getStaticProp(TTextBox::class, '_safeTextParser');
		PradoUnit::setStaticProp(TTextBox::class, '_safeTextParser', null);
	}

	protected function tearDown(): void
	{
		PradoUnit::setStaticProp(TTextBox::class, '_safeTextParser', $this->_savedParser);
		$this->removeTempDirectories();
		parent::tearDown();
	}

	public static function suggestionListClassProvider(): array
	{
		return [
			'TSuggestionList' => [TSuggestionList::class],
			'TActiveSuggestionList' => [TActiveSuggestionList::class],
		];
	}

	private function createPageWithTextBox(string $listClass = TSuggestionList::class): array
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_inFormRender', true);
		PradoUnit::setProp($page, '_clientScript', new TClientScriptManager($page));
		$textbox = new TTextBox();
		$textbox->setID('City');
		$textbox->setEnableClientScript(false);
		$list = new $listClass();
		$list->setID('Cities');
		$page->getControls()->add($textbox);
		$page->getControls()->add($list);
		return [$page, $textbox, $list];
	}

	// --- SuggestionList ---

	public function testSuggestionListDefaultsToEmpty()
	{
		$this->assertSame('', (new TTextBox())->getSuggestionList());
	}

	public function testSetSuggestionList()
	{
		$textbox = new TTextBox();
		$textbox->setSuggestionList('Cities');
		$this->assertSame('Cities', $textbox->getSuggestionList());
		$textbox->setSuggestionList('');
		$this->assertSame('', $textbox->getSuggestionList());
	}

	public function testNoListAttributeByDefault()
	{
		[, $textbox] = $this->createPageWithTextBox();
		$this->assertStringNotContainsString('list=', $this->render($textbox));
	}

	/**
	 * @dataProvider suggestionListClassProvider
	 */
	public function testListAttributeRendersClientId(string $listClass)
	{
		[, $textbox, $list] = $this->createPageWithTextBox($listClass);
		$textbox->setSuggestionList('Cities');
		$this->assertStringContainsString('list="' . $list->getClientID() . '"', $this->render($textbox));
	}

	/**
	 * @dataProvider suggestionListClassProvider
	 */
	public function testListAttributeRendersForInputModes(string $listClass)
	{
		[, $textbox, $list] = $this->createPageWithTextBox($listClass);
		$textbox->setSuggestionList('Cities');
		foreach ([TTextBoxMode::SingleLine, TTextBoxMode::Email, TTextBoxMode::Number, TTextBoxMode::Range, TTextBoxMode::Search, TTextBoxMode::Url] as $mode) {
			$textbox->setTextMode($mode);
			$this->assertStringContainsString('list="' . $list->getClientID() . '"', $this->render($textbox), $mode);
		}
	}

	/**
	 * @dataProvider suggestionListClassProvider
	 */
	public function testNoListAttributeForPasswordAndMultiLine(string $listClass)
	{
		[, $textbox] = $this->createPageWithTextBox($listClass);
		$textbox->setSuggestionList('Cities');
		foreach ([TTextBoxMode::Password, TTextBoxMode::MultiLine] as $mode) {
			$textbox->setTextMode($mode);
			$this->assertStringNotContainsString('list=', $this->render($textbox), $mode);
		}
	}

	/**
	 * @dataProvider suggestionListClassProvider
	 */
	public function testNoListAttributeWhenListNotVisible(string $listClass)
	{
		[, $textbox, $list] = $this->createPageWithTextBox($listClass);
		$textbox->setSuggestionList('Cities');
		$list->setVisible(false);
		$this->assertStringNotContainsString('list=', $this->render($textbox));
	}

	public function testMissingSuggestionListThrows()
	{
		[, $textbox] = $this->createPageWithTextBox();
		$textbox->setSuggestionList('Missing');
		$this->expectException(TInvalidDataValueException::class);
		$this->render($textbox);
	}

	// --- Constraints ---

	private function constrainedTextBox(string $mode): TTextBox
	{
		[, $textbox] = $this->createPageWithTextBox();
		$textbox->setTextMode($mode);
		$textbox->setMin(' 1 ');
		$textbox->setMax('9');
		$textbox->setStep('2');
		$textbox->setPattern('[a-z]+');
		$textbox->setPlaceholder('Hint');
		$textbox->setRequired('true');
		$textbox->setMultiple(true);
		return $textbox;
	}

	public function testConstraintDefaults()
	{
		$textbox = new TTextBox();
		foreach (['Min', 'Max', 'Step', 'Pattern', 'Placeholder'] as $name) {
			$this->assertSame('', $textbox->{'get' . $name}(), $name);
		}
		$this->assertFalse($textbox->getRequired());
		$this->assertFalse($textbox->getMultiple());
		[, $textbox] = $this->createPageWithTextBox();
		$output = $this->render($textbox);
		foreach (['min=', 'max=', 'step=', 'pattern=', 'placeholder=', 'required', 'multiple'] as $attribute) {
			$this->assertStringNotContainsString($attribute, $output);
		}
	}

	public static function constraintModeProvider(): array
	{
		// mode => [min/max/step, pattern, multiple, placeholder, required]
		return [
			'SingleLine' => [TTextBoxMode::SingleLine, false, true, false, true, true],
			'MultiLine' => [TTextBoxMode::MultiLine, false, false, false, true, true],
			'Password' => [TTextBoxMode::Password, false, true, false, true, true],
			'Color' => [TTextBoxMode::Color, false, false, false, false, false],
			'Date' => [TTextBoxMode::Date, true, false, false, false, true],
			'DatetimeLocal' => [TTextBoxMode::DatetimeLocal, true, false, false, false, true],
			'Email' => [TTextBoxMode::Email, false, true, true, true, true],
			'Month' => [TTextBoxMode::Month, true, false, false, false, true],
			'Number' => [TTextBoxMode::Number, true, false, false, true, true],
			'Range' => [TTextBoxMode::Range, true, false, false, false, false],
			'Search' => [TTextBoxMode::Search, false, true, false, true, true],
			'Tel' => [TTextBoxMode::Tel, false, true, false, true, true],
			'Time' => [TTextBoxMode::Time, true, false, false, false, true],
			'Url' => [TTextBoxMode::Url, false, true, false, true, true],
			'Week' => [TTextBoxMode::Week, true, false, false, false, true],
		];
	}

	/**
	 * @dataProvider constraintModeProvider
	 */
	public function testConstraintAttributesRenderForTheirTextModes(string $mode, bool $ranged, bool $pattern, bool $multiple, bool $placeholder, bool $required)
	{
		$output = $this->render($this->constrainedTextBox($mode));
		$check = fn (bool $expected, string $needle) => $expected
			? $this->assertStringContainsString($needle, $output, "$mode $needle")
			: $this->assertStringNotContainsString($needle, $output, "$mode $needle");
		$check($ranged, 'min="1" max="9" step="2"');
		$check($pattern, 'pattern="[a-z]+"');
		$check($multiple, 'multiple="multiple"');
		$check($placeholder, 'placeholder="Hint"');
		$check($required, 'required="required"');
	}

	public function testStepAny()
	{
		[, $textbox] = $this->createPageWithTextBox();
		$textbox->setTextMode(TTextBoxMode::Number);
		$textbox->setStep('any');
		$this->assertStringContainsString('step="any"', $this->render($textbox));
	}

	public static function lengthModeProvider(): array
	{
		return [
			'SingleLine' => [TTextBoxMode::SingleLine, true],
			'MultiLine' => [TTextBoxMode::MultiLine, true],
			'Email' => [TTextBoxMode::Email, true],
			'Password' => [TTextBoxMode::Password, true],
			'Search' => [TTextBoxMode::Search, true],
			'Tel' => [TTextBoxMode::Tel, true],
			'Url' => [TTextBoxMode::Url, true],
			'Number' => [TTextBoxMode::Number, false],
			'Date' => [TTextBoxMode::Date, false],
			'Color' => [TTextBoxMode::Color, false],
		];
	}

	/**
	 * @dataProvider lengthModeProvider
	 */
	public function testMinLengthAndDirNameRenderForTextualModes(string $mode, bool $expected)
	{
		[, $textbox] = $this->createPageWithTextBox();
		$textbox->setTextMode($mode);
		$textbox->setMinLength('3');
		$textbox->setDirName('comment.dir');
		$output = $this->render($textbox);
		if ($expected) {
			$this->assertStringContainsString('minlength="3"', $output);
			$this->assertStringContainsString('dirname="comment.dir"', $output);
		} else {
			$this->assertStringNotContainsString('minlength', $output);
			$this->assertStringNotContainsString('dirname', $output);
		}
	}

	public function testMinLengthDefaultsAndClears()
	{
		$textbox = new TTextBox();
		$this->assertSame(0, $textbox->getMinLength());
		$this->assertSame('', $textbox->getDirName());
		$textbox->setMinLength(-4);
		$this->assertSame(0, $textbox->getMinLength());
	}

	public function testMultiLineRendersMaxLength()
	{
		[, $textbox] = $this->createPageWithTextBox();
		$textbox->setTextMode(TTextBoxMode::MultiLine);
		$textbox->setMaxLength(500);
		$this->assertStringContainsString('maxlength="500"', $this->render($textbox));
	}

	public function testDeprecatedDatetimeStillRendersItsType()
	{
		[, $textbox] = $this->createPageWithTextBox();
		$textbox->setTextMode(TTextBoxMode::Datetime);
		$this->assertStringContainsString('type="datetime"', $this->render($textbox));
	}

	// --- Properties ---

	public function testPropertyDefaultsAndSetters()
	{
		$textbox = new TTextBox();
		$this->assertTrue($textbox->getEnableClientScript());
		$this->assertFalse($textbox->getAutoPostBack());
		$this->assertFalse($textbox->getAutoTrim());
		$this->assertTrue($textbox->getCausesValidation());
		$this->assertSame(0, $textbox->getColumns());
		$this->assertSame(0, $textbox->getMaxLength());
		$this->assertFalse($textbox->getReadOnly());
		$this->assertSame(TTextBox::DEFAULT_ROWS, $textbox->getRows());
		$this->assertFalse($textbox->getPersistPassword());
		$this->assertSame('', $textbox->getValidationGroup());
		$this->assertTrue($textbox->getWrap());
		$this->assertSame(TTextBoxAutoCompleteType::None, $textbox->getAutoCompleteType());
		$this->assertSame('Prado.WebUI.TTextBox', PradoUnit::invoke($textbox, 'getClientClassName'));

		$textbox->setEnableClientScript('false');
		$textbox->setAutoPostBack('true');
		$textbox->setAutoTrim('true');
		$textbox->setCausesValidation('false');
		$textbox->setColumns('12');
		$textbox->setMaxLength('30');
		$textbox->setReadOnly('true');
		$textbox->setRows('7');
		$textbox->setPersistPassword('true');
		$textbox->setValidationGroup('Group');
		$textbox->setWrap('false');
		$textbox->setAutoCompleteType('Disabled');
		$this->assertFalse($textbox->getEnableClientScript());
		$this->assertTrue($textbox->getAutoPostBack());
		$this->assertTrue($textbox->getAutoTrim());
		$this->assertFalse($textbox->getCausesValidation());
		$this->assertSame(12, $textbox->getColumns());
		$this->assertSame(30, $textbox->getMaxLength());
		$this->assertTrue($textbox->getReadOnly());
		$this->assertSame(7, $textbox->getRows());
		$this->assertTrue($textbox->getPersistPassword());
		$this->assertSame('Group', $textbox->getValidationGroup());
		$this->assertFalse($textbox->getWrap());
		$this->assertSame(TTextBoxAutoCompleteType::Disabled, $textbox->getAutoCompleteType());
	}

	public function testTextIsTheDataAndValidationValue()
	{
		$textbox = new TTextBox();
		$textbox->setData(42);
		$this->assertSame('42', $textbox->getText());
		$this->assertSame('42', $textbox->getData());
		$this->assertSame('42', $textbox->getValidationPropertyValue());
		$this->assertTrue($textbox->getIsValid());
		$textbox->setIsValid('false');
		$this->assertFalse($textbox->getIsValid());
	}

	// --- Rendering ---

	public static function inputTypeProvider(): array
	{
		return [
			'SingleLine' => [TTextBoxMode::SingleLine, 'text'],
			'Password' => [TTextBoxMode::Password, 'password'],
			'Color' => [TTextBoxMode::Color, 'color'],
			'Date' => [TTextBoxMode::Date, 'date'],
			'DatetimeLocal' => [TTextBoxMode::DatetimeLocal, 'datetime-local'],
			'Email' => [TTextBoxMode::Email, 'email'],
			'Month' => [TTextBoxMode::Month, 'month'],
			'Number' => [TTextBoxMode::Number, 'number'],
			'Range' => [TTextBoxMode::Range, 'range'],
			'Search' => [TTextBoxMode::Search, 'search'],
			'Tel' => [TTextBoxMode::Tel, 'tel'],
			'Time' => [TTextBoxMode::Time, 'time'],
			'Url' => [TTextBoxMode::Url, 'url'],
			'Week' => [TTextBoxMode::Week, 'week'],
		];
	}

	/**
	 * @dataProvider inputTypeProvider
	 */
	public function testTextModeRendersItsInputType(string $mode, string $type)
	{
		[, $textbox] = $this->createPageWithTextBox();
		$textbox->setTextMode($mode);
		$this->assertStringContainsString('<input name="City" type="' . $type . '"', $this->render($textbox));
	}

	public function testSingleLineRendersValueSizeAndMaxLength()
	{
		[, $textbox] = $this->createPageWithTextBox();
		$textbox->setText('Paris');
		$textbox->setColumns(10);
		$textbox->setMaxLength(20);
		$output = $this->render($textbox);
		$this->assertStringContainsString('value="Paris"', $output);
		$this->assertStringContainsString('size="10"', $output);
		$this->assertStringContainsString('maxlength="20"', $output);
	}

	public function testPasswordRendersItsValueOnlyWhenPersisted()
	{
		[, $textbox] = $this->createPageWithTextBox();
		$textbox->setTextMode(TTextBoxMode::Password);
		$textbox->setText('secret');
		$this->assertStringNotContainsString('value=', $this->render($textbox));
		$textbox->setPersistPassword(true);
		$this->assertStringContainsString('value="secret"', $this->render($textbox));
	}

	public static function autoCompleteProvider(): array
	{
		return [
			'Enabled' => [TTextBoxAutoCompleteType::Enabled, 'autocomplete="on"'],
			'Disabled' => [TTextBoxAutoCompleteType::Disabled, 'autocomplete="off"'],
			'None' => [TTextBoxAutoCompleteType::None, null],
		];
	}

	/**
	 * @dataProvider autoCompleteProvider
	 */
	public function testAutoCompleteTypeRendersAutocomplete(string $type, ?string $expected)
	{
		[, $textbox] = $this->createPageWithTextBox();
		$textbox->setAutoCompleteType($type);
		$output = $this->render($textbox);
		if ($expected === null) {
			$this->assertStringNotContainsString('autocomplete', $output);
		} else {
			$this->assertStringContainsString($expected, $output);
		}
	}

	public function testMultiLineRendersRowsColumnsWrapAndEncodedText()
	{
		[, $textbox] = $this->createPageWithTextBox();
		$textbox->setTextMode(TTextBoxMode::MultiLine);
		$textbox->setRows(0);
		$textbox->setColumns(-1);
		$textbox->setWrap(false);
		$textbox->setText('a < b');
		$this->assertSame('<textarea name="City" rows="4" cols="20" wrap="off" id="City">' . "\n" . 'a &lt; b</textarea>', $this->render($textbox));
	}

	public function testMultiLineRendersItsRowsAndColumns()
	{
		[, $textbox] = $this->createPageWithTextBox();
		$textbox->setTextMode(TTextBoxMode::MultiLine);
		$textbox->setRows(6);
		$textbox->setColumns(40);
		$output = $this->render($textbox);
		$this->assertStringContainsString('rows="6" cols="40"', $output);
		$this->assertStringNotContainsString('wrap=', $output);
	}

	public function testReadOnlyRendersReadonly()
	{
		[, $textbox] = $this->createPageWithTextBox();
		$textbox->setReadOnly(true);
		$this->assertStringContainsString('readonly="readonly"', $this->render($textbox));
	}

	public function testDisabledParentRendersDisabled()
	{
		[$page] = $this->createPageWithTextBox();
		$panel = new \Prado\Web\UI\WebControls\TPanel();
		$panel->setEnabled(false);
		$page->getControls()->add($panel);
		$textbox = new TTextBox();
		$textbox->setEnableClientScript(false);
		$panel->getControls()->add($textbox);
		$this->assertStringContainsString('disabled="disabled"', $this->render($textbox));
	}

	private function mockClientScript(TPage $page): TClientScriptManager
	{
		$cs = $this->getMockBuilder(TClientScriptManager::class)->setConstructorArgs([$page])->onlyMethods(['registerPostBackControl'])->getMock();
		PradoUnit::setProp($page, '_clientScript', $cs);
		return $cs;
	}

	public function testClientScriptRegistersThePostBackControl()
	{
		[$page, $textbox] = $this->createPageWithTextBox();
		$textbox->setEnableClientScript(true);
		$textbox->setAutoPostBack(true);
		$textbox->setCausesValidation(false);
		$textbox->setValidationGroup('Group');
		$this->mockClientScript($page)->expects($this->once())->method('registerPostBackControl')->with('Prado.WebUI.TTextBox', [
			'ID' => 'City',
			'EventTarget' => 'City',
			'AutoPostBack' => true,
			'CausesValidation' => false,
			'ValidationGroup' => 'Group',
			'TextMode' => TTextBoxMode::SingleLine,
		]);
		$this->assertStringContainsString('id="City"', $this->render($textbox));
	}

	public static function clientScriptProvider(): array
	{
		// enabled, enableClientScript, autoPostBack, textMode, javascript => registers
		return [
			'single line' => [true, true, false, TTextBoxMode::SingleLine, true, true],
			'auto postback multiline' => [true, true, true, TTextBoxMode::MultiLine, true, true],
			'multiline' => [true, true, false, TTextBoxMode::MultiLine, true, false],
			'disabled' => [false, true, true, TTextBoxMode::SingleLine, true, false],
			'client script off' => [true, false, true, TTextBoxMode::SingleLine, true, false],
			'no javascript' => [true, true, true, TTextBoxMode::SingleLine, false, false],
		];
	}

	/**
	 * @dataProvider clientScriptProvider
	 */
	public function testClientScriptConditions(bool $enabled, bool $clientScript, bool $autoPostBack, string $mode, bool $javascript, bool $registers)
	{
		[$page, $textbox] = $this->createPageWithTextBox();
		$textbox->setEnabled($enabled);
		$textbox->setEnableClientScript($clientScript);
		$textbox->setAutoPostBack($autoPostBack);
		$textbox->setTextMode($mode);
		$page->setClientSupportsJavaScript($javascript);
		$this->mockClientScript($page)->expects($registers ? $this->once() : $this->never())->method('registerPostBackControl');
		$this->render($textbox);
	}

	// --- Post data ---

	public function testLoadPostDataChangesTheText()
	{
		$textbox = new TTextBox();
		$this->assertTrue($textbox->loadPostData('City', ['City' => ' Paris ']));
		$this->assertSame(' Paris ', $textbox->getText());
		$this->assertTrue($textbox->getDataChanged());
		$this->assertFalse($textbox->loadPostData('City', ['City' => ' Paris ']));
	}

	public function testLoadPostDataTrimsWhenAutoTrim()
	{
		$textbox = new TTextBox();
		$textbox->setText('Paris');
		$textbox->setAutoTrim(true);
		$this->assertFalse($textbox->loadPostData('City', ['City' => ' Paris ']));
		$this->assertFalse($textbox->getDataChanged());
	}

	public function testLoadPostDataIgnoresReadOnly()
	{
		$textbox = new TTextBox();
		$textbox->setReadOnly(true);
		$this->assertFalse($textbox->loadPostData('City', ['City' => 'Paris']));
		$this->assertSame('', $textbox->getText());
	}

	public function testRaisePostDataChangedEventRaisesOnTextChanged()
	{
		$textbox = new TTextBox();
		$raised = [];
		$textbox->attachEventHandler('OnTextChanged', function ($sender, $param) use (&$raised) {
			$raised[] = [$sender, $param];
		});
		$textbox->raisePostDataChangedEvent();
		$this->assertSame([[$textbox, null]], $raised);
	}

	public static function postBackValidationProvider(): array
	{
		return [
			'auto postback and causes validation' => [true, true, true],
			'auto postback only' => [true, false, false],
			'causes validation only' => [false, true, false],
		];
	}

	/**
	 * @dataProvider postBackValidationProvider
	 */
	public function testRaisePostDataChangedEventValidatesOnAutoPostBack(bool $autoPostBack, bool $causesValidation, bool $validates)
	{
		$page = $this->createMock(TPage::class);
		$page->expects($validates ? $this->once() : $this->never())->method('validate')->with('Group');
		$textbox = new TTextBox();
		$textbox->setPage($page);
		$textbox->setAutoPostBack($autoPostBack);
		$textbox->setCausesValidation($causesValidation);
		$textbox->setValidationGroup('Group');
		$textbox->raisePostDataChangedEvent();
	}

	// --- SafeText ---

	private function useTempRuntimePath(): array
	{
		$app = Prado::getApplication();
		$saved = $app->getRuntimePath();
		$path = $this->makeTempDirectory();
		PradoUnit::setProp($app, '_runtimePath', $path);
		return [$app, $saved, $path];
	}

	private function uncachedConfig(): \HTMLPurifier_Config
	{
		$config = \HTMLPurifier_Config::createDefault();
		$config->set('Cache.DefinitionImpl', null);
		return $config;
	}

	public function testDefaultConfigCachesInTheRuntimePath()
	{
		[$app, $saved, $path] = $this->useTempRuntimePath();
		try {
			$config = (new TTextBox())->getConfig();
			$this->assertInstanceOf(\HTMLPurifier_Config::class, $config);
			$this->assertSame($path . DIRECTORY_SEPARATOR . 'htmlpurifier', $config->get('Cache.SerializerPath'));
			$this->assertDirectoryExists($path . DIRECTORY_SEPARATOR . 'htmlpurifier');
			$this->assertSame($config->get('Cache.SerializerPath'), (new TTextBox())->getConfig()->get('Cache.SerializerPath'));
		} finally {
			PradoUnit::setProp($app, '_runtimePath', $saved);
		}
	}

	public function testDefaultConfigThrowsWhenItsFolderCannotBeCreated()
	{
		[$app, $saved, $path] = $this->useTempRuntimePath();
		PradoUnit::setProp($app, '_runtimePath', $path . DIRECTORY_SEPARATOR . 'missing');
		try {
			$this->expectException(TConfigurationException::class);
			(new TTextBox())->getConfig();
		} finally {
			PradoUnit::setProp($app, '_runtimePath', $saved);
		}
	}

	public function testSafeTextStripsScriptAndIsCachedUntilTheTextChanges()
	{
		[$app, $saved] = $this->useTempRuntimePath();
		try {
			$textbox = new TTextBox();
			$textbox->setText('<b>bold</b><script>alert(1)</script>');
			$this->assertSame('<b>bold</b>', $textbox->getSafeText());
			$this->assertSame('<b>bold</b>', $textbox->getSafeText());
			$textbox->setText('<i>x</i>');
			$this->assertSame('<i>x</i>', $textbox->getSafeText());
		} finally {
			PradoUnit::setProp($app, '_runtimePath', $saved);
		}
	}

	public function testSafeTextUsesEachTextBoxConfig()
	{
		[$app, $saved] = $this->useTempRuntimePath();
		try {
			$default = new TTextBox();
			$default->setText('<b>bold</b><i>it</i>');
			$this->assertSame('<b>bold</b><i>it</i>', $default->getSafeText());

			$strict = new TTextBox();
			$config = $this->uncachedConfig();
			$config->set('HTML.Allowed', 'b');
			$strict->setConfig($config);
			$this->assertSame($config, $strict->getConfig());
			$strict->setText('<b>bold</b><i>it</i>');
			$this->assertSame('<b>bold</b>it', $strict->getSafeText());

			$other = new TTextBox();
			$other->setText('<b>bold</b><i>it</i>');
			$this->assertSame('<b>bold</b><i>it</i>', $other->getSafeText());
		} finally {
			PradoUnit::setProp($app, '_runtimePath', $saved);
		}
	}

	public function testSetConfigResetsTheSafeText()
	{
		[$app, $saved] = $this->useTempRuntimePath();
		try {
			$textbox = new TTextBox();
			$textbox->setText('<b>bold</b><i>it</i>');
			$this->assertSame('<b>bold</b><i>it</i>', $textbox->getSafeText());
			$config = $this->uncachedConfig();
			$config->set('HTML.Allowed', 'i');
			$textbox->setConfig($config);
			$this->assertSame('bold<i>it</i>', $textbox->getSafeText());
			$config = $this->uncachedConfig();
			$config->set('HTML.Allowed', 'b');
			$textbox->setConfig($config);
			$this->assertSame('<b>bold</b>it', $textbox->getSafeText());
		} finally {
			PradoUnit::setProp($app, '_runtimePath', $saved);
		}
	}

	public function testLoadPostDataTrimsAChangedValue()
	{
		$textbox = new TTextBox();
		$textbox->setAutoTrim(true);
		$this->assertTrue($textbox->loadPostData('City', ['City' => ' Paris ']));
		$this->assertSame('Paris', $textbox->getText());
	}

	public function testLoadPostDataTrimsButIgnoresReadOnly()
	{
		$textbox = new TTextBox();
		$textbox->setAutoTrim(true);
		$textbox->setReadOnly(true);
		$this->assertFalse($textbox->loadPostData('City', ['City' => ' Paris ']));
		$this->assertSame('', $textbox->getText());
	}

	public function testListAttributeValueIsEmptyForMultiLine()
	{
		[, $textbox] = $this->createPageWithTextBox();
		$textbox->setSuggestionList('Cities');
		$textbox->setTextMode(TTextBoxMode::MultiLine);
		$this->assertSame('', PradoUnit::invoke($textbox, 'getListAttributeValue'));
	}

	public function testCustomConfigParserIsReusedForNewText()
	{
		[$app, $saved] = $this->useTempRuntimePath();
		try {
			$textbox = new TTextBox();
			$config = $this->uncachedConfig();
			$config->set('HTML.Allowed', 'b');
			$textbox->setConfig($config);
			$textbox->setText('<b>a</b><i>b</i>');
			$parser = PradoUnit::invoke($textbox, 'getSafeTextParser');
			$this->assertSame('<b>a</b>b', $textbox->getSafeText());
			$textbox->setText('<i>c</i>');
			$this->assertSame('c', $textbox->getSafeText());
			$this->assertSame($parser, PradoUnit::invoke($textbox, 'getSafeTextParser'));
		} finally {
			PradoUnit::setProp($app, '_runtimePath', $saved);
		}
	}

	public function testUnknownViewStateModesRenderATextInputWithoutAutocomplete()
	{
		[, $textbox] = $this->createPageWithTextBox();
		PradoUnit::invoke($textbox, 'setViewState', 'TextMode', 'Unknown');
		PradoUnit::invoke($textbox, 'setViewState', 'AutoCompleteType', 'Unknown');
		$output = $this->render($textbox);
		$this->assertStringContainsString('type="text"', $output);
		$this->assertStringNotContainsString('autocomplete', $output);
	}
}
