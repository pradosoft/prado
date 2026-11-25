<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\TPage;
use Prado\IO\TTextWriter;
use Prado\Web\UI\WebControls\TCheckBox;
use Prado\Web\UI\WebControls\TPanel;
use Prado\Web\UI\WebControls\TRadioButton;
use Prado\Web\UI\WebControls\TTextAlign;

class TCheckBoxTest extends TestCase
{
	use TWebControlRenderTrait;

	private $_service;

	protected function setUp(): void
	{
		// the page's client script manager comes from the page service
		$app = \Prado\Prado::getApplication();
		$this->_service = $app->getService();
		$app->setService(new \Prado\Web\Services\TPageService());
	}

	protected function tearDown(): void
	{
		\Prado\Prado::getApplication()->setService($this->_service);
	}

	private function renderLabel(TCheckBox $checkBox, string $text): string
	{
		$tw = new TTextWriter();
		PradoUnit::invoke($checkBox, 'renderLabel', new THtmlWriter($tw), 'cb', $text);
		return $tw->flush();
	}

	public function testRenderLabelEncodesTextWhenEncode()
	{
		$checkBox = new TCheckBox();
		$this->assertFalse($checkBox->getEncode());
		$this->assertSame('<label for="cb"><b>a & b</b></label>', $this->renderLabel($checkBox, '<b>a & b</b>'));

		$checkBox->setEncode(true);
		$this->assertTrue($checkBox->getEncode());
		$this->assertSame('<label for="cb">&lt;b&gt;a & b&lt;/b&gt;</label>', $this->renderLabel($checkBox, '<b>a & b</b>'));
	}

	public function testRadioButtonInheritsEncode()
	{
		$radio = new TRadioButton();
		$radio->setEncode(true);
		$this->assertSame('<label for="cb">&lt;i&gt;</label>', $this->renderLabel($radio, '<i>'));
	}

	private function renderInput(TCheckBox $checkBox, string $clientID = 'cb'): string
	{
		$tw = new TTextWriter();
		PradoUnit::invoke($checkBox, 'renderInputTag', new THtmlWriter($tw), $clientID, '');
		return $tw->flush();
	}

	private function createCheckBox(string $class = TCheckBox::class): TCheckBox
	{
		$page = new TPage();
		$checkBox = new $class();
		$checkBox->setID('cb');
		$page->getControls()->add($checkBox);
		return $checkBox;
	}

	public function testIndeterminateAndSwitchDefaults()
	{
		$checkBox = new TCheckBox();
		$this->assertFalse($checkBox->getIndeterminate());
		$this->assertFalse($checkBox->getSwitch());
	}

	public function testIndeterminateRegistersScript()
	{
		$checkBox = $this->createCheckBox();
		$checkBox->setIndeterminate('true');
		$output = $this->renderInput($checkBox);
		$this->assertStringNotContainsString('indeterminate', $output);
		$cs = $checkBox->getPage()->getClientScript();
		$this->assertTrue($cs->isEndScriptRegistered('prado:indeterminate:cb'));
		$this->assertSame(
			'document.getElementById("cb").indeterminate = true;',
			PradoUnit::getProp($cs, '_endScripts')['prado:indeterminate:cb']
		);
	}

	public function testSwitchRendersRoleAndIgnoresIndeterminate()
	{
		$checkBox = $this->createCheckBox();
		$checkBox->setSwitch(true);
		$checkBox->setIndeterminate(true);
		$output = $this->renderInput($checkBox);
		$this->assertStringContainsString('switch="switch"', $output);
		$this->assertStringContainsString('role="switch"', $output);
		$this->assertFalse($checkBox->getPage()->getClientScript()->isEndScriptRegistered('prado:indeterminate:cb'));
	}

	public function testRadioButtonRendersNeither()
	{
		$radio = $this->createCheckBox(TRadioButton::class);
		$radio->setSwitch(true);
		$radio->setIndeterminate(true);
		$output = $this->renderInput($radio);
		$this->assertStringNotContainsString('switch', $output);
		$this->assertFalse($radio->getPage()->getClientScript()->isEndScriptRegistered('prado:indeterminate:cb'));
	}

	public function testChangedPostbackClearsIndeterminate()
	{
		$checkBox = new TCheckBox();
		$checkBox->setIndeterminate(true);
		$checkBox->loadPostData('cb', []);
		$this->assertTrue($checkBox->getIndeterminate());

		$checkBox->loadPostData('cb', ['cb' => 'on']);
		$this->assertTrue($checkBox->getChecked());
		$this->assertFalse($checkBox->getIndeterminate());
	}
	private function renderControl(TCheckBox $checkBox): string
	{
		PradoUnit::setProp($checkBox->getPage(), '_inFormRender', true);
		$tw = new TTextWriter();
		$checkBox->render(new THtmlWriter($tw));
		return $tw->flush();
	}

