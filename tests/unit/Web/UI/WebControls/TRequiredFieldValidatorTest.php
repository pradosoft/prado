<?php

use Prado\Web\UI\WebControls\TRequiredFieldValidator;
use Prado\Web\UI\WebControls\TTextBox;
use Prado\Web\UI\WebControls\TDropDownList;
use Prado\Web\UI\WebControls\TListItem;
use Prado\Web\UI\WebControls\TRadioButton;
use Prado\Web\UI\THtmlWriter;
use Prado\IO\TTextWriter;
use Prado\Exceptions\TConfigurationException;
use PHPUnit\Framework\TestCase;

class TRequiredFieldValidatorTest extends TestCase
{
	private function createPageWithControls()
	{
		$page = new \Prado\Web\UI\TPage();
		$textbox = new TTextBox();
		$textbox->setID('input1');
		$page->getControls()->add($textbox);
		return [$page, $textbox];
	}

	private function createValidator($page, $target, $id = 'v1')
	{
		$validator = new TRequiredFieldValidator();
		$validator->setID($id);
		$validator->setControlToValidate($target->getID());
		$page->getControls()->add($validator);
		return $validator;
	}

	private function render($control)
	{
		$tw = new TTextWriter();
		$writer = new THtmlWriter($tw);
		$control->render($writer);
		return $tw->flush();
	}

	private function invokeEvaluateIsValid($validator)
	{
		$method = new ReflectionMethod($validator, 'evaluateIsValid');
		$method->setAccessible(true);
		return $method->invoke($validator);
	}

	private function invokeGetClientScriptOptions($validator)
	{
		$method = new ReflectionMethod($validator, 'getClientScriptOptions');
		$method->setAccessible(true);
		return $method->invoke($validator);
	}

	private function getControlPromptValue($validator)
	{
		$method = new ReflectionMethod($validator, 'getControlPromptValue');
		$method->setAccessible(true);
		return $method->invoke($validator);
	}

	private function getClientClassName($validator)
	{
		$method = new ReflectionMethod($validator, 'getClientClassName');
		$method->setAccessible(true);
		return $method->invoke($validator);
	}

	// ================================================================================
	// Constructor and Default State Tests
	// ================================================================================

	public function testSetForeColorToRed()
	{
		$validator = new TRequiredFieldValidator();
		$this->assertEquals('red', $validator->getForeColor());
	}

	public function testExtendsTBaseValidator()
	{
		$validator = new TRequiredFieldValidator();
		$this->assertInstanceOf(\Prado\Web\UI\WebControls\TBaseValidator::class, $validator);
	}

	// ================================================================================
	// InitialValue Property Tests
	// ================================================================================

	public function testSetInitialValue()
	{
		[$page, $textbox] = $this->createPageWithControls();
		$validator = $this->createValidator($page, $textbox);
		$validator->setInitialValue('expected');
		$this->assertEquals('expected', $validator->getInitialValue());
	}

	public function testGetEmptyInitialValue()
	{
		[$page, $textbox] = $this->createPageWithControls();
		$validator = $this->createValidator($page, $textbox);
		$this->assertEquals('', $validator->getInitialValue());
	}

	public function testInitialValueWithWhitespace()
	{
		[$page, $textbox] = $this->createPageWithControls();
		$validator = $this->createValidator($page, $textbox);
		$validator->setInitialValue('  trimmed  ');
		$this->assertEquals('  trimmed  ', $validator->getInitialValue());
	}

	public function testInitialValueWithPromptValue()
	{
		$page = new \Prado\Web\UI\TPage();
		$dropdown = new TDropDownList();
		$dropdown->setID('dropdown1');
		$dropdown->setPromptText('Select...');
		$dropdown->setPromptValue('prompt');
		$page->getControls()->add($dropdown);

		$validator = $this->createValidator($page, $dropdown);

		$this->assertEquals('prompt', $this->getControlPromptValue($validator));
		$this->assertEquals('prompt', $validator->getInitialValue());
	}

	public function testInitialValueOverridesPromptValue()
	{
		$page = new \Prado\Web\UI\TPage();
		$dropdown = new TDropDownList();
		$dropdown->setID('dropdown1');
		$dropdown->setPromptText('Select...');
		$dropdown->setPromptValue('prompt');
		$page->getControls()->add($dropdown);

		$validator = $this->createValidator($page, $dropdown);
		$validator->setInitialValue('custom');

		$this->assertEquals('custom', $validator->getInitialValue());
	}

