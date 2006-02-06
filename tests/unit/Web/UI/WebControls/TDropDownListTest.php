<?php
require_once dirname(__FILE__).'/../../../phpunit2.php';

Prado::using('System.Web.UI.WebControls.TDropDownList');

/**
 * @package System.Web.UI.WebControls
 */
class TDropDownListTest extends PHPUnit2_Framework_TestCase {

  public function testSetDataSource() {
    $list = new TDropDownList();
    $data = array('a' => 1,
		  'b' => 2,
		  'c' => 3);
    $list->setDataSource($data);
    $list->dataBind();
    $items =& $list->getItems();
    $this->assertTrue($items instanceof TListItemCollection);
    $expected_keys = array_keys($data);
    $i = 0;
    foreach($items as $item) {
      $this->assertEquals($expected_keys[$i], $item->getValue());
      $this->assertEquals((string)$data[$expected_keys[$i]], $item->getText());
      $i++;
    }
  }
}

?>