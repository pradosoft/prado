<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Collections\TList;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TButton;
use Prado\Web\UI\WebControls\TButtonTag;
use Prado\Web\UI\WebControls\TButtonType;
use Prado\Web\UI\WebControls\TDialog;
use Prado\Web\UI\WebControls\TLiteral;
use Prado\Web\UI\WebControls\TPanel;

class TButtonTest extends TestCase
{
	use TWebControlRenderTrait;

	private function createButton(): TButton
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_inFormRender', true);
		$button = new TButton();
		$button->setID('save');
		$page->getControls()->add($button);
		return $button;
	}

	public function testFormOverrideDefaults()
	{
		$button = new TButton();
		$this->assertSame('', $button->getFormAction());
		$this->assertSame('', $button->getFormEncType());
		$this->assertSame('', $button->getFormTarget());
		$output = $this->render($this->createButton());
		$this->assertStringNotContainsString('formaction', $output);
		$this->assertStringNotContainsString('formenctype', $output);
		$this->assertStringNotContainsString('formtarget', $output);
		$this->assertStringNotContainsString('formnovalidate', $output);
	}

	public function testFormOverridesRender()
	{
		$button = $this->createButton();
		$button->setFormAction(' /export ');
		$button->setFormEncType('Multipart/Form-Data');
		$button->setFormTarget('_blank');
		$output = $this->render($button);
		$this->assertStringContainsString('formaction="/export"', $output);
		$this->assertStringContainsString('formenctype="multipart/form-data"', $output);
		$this->assertStringContainsString('formtarget="_blank"', $output);
	}

	public function testNoValidationRendersFormNoValidate()
	{
		$button = $this->createButton();
		$button->setCausesValidation(false);
		$this->assertStringContainsString('formnovalidate="formnovalidate"', $this->render($button));
	}

	public function testOverridesRenderOnlyOnSubmitButtons()
	{
		$button = $this->createButton();
		$button->setButtonType(TButtonType::Button);
		$button->setFormAction('/export');
		$button->setCausesValidation(false);
		$output = $this->render($button);
		$this->assertStringNotContainsString('formaction', $output);
		$this->assertStringNotContainsString('formnovalidate', $output);
	}

	public function testCommandDefaults()
	{
		$button = new TButton();
		$this->assertSame('', $button->getCommandFor());
		$this->assertSame('', $button->getCommand());
		$this->assertSame(TButtonTag::Input, $button->getButtonTag());
		$this->assertSame(TButtonType::Submit, $button->getButtonType());
		$output = $this->render($this->createButton());
		$this->assertStringNotContainsString('commandfor', $output);
		$this->assertStringNotContainsString('command=', $output);
	}

	public function testCommandForDefaultsToButtonTagAndType()
	{
		$button = new TButton();
		$button->setCommandFor(' Dialog ');
		$this->assertSame('Dialog', $button->getCommandFor());
		$this->assertSame(TButtonTag::Button, $button->getButtonTag());
		$this->assertSame(TButtonType::Button, $button->getButtonType());

		$button->setButtonType(TButtonType::Reset);
		$this->assertSame(TButtonType::Reset, $button->getButtonType());
	}

	public function testCommandRendersOnButtonElement()
	{
		$button = $this->createButton();
		$dialog = $this->addDialog($button);
		$button->setText('Edit');
		$button->setCommandFor('editor');
		$button->setCommand(' show-modal ');
		$output = $this->render($button);
		$this->assertStringStartsWith('<button', $output);
		$this->assertStringContainsString('type="button"', $output);
		$this->assertStringContainsString('commandfor="' . $dialog->getClientID() . '"', $output);
		$this->assertStringContainsString('command="show-modal"', $output);
		$this->assertStringContainsString('>Edit</button>', $output);
		$this->assertStringNotContainsString('formnovalidate', $output);
	}

	public function testCommandWithoutCommandForRendersNothing()
	{
		$button = $this->createButton();
		$button->setCommand('close');
		$output = $this->render($button);
		$this->assertStringNotContainsString('command=', $output);
		$this->assertStringContainsString('type="submit"', $output);
	}

	public function testCommandForInvisibleControlIsOmitted()
	{
		$button = $this->createButton();
		$this->addDialog($button)->setVisible(false);
		$button->setCommandFor('editor');
		$button->setCommand('close');
		$output = $this->render($button);
		$this->assertStringNotContainsString('commandfor', $output);
		$this->assertStringNotContainsString('command=', $output);
	}

	public function testCommandForUnknownControlThrows()
	{
		$button = $this->createButton();
		$button->setCommandFor('missing');
		$this->expectException(TInvalidDataValueException::class);
		$this->render($button);
	}

	private function addDialog(TButton $button): TDialog
	{
		$dialog = new TDialog();
		$dialog->setID('editor');
		$button->getPage()->getControls()->add($dialog);
		return $dialog;
	}

	/**
	 * Returns a button on a page whose validator count for any group is $validators, with a client
	 * script manager that records registerPostBackControl().
	 */
	private function createScriptedButton(int $validators = 0): array
	{
		$page = $this->getMockBuilder(TPage::class)->onlyMethods(['getValidators'])->getMock();
		$page->method('getValidators')->willReturn(new TList(array_fill(0, $validators, 'v')));
		PradoUnit::setProp($page, '_inFormRender', true);
		$cs = $this->getMockBuilder(TClientScriptManager::class)->setConstructorArgs([$page])->onlyMethods(['registerPostBackControl'])->getMock();
		PradoUnit::setProp($page, '_clientScript', $cs);
		$button = new TButton();
		$button->setID('save');
		$page->getControls()->add($button);
		return [$button, $cs];
	}

	public function testPropertyDefaultsAndSetters()
	{
		$button = new TButton();
		$this->assertTrue($button->getEnableClientScript());
		$this->assertFalse($button->getIsDefaultButton());
		$this->assertSame('', $button->getText());
		$this->assertSame('', $button->getValidationGroup());
		$this->assertSame('input', PradoUnit::invoke($button, 'getTagName'));
		$this->assertSame('Prado.WebUI.TButton', PradoUnit::invoke($button, 'getClientClassName'));

		$button->setButtonTag('Button');
		$button->setEnableClientScript('false');
		$button->setIsDefaultButton('true');
		$button->setData('Save');
		$button->setValidationGroup('Group');
		$this->assertSame(TButtonTag::Button, $button->getButtonTag());
		$this->assertSame('button', PradoUnit::invoke($button, 'getTagName'));
		$this->assertFalse($button->getEnableClientScript());
		$this->assertTrue($button->getIsDefaultButton());
		$this->assertSame('Save', $button->getText());
		$this->assertSame('Save', $button->getData());
		$this->assertSame('Group', $button->getValidationGroup());
	}

	public function testInputRendersTheTextAsItsValue()
	{
		$button = $this->createButton();
		$button->setText('Save');
		$this->assertSame('<input type="submit" name="save" value="Save" id="save" />', $this->render($button));
	}

	public function testButtonTagRendersItsChildrenThenTheText()
	{
		$button = $this->createButton();
		$button->setButtonTag(TButtonTag::Button);
		$button->setText('Save');
		$icon = new TLiteral();
		$icon->setText('<i></i>');
		$button->getControls()->add($icon);
		$expected = '<button type="submit" name="save" id="save"><i></i>Save</button>';
		$this->assertSame($expected, $this->render($button));
		$this->assertSame($expected, $this->render($button));
		$this->assertCount(1, $button->getControls());
	}

	public function testButtonTagEncodesTheTextWhenEncode()
	{
		$button = $this->createButton();
		$button->setButtonTag(TButtonTag::Button);
		$button->setText('<b>a & b</b>');
		$this->assertFalse($button->getEncode());
		$this->assertSame('<button type="submit" name="save" id="save"><b>a & b</b></button>', $this->render($button));
		$button->setEncode(true);
		$this->assertTrue($button->getEncode());
		$this->assertSame('<button type="submit" name="save" id="save">&lt;b&gt;a & b&lt;/b&gt;</button>', $this->render($button));
		$button->setButtonTag(TButtonTag::Input);
		$this->assertSame('<input type="submit" name="save" value="&lt;b&gt;a & b&lt;/b&gt;" id="save" />', $this->render($button));
	}

	public function testDisabledParentRendersDisabled()
	{
		$page = new TPage();
		PradoUnit::setProp($page, '_inFormRender', true);
		$panel = new TPanel();
		$panel->setEnabled(false);
		$page->getControls()->add($panel);
		$button = new TButton();
		$panel->getControls()->add($button);
		$this->assertStringContainsString('disabled="disabled"', $this->render($button));
	}

	public function testValidatorsRegisterThePostBackControl()
	{
		[$button, $cs] = $this->createScriptedButton(1);
		$button->setValidationGroup('Group');
		$cs->expects($this->once())->method('registerPostBackControl')->with('Prado.WebUI.TButton', [
			'ID' => 'save',
			'CausesValidation' => true,
			'EventTarget' => 'save',
			'ValidationGroup' => 'Group',
		]);
		$this->assertStringContainsString('id="save"', $this->render($button));
	}

	public static function postBackScriptProvider(): array
	{
		// validators, causesValidation, buttonType, handler, isDefault, enableClientScript => registers
		return [
			'validators' => [1, true, TButtonType::Submit, null, false, true, true],
			'validators without validation' => [1, false, TButtonType::Submit, null, false, true, false],
			'no validators' => [0, true, TButtonType::Submit, null, false, true, false],
			'submit with OnClick' => [0, true, TButtonType::Submit, 'OnClick', false, true, false],
			'button with OnClick' => [0, true, TButtonType::Button, 'OnClick', false, true, true],
			'button with OnCommand' => [0, true, TButtonType::Button, 'OnCommand', false, true, true],
			'button without handlers' => [0, true, TButtonType::Button, null, false, true, false],
			'default button' => [0, true, TButtonType::Submit, null, true, true, true],
			'client script off' => [1, true, TButtonType::Submit, null, true, false, false],
		];
	}

	/**
	 * @dataProvider postBackScriptProvider
	 */
	public function testPostBackScriptConditions(int $validators, bool $causesValidation, string $type, ?string $handler, bool $isDefault, bool $clientScript, bool $registers)
	{
		[$button, $cs] = $this->createScriptedButton($validators);
		$button->setCausesValidation($causesValidation);
		$button->setButtonType($type);
		if ($handler !== null) {
			$button->attachEventHandler($handler, fn () => null);
		}
		$button->setIsDefaultButton($isDefault);
		$button->setEnableClientScript($clientScript);
		$cs->expects($registers ? $this->once() : $this->never())->method('registerPostBackControl');
		$this->render($button);
	}

	public function testRaisePostBackEventValidatesTheGroup()
	{
		$page = $this->createMock(TPage::class);
		$page->expects($this->once())->method('validate')->with('Group');
		$button = new TButton();
		$button->setPage($page);
		$button->setValidationGroup('Group');
		$button->raisePostBackEvent(null);
	}

	public function testRaisePostBackEventWithoutValidation()
	{
		$page = $this->createMock(TPage::class);
		$page->expects($this->never())->method('validate');
		$button = new TButton();
		$button->setPage($page);
		$button->setCausesValidation(false);
		$button->raisePostBackEvent(null);
	}
}
