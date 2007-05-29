<?php
require_once dirname(__FILE__).'/../../../phpunit.php';

Prado::using('System.Web.UI.WebControls.TRequiredFieldValidator');

/**
 * @package System.Web.UI.WebControls
 */
class TRequiredFieldValidatorTest extends PHPUnit_Framework_TestCase {

  public function testGetEmptyInitialValue() {
    $validator = new TRequiredFieldValidator();
    $this->assertEquals('', $validator->getInitialValue());
  }
}

?>