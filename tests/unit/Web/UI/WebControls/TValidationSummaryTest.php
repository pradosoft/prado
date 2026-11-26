<?php


namespace Prado\Test\Unit\Web\UI\WebControls;

use Prado\Web\UI\WebControls\TRequiredFieldValidator;
use Prado\Web\UI\WebControls\TTextBox;
use Prado\Web\UI\WebControls\TValidationSummary;
use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TAssetManagerMockTrait;
use Prado\Test\Unit\Harness\Traits\TFormPageTrait;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Web\UI\WebControls\TClientSideValidationSummaryOptions;
use Prado\Web\UI\WebControls\TValidationSummaryDisplayMode;
use Prado\Web\UI\WebControls\TValidationSummaryDisplayStyle;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\Javascripts\TJavaScript;

class TValidationSummaryTest extends TestCase
{
	use TAssetManagerMockTrait;
	use TFormPageTrait;
	use TWebControlRenderTrait;

	private string $_mode;

	protected function setUp(): void
	{
		$this->mockAssetManager(['getBaseUrl'])->method('getBaseUrl')->willReturn('');
		// Outside Debug mode, the client script manager writes minified scripts to the published path.
		$this->_mode = \Prado\Prado::getApplication()->getMode();
		\Prado\Prado::getApplication()->setMode(\Prado\TApplicationMode::Debug);
	}

	protected function tearDown(): void
	{
		\Prado\Prado::getApplication()->setMode($this->_mode);
		$this->restoreAssetManager();
	}

	private function createSummary($id = 'summary1')
	{
		$page = new \Prado\Web\UI\TPage();
		$summary = new TValidationSummary();
		$summary->setID($id);
		$page->getControls()->add($summary);
		return $summary;
	}

	// ================================================================================
	// Accessibility Rendering Tests
	// ================================================================================

	public function testRenderAddsAlertRole()
	{
		$summary = $this->createSummary();
		$html = $this->renderBeginTag($summary);
		$this->assertStringContainsString('role="alert"', $html);
	}

	public function testRenderIncludesClientId()
	{
		$summary = $this->createSummary('mySummary');
		$html = $this->renderBeginTag($summary);
		$this->assertStringContainsString('id="' . $summary->getClientID() . '"', $html);
	}

	/**
	 * Adds a failed validator with the message to the summary's page.
	 */
	private function addFailedValidator(TValidationSummary $summary, string $id, string $message, bool $encode): TRequiredFieldValidator
	{
		$page = $summary->getPage();
		$textbox = new TTextBox();
		$textbox->setID($id . 'Input');
		$page->getControls()->add($textbox);
		$validator = new TRequiredFieldValidator();
		$validator->setID($id);
		$validator->setControlToValidate($textbox->getID());
		$validator->setErrorMessage($message);
		$validator->setEncode($encode);
		$page->getControls()->add($validator);
		$validator->onInit(null);
		$validator->setIsValid(false);
		return $validator;
	}

	/**
	 * A message is HTML unless its validator's Encode is true.
	 */
	public function testErrorMessagesEncodedPerValidator()
	{
		$summary = $this->createSummary();
		$this->addFailedValidator($summary, 'raw', '<b>raw</b>', false);
		$this->addFailedValidator($summary, 'enc', '<b>enc</b>', true);

		$this->assertSame(['<b>raw</b>', '&lt;b&gt;enc&lt;/b&gt;'], PradoUnit::invoke($summary, 'getErrorMessages'));

		$tw = new \Prado\IO\TTextWriter();
		PradoUnit::invoke($summary, 'renderBulletList', new \Prado\Web\UI\THtmlWriter($tw));
		$html = $tw->flush();
		$this->assertStringContainsString('<li><b>raw</b></li>', $html);
		$this->assertStringContainsString('<li>&lt;b&gt;enc&lt;/b&gt;</li>', $html);
	}

	/**
	 * Builds a form page with a summary `Summary` that does not register client script.
	 */
	private function createFormSummary(): TValidationSummary
	{
		$summary = new TValidationSummary();
		$summary->setID('Summary');
		$this->createFormPage($summary);
		return $summary;
	}

