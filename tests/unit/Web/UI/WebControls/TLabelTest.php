<?php
require_once dirname(__FILE__).'/../../../phpunit.php';

Prado::using('System.Web.UI.WebControls.TLabel');
Prado::using('System.Web.UI.THtmlWriter');

/**
 * @package System.Web.UI.WebControls
 */
class TLabelTest extends PHPUnit_Framework_TestCase {

  public function testSetText() {
    $label = new TLabel();
    $label->setText('Test');
    $this->assertEquals('Test', $label->getText());
  }
}

?>