<?php


use Prado\Web\UI\WebControls\TLabel;


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