	// ================================================================================
	// Client Class Name Tests
	// ================================================================================

	public function testGetClientClassName()
	{
		[$page, $textbox] = $this->createPageWithControls();
		$validator = $this->createValidator($page, $textbox);
		$this->assertEquals('Prado.WebUI.TRequiredFieldValidator', $this->getClientClassName($validator));
	}

	// ================================================================================
	// Standard Control Validation Tests
	// ================================================================================

	public function testValidateEmptyControl()
	{
		[$page, $textbox] = $this->createPageWithControls();
		$validator = $this->createValidator($page, $textbox);
		$validator->setInitialValue('');

		$this->assertFalse($this->invokeEvaluateIsValid($validator));
	}

	public function testValidateNonEmptyControl()
	{
		[$page, $textbox] = $this->createPageWithControls();
		$textbox->setText('some value');
		$validator = $this->createValidator($page, $textbox);
		$validator->setInitialValue('');

		$this->assertTrue($this->invokeEvaluateIsValid($validator));
	}

	public function testValidateControlSameAsInitialValue()
	{
		[$page, $textbox] = $this->createPageWithControls();
		$textbox->setText('expected');
		$validator = $this->createValidator($page, $textbox);
		$validator->setInitialValue('expected');

		$this->assertFalse($this->invokeEvaluateIsValid($validator));
	}

	public function testValidateControlDifferentFromInitialValue()
	{
		[$page, $textbox] = $this->createPageWithControls();
		$textbox->setText('new value');
		$validator = $this->createValidator($page, $textbox);
		$validator->setInitialValue('expected');

		$this->assertTrue($this->invokeEvaluateIsValid($validator));
	}

	public function testValidateControlWhitespaceInitialValue()
	{
		[$page, $textbox] = $this->createPageWithControls();
		$textbox->setText('some value');
		$validator = $this->createValidator($page, $textbox);
		$validator->setInitialValue('   ');

		$this->assertTrue($this->invokeEvaluateIsValid($validator));
	}

	public function testValidateControlWhitespaceInValue()
	{
		[$page, $textbox] = $this->createPageWithControls();
		$textbox->setText('   ');
		$validator = $this->createValidator($page, $textbox);
		$validator->setInitialValue('');

		$this->assertFalse($this->invokeEvaluateIsValid($validator));
	}

	public function testValidateControlBooleanTrueValue()
	{
		[$page, $textbox] = $this->createPageWithControls();
		$textbox->setText('true');
		$validator = $this->createValidator($page, $textbox);
		$validator->setInitialValue('');

		$this->assertTrue($this->invokeEvaluateIsValid($validator));
	}

	// ================================================================================
	// ListControl Validation Tests
	// ================================================================================

	public function testValidateListControlWithSelection()
	{
		$page = new \Prado\Web\UI\TPage();
		$dropdown = new TDropDownList();
		$dropdown->setID('dropdown1');
		$dropdown->getItems()->add(new TListItem('Select...', ''));
		$dropdown->getItems()->add(new TListItem('Option 1', '1'));
		$dropdown->getItems()->add(new TListItem('Option 2', '2'));
		$page->getControls()->add($dropdown);

		$validator = $this->createValidator($page, $dropdown);
		$validator->setInitialValue('');

		$dropdown->setSelectedIndex(2);

		$this->assertTrue($this->invokeEvaluateIsValid($validator));
	}

	public function testValidateListControlSameAsInitialValue()
	{
		$page = new \Prado\Web\UI\TPage();
		$dropdown = new TDropDownList();
		$dropdown->setID('dropdown1');
		$dropdown->getItems()->add(new TListItem('Select...', 'select'));
		$dropdown->getItems()->add(new TListItem('Option 1', '1'));
		$page->getControls()->add($dropdown);

		$validator = $this->createValidator($page, $dropdown);
		$validator->setInitialValue('select');

		$dropdown->setSelectedIndex(0);

		$this->assertFalse($this->invokeEvaluateIsValid($validator));
	}

	public function testValidateListControlDifferentFromInitialValue()
	{
		$page = new \Prado\Web\UI\TPage();
		$dropdown = new TDropDownList();
		$dropdown->setID('dropdown1');
		$dropdown->getItems()->add(new TListItem('Select...', 'select'));
		$dropdown->getItems()->add(new TListItem('Option 1', '1'));
		$page->getControls()->add($dropdown);

		$validator = $this->createValidator($page, $dropdown);
		$validator->setInitialValue('select');

		$dropdown->setSelectedIndex(1);

		$this->assertTrue($this->invokeEvaluateIsValid($validator));
	}

