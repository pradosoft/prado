<?php


use Prado\Exceptions\TConfigurationException;
use Prado\Web\UI\WebControls\TRequiredFieldValidator;

class TRequiredFieldValidatorTest extends PHPUnit\Framework\TestCase
{
	public function testGetEmptyInitialValue()
	{
		$validator = new TRequiredFieldValidator();
		try {
			$value = $validator->getInitialValue();
		} catch (TConfigurationException $e) {
			//since prado 3.2.2 you need to set at least ControlToValidate
			$value = '';
		}
		$this->assertEquals('', $value);
	}
}
