<?php
require_once dirname(__FILE__).'/../phpunit.php';

Prado::using('System.Web.THttpRequest');

/**
 * @package System.Web
 */
class THttpCookieCollectionTest extends PHPUnit_Framework_TestCase {

  public function testConstruct() {
    $coll=new THttpCookieCollection();
    self::assertType('THttpCookieCollection', $coll);
  }

  public function testInsertAt() {
    $coll=new THttpCookieCollection();
    $coll->insertAt(0, new THttpCookie('name','value'));
    self::assertEquals('value',$coll->itemAt(0)->getValue());
    try {
    	$coll->insertAt(1, "bad parameter");
    	self::fail ('Invalid data type exception not raised');
    } catch (TInvalidDataTypeException $e) {}
  }

  public function testRemoveAt() {
    $coll=new THttpCookieCollection();
    try {
    	$coll->removeAt(0);
    	self::fail('Invalid Value exception not raised');
    } catch (TInvalidDataValueException $e) {}
    
    $coll->insertAt(0, new THttpCookie('name','value'));
    self::assertEquals('value',$coll->removeAt(0)->getValue());
  }

  public function testItemAt() {
    $coll=new THttpCookieCollection();
    $coll->insertAt(0, new THttpCookie('name','value'));
    self::assertEquals('value',$coll->itemAt(0)->getValue());
    self::assertEquals('value',$coll->itemAt('name')->getValue());
  }

  public function testFindCookieByName() {
    $coll=new THttpCookieCollection();
    $coll->insertAt(0, new THttpCookie('name','value'));
    self::assertEquals ('value', $coll->findCookieByName('name')->getValue());
    self::assertNull ($coll->findCookieByName('invalid'));
  }
}
?>