	/**
	 * Renders the summary's contents with the client script disabled.
	 * @param TValidationSummary $summary the summary.
	 * @return string the rendered contents.
	 */
	private function renderMessages(TValidationSummary $summary): string
	{
		$summary->setEnableClientScript(false);
		return $this->renderContents($summary);
	}

	public function testDefaults()
	{
		$summary = new TValidationSummary();
		$this->assertSame('red', $summary->getForeColor());
		$this->assertSame(TValidationSummaryDisplayStyle::Fixed, $summary->getDisplay());
		$this->assertSame('', $summary->getHeaderText());
		$this->assertSame(TValidationSummaryDisplayMode::BulletList, $summary->getDisplayMode());
		$this->assertTrue($summary->getEnableClientScript());
		$this->assertFalse($summary->getShowMessageBox());
		$this->assertTrue($summary->getShowSummary());
		$this->assertTrue($summary->getScrollToSummary());
		$this->assertFalse($summary->getShowAnchor());
		$this->assertTrue($summary->getAutoUpdate());
		$this->assertSame('', $summary->getValidationGroup());
	}

	public function testProperties()
	{
		$summary = new TValidationSummary();
		$summary->setDisplay('Dynamic');
		$this->assertSame(TValidationSummaryDisplayStyle::Dynamic, $summary->getDisplay());
		$summary->setHeaderText('Errors:');
		$this->assertSame('Errors:', $summary->getHeaderText());
		$summary->setDisplayMode('SimpleList');
		$this->assertSame(TValidationSummaryDisplayMode::SimpleList, $summary->getDisplayMode());
		$summary->setEnableClientScript('false');
		$this->assertFalse($summary->getEnableClientScript());
		$summary->setShowMessageBox('true');
		$this->assertTrue($summary->getShowMessageBox());
		$summary->setShowSummary('false');
		$this->assertFalse($summary->getShowSummary());
		$summary->setScrollToSummary('false');
		$this->assertFalse($summary->getScrollToSummary());
		$summary->setShowAnchor('true');
		$this->assertTrue($summary->getShowAnchor());
		$summary->setAutoUpdate('false');
		$this->assertFalse($summary->getAutoUpdate());
		$summary->setValidationGroup('Group');
		$this->assertSame('Group', $summary->getValidationGroup());
	}

	public function testClientSideIsCreatedOnce()
	{
		$summary = new TValidationSummary();
		$clientSide = $summary->getClientSide();
		$this->assertInstanceOf(TClientSideValidationSummaryOptions::class, $clientSide);
		$this->assertSame($clientSide, $summary->getClientSide());
	}

	public static function provideDisplay(): array
	{
		$hidden = [
			TValidationSummaryDisplayStyle::Fixed => 'visibility:hidden',
			TValidationSummaryDisplayStyle::Dynamic => 'display:none',
			TValidationSummaryDisplayStyle::None => 'display:none',
		];
		$data = [];
		foreach ($hidden as $display => $style) {
			$data["$display, failure"] = [$display, true, true, null];
			$data["$display, no failure"] = [$display, false, true, $style];
			$data["$display, failure, disabled"] = [$display, true, false, $style];
		}
		return $data;
	}