	// ================================================================================
	// Enabled State Tests
	// =============================================================================

	public function testDisabledValidatorIsAlwaysValid()
	{
		[$page, $textbox] = $this->createPageWithControls();
		$validator = $this->createValidator($page, $textbox);
		$validator->setInitialValue('');
		$validator->setEnabled(false);

		$textbox->setText('');

		$result = $validator->validate();
		$this->assertTrue($result);
		$this->assertTrue($validator->getIsValid());
	}

	// ================================================================================
	// Validate Method Integration Tests
	// ================================================================================

	public function testValidateMethod()
	{
		[$page, $textbox] = $this->createPageWithControls();
		$validator = $this->createValidator($page, $textbox);
		$validator->setInitialValue('');
		$textbox->setText('value');

		$this->assertTrue($validator->validate());
		$this->assertTrue($validator->getIsValid());
	}

	public function testValidateMethodFails()
	{
		[$page, $textbox] = $this->createPageWithControls();
		$validator = $this->createValidator($page, $textbox);
		$validator->setInitialValue('');
		$textbox->setText('');

		$this->assertFalse($validator->validate());
		$this->assertFalse($validator->getIsValid());
	}

	// ================================================================================
	// Display Style Tests
	// ================================================================================

	public function testDisplayStyleDynamic()
	{
		$validator = new TRequiredFieldValidator();
		$validator->setDisplay(\Prado\Web\UI\WebControls\TValidatorDisplayStyle::Dynamic);
		$this->assertEquals(\Prado\Web\UI\WebControls\TValidatorDisplayStyle::Dynamic, $validator->getDisplay());
	}

	public function testDisplayStyleNone()
	{
		$validator = new TRequiredFieldValidator();
		$validator->setDisplay(\Prado\Web\UI\WebControls\TValidatorDisplayStyle::None);
		$this->assertEquals(\Prado\Web\UI\WebControls\TValidatorDisplayStyle::None, $validator->getDisplay());
	}

	public function testDisplayStyleFixed()
	{
		$validator = new TRequiredFieldValidator();
		$validator->setDisplay(\Prado\Web\UI\WebControls\TValidatorDisplayStyle::Fixed);
		$this->assertEquals(\Prado\Web\UI\WebControls\TValidatorDisplayStyle::Fixed, $validator->getDisplay());
	}

	// ================================================================================
	// FocusOnError Tests
	// ================================================================================

	public function testFocusOnErrorDefault()
	{
		$validator = new TRequiredFieldValidator();
		$this->assertFalse($validator->getFocusOnError());
	}

	public function testSetFocusOnError()
	{
		$validator = new TRequiredFieldValidator();
		$validator->setFocusOnError(true);
		$this->assertTrue($validator->getFocusOnError());
	}

	// ================================================================================
	// ValidationGroup Tests
	// ================================================================================

	public function testValidationGroupDefault()
	{
		$validator = new TRequiredFieldValidator();
		$this->assertEquals('', $validator->getValidationGroup());
	}

	public function testSetValidationGroup()
	{
		$validator = new TRequiredFieldValidator();
		$validator->setValidationGroup('group1');
		$this->assertEquals('group1', $validator->getValidationGroup());
	}

	// ================================================================================
	// ControlCssClass Tests
	// ================================================================================

	public function testControlCssClassDefault()
	{
		$validator = new TRequiredFieldValidator();
		$this->assertEquals('', $validator->getControlCssClass());
	}

	public function testSetControlCssClass()
	{
		$validator = new TRequiredFieldValidator();
		$validator->setControlCssClass('error-input');
		$this->assertEquals('error-input', $validator->getControlCssClass());
	}

	// ================================================================================
	// ErrorMessage Tests
	// ================================================================================

	public function testErrorMessageDefault()
	{
		$validator = new TRequiredFieldValidator();
		$this->assertEquals('', $validator->getErrorMessage());
	}

	public function testSetErrorMessage()
	{
		$validator = new TRequiredFieldValidator();
		$validator->setErrorMessage('This field is required');
		$this->assertEquals('This field is required', $validator->getErrorMessage());
	}

	// ================================================================================
	// ForControl Not Supported Tests
	// ================================================================================

	public function testSetForControlThrowsException()
	{
		$validator = new TRequiredFieldValidator();
		$this->expectException(\Prado\Exceptions\TNotSupportedException::class);
		$validator->setForControl('input1');
	}
}