	/**
	 * A box that failed validation renders aria-invalid on its input element, which
	 * the client validator also updates, and not on the surrounding span.
	 */
	public function testInvalidRendersAriaInvalidOnTheInput()
	{
		foreach ([TCheckBox::class, TRadioButton::class] as $class) {
			$box = $this->createCheckBox($class);
			$box->setText('Agree');
			$this->assertStringNotContainsString('aria-invalid', $this->renderControl($box), $class);

			$box->setIsValid(false);
			$output = $this->renderControl($box);
			$this->assertSame(1, substr_count($output, 'aria-invalid'), $class);
			$this->assertMatchesRegularExpression('/<input [^>]*aria-invalid="true"/', $output, $class);
			$this->assertMatchesRegularExpression('/<span id="cb_parent">/', $output, $class);

			$box->setText('');
			$this->assertMatchesRegularExpression('/<input [^>]*aria-invalid="true"/', $this->renderControl($box), $class);
		}
	}

	/**
	 * The Aria collection renders on the input element, which carries the role and state.
	 */
	public function testAriaRendersOnTheInput()
	{
		foreach ([TCheckBox::class, TRadioButton::class] as $class) {
			$box = $this->createCheckBox($class);
			$box->setText('Agree');
			$box->getAria()['describedby'] = 'help';
			$output = $this->renderControl($box);
			$this->assertSame(1, substr_count($output, 'aria-describedby'), $class);
			$this->assertMatchesRegularExpression('/<input [^>]*aria-describedby="help"/', $output, $class);
		}
	}

	// --- Properties ---

	public function testPropertyDefaultsAndSetters()
	{
		$checkBox = new TCheckBox();
		$this->assertSame('', $checkBox->getText());
		$this->assertSame('', $checkBox->getValue());
		$this->assertSame(TTextAlign::Right, $checkBox->getTextAlign());
		$this->assertFalse($checkBox->getChecked());
		$this->assertFalse($checkBox->getAutoPostBack());
		$this->assertTrue($checkBox->getCausesValidation());
		$this->assertSame('', $checkBox->getValidationGroup());
		$this->assertTrue($checkBox->getEnableClientScript());
		$this->assertTrue($checkBox->getIsValid());
		$this->assertSame('span', $checkBox->getSurroundingTag());
		$this->assertSame('Prado.WebUI.TCheckBox', PradoUnit::invoke($checkBox, 'getClientClassName'));

		$checkBox->setText('Agree');
		$checkBox->setValue(5);
		$checkBox->setTextAlign('Left');
		$checkBox->setData('true');
		$checkBox->setAutoPostBack('true');
		$checkBox->setCausesValidation('false');
		$checkBox->setValidationGroup('Group');
		$checkBox->setEnableClientScript('false');
		$checkBox->setIsValid('false');
		$this->assertSame('Agree', $checkBox->getText());
		$this->assertSame('5', $checkBox->getValue());
		$this->assertSame(TTextAlign::Left, $checkBox->getTextAlign());
		$this->assertTrue($checkBox->getChecked());
		$this->assertTrue($checkBox->getData());
		$this->assertTrue($checkBox->getValidationPropertyValue());
		$this->assertTrue($checkBox->getAutoPostBack());
		$this->assertFalse($checkBox->getCausesValidation());
		$this->assertSame('Group', $checkBox->getValidationGroup());
		$this->assertFalse($checkBox->getEnableClientScript());
		$this->assertFalse($checkBox->getIsValid());
	}

	public function testSurroundingTagIdNeedsText()
	{
		$checkBox = $this->createCheckBox();
		$this->assertSame('cb', $checkBox->getSurroundingTagID());
		$checkBox->setText('Agree');
		$this->assertSame('cb_parent', $checkBox->getSurroundingTagID());
	}

	public function testLabelAndInputAttributesAreCreatedOnce()
	{
		$checkBox = new TCheckBox();
		$label = $checkBox->getLabelAttributes();
		$this->assertSame($label, $checkBox->getLabelAttributes());
		$input = $checkBox->getInputAttributes();
		$this->assertSame($input, $checkBox->getInputAttributes());
		$this->assertNotSame($label, $input);
	}

	// --- Post data ---