	/**
	 * @param string $display the display style.
	 * @param bool $failed whether a validator failed.
	 * @param bool $enabled whether the summary is enabled.
	 * @param ?string $style the expected hiding style, or null when the summary shows.
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('provideDisplay')]
	public function testRenderHidesTheSummaryWithoutFailure(string $display, bool $failed, bool $enabled, ?string $style)
	{
		$summary = $this->createSummary();
		$summary->setDisplay($display);
		$summary->setEnabled($enabled);
		$validator = $this->addFailedValidator($summary, 'name', 'Name is required.', false);
		$validator->setIsValid(!$failed);
		$html = $this->renderBeginTag($summary);
		if ($style === null) {
			$this->assertStringNotContainsString('display:none', $html);
			$this->assertStringNotContainsString('visibility:hidden', $html);
		} else {
			$this->assertStringContainsString($style, $html);
		}
	}

	public function testErrorMessagesOfTheValidationGroup()
	{
		$summary = $this->createSummary();
		$this->addFailedValidator($summary, 'none', 'No group.', false);
		$this->addFailedValidator($summary, 'empty', '', false);
		$this->addFailedValidator($summary, 'grouped', 'Grouped.', false)->setValidationGroup('Group');
		$this->addFailedValidator($summary, 'valid', 'Valid.', false)->setIsValid(true);

		$this->assertSame(['No group.', 'Grouped.'], PradoUnit::invoke($summary, 'getErrorMessages'), 'Without a group, the summary shows every group.');
		$summary->setValidationGroup('Group');
		$this->assertSame(['Grouped.'], PradoUnit::invoke($summary, 'getErrorMessages'));
		$this->assertTrue(PradoUnit::invoke($summary, 'getValidationFailed'));
		$summary->setValidationGroup('Other');
		$this->assertSame([], PradoUnit::invoke($summary, 'getErrorMessages'));
		$this->assertFalse(PradoUnit::invoke($summary, 'getValidationFailed'));
	}

	public static function provideDisplayModes(): array
	{
		return [
			'bullet list' => [TValidationSummaryDisplayMode::BulletList, "Errors:<ul>\n<li>First.</li>\n<li>Second.</li>\n</ul>\n", 'Errors:'],
			'simple list' => [TValidationSummaryDisplayMode::SimpleList, "Errors:<br/>\nFirst.<br/>\nSecond.<br/>\n", "Errors:<br/>\n"],
			'single paragraph' => [TValidationSummaryDisplayMode::SingleParagraph, 'Errors: First. Second.', 'Errors:'],
			'header only' => [TValidationSummaryDisplayMode::HeaderOnly, 'Errors:', 'Errors:'],
		];
	}

	/**
	 * @param string $mode the display mode.
	 * @param string $expected the rendered messages.
	 * @param string $withoutMessages the rendering without failed validators.
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('provideDisplayModes')]
	public function testRenderContentsInTheDisplayMode(string $mode, string $expected, string $withoutMessages)
	{
		$summary = $this->createFormSummary();
		$summary->setDisplayMode($mode);
		$summary->setHeaderText('Errors:');
		$this->assertSame($withoutMessages, $this->renderMessages($summary));

		$this->addFailedValidator($summary, 'first', 'First.', false);
		$this->addFailedValidator($summary, 'second', 'Second.', false);
		$this->assertSame($expected, $this->renderMessages($summary));
	}

	public function testRenderContentsOfASimpleListWithoutHeader()
	{
		$summary = $this->createFormSummary();
		$summary->setDisplayMode(TValidationSummaryDisplayMode::SimpleList);
		$this->addFailedValidator($summary, 'first', 'First.', false);
		$this->assertSame("First.<br/>\n", $this->renderMessages($summary));
	}

	public function testRenderContentsWithoutShowSummary()
	{
		$summary = $this->createFormSummary();
		$summary->setShowSummary(false);
		$this->addFailedValidator($summary, 'first', 'First.', false);
		$this->assertSame('', $this->renderMessages($summary));
	}

	public function testRenderRegistersTheValidationManagerAndTheSummary()
	{
		$summary = $this->createFormSummary();
		$page = $summary->getPage();
		$this->renderContents($summary);
		$this->assertTrue(isset(PradoUnit::getProp($page->getClientScript(), '_registeredScripts')['validator']));
		$this->assertSame('new Prado.ValidationManager(' . TJavaScript::encode(['FormID' => 'Form']) . ');', $this->getEndScript($page, 'TBaseValidator:Form'));
		$options = ['ID' => 'Summary', 'FormID' => 'Form', 'ScrollToSummary' => true, 'HeaderText' => '', 'DisplayMode' => 'BulletList', 'Refresh' => true, 'ValidationGroup' => '', 'Display' => 'Fixed'];
		$this->assertSame('new Prado.WebUI.TValidationSummary(' . TJavaScript::encode($options) . ');', $this->getEndScript($page, 'Summary'));
	}

	public function testRenderKeepsARegisteredValidationManager()
	{
		$summary = $this->createFormSummary();
		$page = $summary->getPage();
		$page->getClientScript()->registerEndScript('TBaseValidator:Form', 'manager');
		$this->renderContents($summary);
		$this->assertSame('manager', $this->getEndScript($page, 'TBaseValidator:Form'));
		$this->assertNotNull($this->getEndScript($page, 'Summary'));
	}

	public function testRenderOfADisabledSummaryRegistersNothing()
	{
		foreach (['setEnabled', 'setEnableClientScript'] as $setter) {
			$summary = $this->createFormSummary();
			$summary->$setter(false);
			$page = $summary->getPage();
			$this->renderContents($summary);
			$this->assertNull($this->getEndScript($page, 'TBaseValidator:Form'), $setter);
			$this->assertNull($this->getEndScript($page, 'Summary'), $setter);
		}
	}

	public function testClientScriptOptions()
	{
		$summary = $this->createFormSummary();
		$summary->setShowMessageBox(true);
		$summary->setShowSummary(false);
		$summary->setScrollToSummary(false);
		$summary->setHeaderText('Errors:');
		$summary->setDisplayMode(TValidationSummaryDisplayMode::SingleParagraph);
		$summary->setAutoUpdate(false);
		$summary->setValidationGroup('Group');
		$summary->setDisplay(TValidationSummaryDisplayStyle::Dynamic);
		$summary->getClientSide()->setOnShowSummary('show()');
		$this->assertSame([
			'ID' => 'Summary',
			'FormID' => 'Form',
			'ShowMessageBox' => true,
			'ShowSummary' => false,
			'ScrollToSummary' => false,
			'HeaderText' => 'Errors:',
			'DisplayMode' => TValidationSummaryDisplayMode::SingleParagraph,
			'Refresh' => false,
			'ValidationGroup' => 'Group',
			'Display' => TValidationSummaryDisplayStyle::Dynamic,
			'OnShowSummary' => $summary->getClientSide()->getOnShowSummary(),
		], PradoUnit::invoke($summary, 'getClientScriptOptions'));
	}

	public static function provideClientScriptOptionFlags(): array
	{
		$data = [];
		foreach ([false, true] as $messageBox) {
			foreach ([true, false] as $showSummary) {
				foreach ([false, true] as $clientSide) {
					$data[sprintf('message box %d, summary %d, client side %d', $messageBox, $showSummary, $clientSide)] = [$messageBox, $showSummary, $clientSide];
				}
			}
		}
		return $data;
	}

	/**
	 * @param bool $messageBox the ShowMessageBox value.
	 * @param bool $showSummary the ShowSummary value.
	 * @param bool $clientSide whether the summary has client side options.
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('provideClientScriptOptionFlags')]
	public function testClientScriptOptionFlags(bool $messageBox, bool $showSummary, bool $clientSide)
	{
		$summary = $this->createFormSummary();
		$summary->setShowMessageBox($messageBox);
		$summary->setShowSummary($showSummary);
		if ($clientSide) {
			$summary->getClientSide()->setOnHideSummary('hide()');
		}
		$options = PradoUnit::invoke($summary, 'getClientScriptOptions');
		$this->assertSame($messageBox ?: null, $options['ShowMessageBox'] ?? null);
		$this->assertSame($showSummary ? null : false, $options['ShowSummary'] ?? null);
		$this->assertSame($clientSide, isset($options['OnHideSummary']));
	}

	public static function provideMessageCounts(): array
	{
		$expected = [
			TValidationSummaryDisplayMode::BulletList => ['', 'H', "<ul>\n<li>M1</li>\n</ul>\n", "<ul>\n<li>M1</li>\n<li>M2</li>\n</ul>\n"],
			TValidationSummaryDisplayMode::SimpleList => ['', "H<br/>\n", "M1<br/>\n", "M1<br/>\nM2<br/>\n"],
			TValidationSummaryDisplayMode::SingleParagraph => ['', 'H', ' M1', ' M1 M2'],
			TValidationSummaryDisplayMode::HeaderOnly => ['', 'H', '', ''],
		];
		$data = [];
		foreach ($expected as $mode => [$none, $headerNone, $one, $two]) {
			$data["$mode, no header, no message"] = [$mode, '', 0, $none];
			$data["$mode, header, no message"] = [$mode, 'H', 0, $headerNone];
			$data["$mode, no header, one message"] = [$mode, '', 1, $one];
			$data["$mode, no header, two messages"] = [$mode, '', 2, $two];
		}
		return $data;
	}

	/**
	 * @param string $mode the display mode.
	 * @param string $header the header text.
	 * @param int $count the number of failed validators.
	 * @param string $expected the rendered messages.
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('provideMessageCounts')]
	public function testRenderMessageCounts(string $mode, string $header, int $count, string $expected)
	{
		$summary = $this->createFormSummary();
		$summary->setDisplayMode($mode);
		$summary->setHeaderText($header);
		for ($i = 1; $i <= $count; $i++) {
			$this->addFailedValidator($summary, 'v' . $i, 'M' . $i, false);
		}
		$this->assertSame($expected, $this->renderMessages($summary));
	}

	public function testValidationFailedWithOnlyValidValidators()
	{
		$summary = $this->createSummary();
		$this->assertFalse(PradoUnit::invoke($summary, 'getValidationFailed'), 'No validator.');
		$this->addFailedValidator($summary, 'one', 'One.', false)->setIsValid(true);
		$this->addFailedValidator($summary, 'two', 'Two.', false)->setIsValid(true);
		$this->assertFalse(PradoUnit::invoke($summary, 'getValidationFailed'));
		$this->assertSame([], PradoUnit::invoke($summary, 'getErrorMessages'));
	}

	public function testValidationFailedWithOneValidValidator()
	{
		$summary = $this->createSummary();
		$this->addFailedValidator($summary, 'one', 'One.', false)->setIsValid(true);
		$this->assertFalse(PradoUnit::invoke($summary, 'getValidationFailed'));
	}

	public static function provideOneValidator(): array
	{
		return [
			'valid' => [true, 'Message.', false, []],
			'failed, no message' => [false, '', false, []],
			'failed' => [false, '<b>Message</b>', false, ['<b>Message</b>']],
			'failed, encoded' => [false, '<b>Message</b>', true, ['&lt;b&gt;Message&lt;/b&gt;']],
		];
	}

	/**
	 * @param bool $isValid whether the validator is valid.
	 * @param string $message the error message.
	 * @param bool $encode whether the message is encoded.
	 * @param string[] $expected the summary's messages.
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('provideOneValidator')]
	public function testErrorMessagesOfOneValidator(bool $isValid, string $message, bool $encode, array $expected)
	{
		$summary = $this->createSummary();
		$this->addFailedValidator($summary, 'one', $message, $encode)->setIsValid($isValid);
		$this->assertSame($expected, PradoUnit::invoke($summary, 'getErrorMessages'));
	}

	public function testErrorMessagesOfAValidatorThatIsNotABaseValidator()
	{
		$summary = $this->createSummary();
		$summary->getPage()->getValidators()->add(new TValidationSummaryTestValidator());
		$this->assertSame(['<b>Other</b>'], PradoUnit::invoke($summary, 'getErrorMessages'), 'A validator without Encode is not encoded.');
		$this->assertTrue(PradoUnit::invoke($summary, 'getValidationFailed'));
	}
}

/**
 * A failed validator that is not a TBaseValidator.
 */
class TValidationSummaryTestValidator implements \Prado\Web\UI\IValidator
{
	public function validate()
	{
		return false;
	}

	public function getIsValid()
	{
		return false;
	}

	public function setIsValid($value)
	{
	}

	public function getErrorMessage()
	{
		return '<b>Other</b>';
	}

	public function setErrorMessage($value)
	{
	}

	public function getValidationGroup()
	{
		return '';
	}
}
