<?php


use Prado\Exceptions\TConfigurationException;
use Prado\Web\UI\WebControls\TRequiredFieldValidator;

class TRequiredFieldValidatorTest extends PHPUnit\Framework\TestCase
{
	public function testGetEmptyInitialValue()
	{
		// getInitialValue() calls getControlPromptValue() as its viewstate default value,
		// which requires ControlToValidate to be set; set up a minimal target control
		$page = new \Prado\Web\UI\TPage();
		$textbox = new \Prado\Web\UI\WebControls\TTextBox();
		$textbox->setID('input1');
		$page->getControls()->add($textbox);

		$validator = new TRequiredFieldValidator();
		$validator->setID('v1');
		$validator->setControlToValidate('input1');
		$page->getControls()->add($validator);

		// Default initial value is '' (TTextBox has no prompt value)
		$this->assertEquals('', $validator->getInitialValue());

		// Explicitly set value is returned correctly
		$validator->setInitialValue('expected');
		$this->assertEquals('expected', $validator->getInitialValue());
	}
}