	public function testLoadPostDataChecksAndStoresTheValue()
	{
		$checkBox = new TCheckBox();
		$this->assertTrue($checkBox->loadPostData('cb', ['cb' => 'yes']));
		$this->assertTrue($checkBox->getChecked());
		$this->assertSame('yes', $checkBox->getValue());
		$this->assertTrue($checkBox->getDataChanged());
		$this->assertFalse($checkBox->loadPostData('cb', ['cb' => 'yes']));
		$this->assertFalse($checkBox->getDataChanged());
	}

	public function testLoadPostDataUnchecksWhenAbsent()
	{
		$checkBox = new TCheckBox();
		$checkBox->setChecked(true);
		$checkBox->setValue('yes');
		$this->assertTrue($checkBox->loadPostData('cb', []));
		$this->assertFalse($checkBox->getChecked());
		$this->assertSame('yes', $checkBox->getValue());
	}

	public function testUnchangedPostbackKeepsIndeterminate()
	{
		$checkBox = new TCheckBox();
		$checkBox->setIndeterminate(true);
		$this->assertFalse($checkBox->loadPostData('cb', []));
		$this->assertTrue($checkBox->getIndeterminate());
	}

	public function testRaisePostDataChangedEventRaisesOnCheckedChanged()
	{
		$checkBox = new TCheckBox();
		$raised = [];
		$checkBox->attachEventHandler('OnCheckedChanged', function ($sender, $param) use (&$raised) {
			$raised[] = [$sender, $param];
		});
		$checkBox->raisePostDataChangedEvent();
		$this->assertSame([[$checkBox, null]], $raised);
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
		$checkBox = new TCheckBox();
		$checkBox->setPage($page);
		$checkBox->setAutoPostBack($autoPostBack);
		$checkBox->setCausesValidation($causesValidation);
		$checkBox->setValidationGroup('Group');
		$checkBox->raisePostDataChangedEvent();
	}

	public function testOnPreRenderRequiresPostDataWhenEnabled()
	{
		$checkBox = $this->createCheckBox();
		$checkBox->onPreRender(null);
		$this->assertArrayHasKey('cb', PradoUnit::getProp($checkBox->getPage(), '_controlsRegisteredForPostData'));

		$checkBox = $this->createCheckBox();
		$checkBox->setEnabled(false);
		$checkBox->onPreRender(null);
		$this->assertArrayNotHasKey('cb', PradoUnit::getProp($checkBox->getPage(), '_controlsRegisteredForPostData'));
	}

	// --- Rendering ---

	public function testRenderWithoutTextRendersOnlyTheInput()
	{
		$checkBox = $this->createCheckBox();
		$checkBox->setEnableClientScript(false);
		$this->assertSame('<input id="cb" type="checkbox" name="cb" />', $this->renderControl($checkBox));
	}

	public function testRenderPlacesTheLabelByTextAlign()
	{
		$checkBox = $this->createCheckBox();
		$checkBox->setText('Agree');
		$checkBox->setChecked(true);
		$this->assertSame('<span id="cb_parent"><input id="cb" type="checkbox" name="cb" checked="checked" /><label for="cb">Agree</label></span>', $this->renderControl($checkBox));
		$checkBox->setTextAlign(TTextAlign::Left);
		$this->assertSame('<span id="cb_parent"><label for="cb">Agree</label><input id="cb" type="checkbox" name="cb" checked="checked" /></span>', $this->renderControl($checkBox));
	}

	public function testRenderPutsStyleToolTipAndAttributesOnTheSpanAndValueOnTheInput()
	{
		$checkBox = $this->createCheckBox();
		$checkBox->setText('Agree');
		$checkBox->setToolTip('Tip');
		$checkBox->setCssClass('box');
		$checkBox->setAttribute('rel', 'x');
		$checkBox->setAttribute('value', 'v');
		$checkBox->getLabelAttributes()->add('class', 'lbl');
		$checkBox->getInputAttributes()->add('data-in', '2');
		$this->assertSame('<span class="box" title="Tip" rel="x" id="cb_parent"><input id="cb" type="checkbox" value="v" name="cb" data-in="2" /><label for="cb" class="lbl">Agree</label></span>', $this->renderControl($checkBox));
		$this->assertSame('v', $checkBox->getAttribute('value'));
	}

	public function testRenderMovesOnClickToTheInputAndKeepsIt()
	{
		$checkBox = $this->createCheckBox();
		$checkBox->setAttribute('onclick', 'go()');
		$expected = '<input id="cb" type="checkbox" onclick="go()" name="cb" />';
		$this->assertSame($expected, $this->renderControl($checkBox));
		$this->assertSame('go()', $checkBox->getAttribute('onclick'));
		$this->assertSame($expected, $this->renderControl($checkBox));
	}

