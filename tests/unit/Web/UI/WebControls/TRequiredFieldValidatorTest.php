<?php


Prado::using('System.Web.UI.WebControls.TRequiredFieldValidator');

/**
 * @package System.Web.UI.WebControls
 */
class TRequiredFieldValidatorTest extends PHPUnit_Framework_TestCase {

  public function testGetEmptyInitialValue() {
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
