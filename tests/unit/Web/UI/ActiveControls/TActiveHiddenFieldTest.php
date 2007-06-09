<?php
require_once dirname(__FILE__).'/../../../phpunit.php';

Prado::using('System.Web.UI.ActiveControls.TActiveHiddenField');

/**
 * @package System.Web.UI.ActiveControls
 */
class TActiveHiddenFieldTest extends PHPUnit_Framework_TestCase {

  public function testSetValue() {
    $field = new TActiveHiddenField();
    $field->setValue('Test');
    $this->assertEquals('Test', $field->getValue());
  }
}

?>