	public function testRenderWithOnlyTheValueAttribute()
	{
		$checkBox = $this->createCheckBox();
		$checkBox->setAttribute('value', 'v');
		$this->assertSame('<input id="cb" type="checkbox" value="v" name="cb" />', $this->renderControl($checkBox));
	}

	public function testValueAttributePrefersValueThenInputThenControlAttributes()
	{
		$checkBox = new TCheckBox();
		$this->assertSame('', PradoUnit::invoke($checkBox, 'getValueAttribute'));
		$checkBox->setAttribute('value', 'control');
		$this->assertSame('control', PradoUnit::invoke($checkBox, 'getValueAttribute'));
		$checkBox->getInputAttributes()->add('other', 'x');
		$this->assertSame('control', PradoUnit::invoke($checkBox, 'getValueAttribute'));
		$checkBox->getInputAttributes()->add('value', 'input');
		$this->assertSame('input', PradoUnit::invoke($checkBox, 'getValueAttribute'));
		$checkBox->setValue('value');
		$this->assertSame('value', PradoUnit::invoke($checkBox, 'getValueAttribute'));
	}

	public function testRenderInputTagWithoutClientIdOrUniqueId()
	{
		$checkBox = new TCheckBox();
		$checkBox->setPage(new TPage());
		$checkBox->setEnableClientScript(false);
		$this->assertSame('<input type="checkbox" />', $this->renderInput($checkBox, ''));
	}

	public function testRenderInputTagRendersDisabledAccessKeyAndTabIndex()
	{
		$page = new TPage();
		$panel = new TPanel();
		$panel->setEnabled(false);
		$page->getControls()->add($panel);
		$checkBox = new TCheckBox();
		$checkBox->setID('cb');
		$panel->getControls()->add($checkBox);
		$checkBox->setAccessKey('a');
		$checkBox->setTabIndex(3);
		$this->assertSame('<input id="cb" type="checkbox" name="cb" disabled="disabled" accesskey="a" tabindex="3" />', $this->renderInput($checkBox));
	}

	private function mockClientScript(TPage $page): TClientScriptManager
	{
		$cs = $this->getMockBuilder(TClientScriptManager::class)->setConstructorArgs([$page])->onlyMethods(['registerPostBackControl'])->getMock();
		PradoUnit::setProp($page, '_clientScript', $cs);
		return $cs;
	}

	public function testAutoPostBackRegistersThePostBackControl()
	{
		$checkBox = $this->createCheckBox();
		$checkBox->setAutoPostBack(true);
		$checkBox->setCausesValidation(false);
		$checkBox->setValidationGroup('Group');
		$this->mockClientScript($checkBox->getPage())->expects($this->once())->method('registerPostBackControl')->with('Prado.WebUI.TCheckBox', [
			'ID' => 'cb',
			'ValidationGroup' => 'Group',
			'CausesValidation' => false,
			'EventTarget' => 'cb',
		]);
		$this->renderInput($checkBox);
	}

	public static function clientScriptProvider(): array
	{
		// enabled, enableClientScript, autoPostBack, javascript => registers
		return [
			'no auto postback' => [true, true, false, true],
			'disabled' => [false, true, true, true],
			'client script off' => [true, false, true, true],
			'no javascript' => [true, true, true, false],
		];
	}

	/**
	 * @dataProvider clientScriptProvider
	 */
	public function testPostBackControlConditions(bool $enabled, bool $clientScript, bool $autoPostBack, bool $javascript)
	{
		$checkBox = $this->createCheckBox();
		$checkBox->setEnabled($enabled);
		$checkBox->setEnableClientScript($clientScript);
		$checkBox->setAutoPostBack($autoPostBack);
		$checkBox->getPage()->setClientSupportsJavaScript($javascript);
		$this->mockClientScript($checkBox->getPage())->expects($this->never())->method('registerPostBackControl');
		$this->renderInput($checkBox);
	}

	public function testTagNameIsInput()
	{
		$this->assertSame('input', PradoUnit::invoke(new TCheckBox(), 'getTagName'));
	}

	public function testLoadPostDataIgnoresAnArrayValue()
	{
		$checkBox = new TCheckBox();
		$checkBox->setValue('yes');
		$this->assertFalse($checkBox->loadPostData('cb', ['cb' => ['x']]));
		$this->assertFalse($checkBox->getChecked());
		$this->assertSame('yes', $checkBox->getValue());
	}
}
