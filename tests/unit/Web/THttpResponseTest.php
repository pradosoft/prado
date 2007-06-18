<?php
require_once dirname(__FILE__).'/../phpunit.php';
require_once 'PHPUnit/Extensions/OutputTestCase.php';

Prado::using('System.Web.THttpResponse');


/**
 * @package System.Web
 */
class THttpResponseTest extends PHPUnit_Framework_TestCase {
  
  public static $app=null;

  public function setUp () {
    if (self::$app===null) self::$app=new TApplication(dirname(__FILE__).'/app');
    ob_start();
  }
  
  public function tearDown () {
    ob_end_flush();
  }
  
  public function testInit() {
    $response=new THttpResponse ();
    $response->init (null);
    self::assertEquals ($response, self::$app->getResponse());
  }

  public function testSetCacheExpire() {
    $response=new THttpResponse ();
    $response->init (null);
    $response->setCacheExpire (300);
    self::assertEquals(300, $response->getCacheExpire());
  }

  public function testSetCacheControl() {
    $response=new THttpResponse ();
    $response->init (null);
    foreach (array ('none','nocache','private','private_no_expire','public') as $cc) {
      $response->setCacheControl($cc);
      self::assertEquals($cc, $response->getCacheControl());
    }
    try {
      $response->setCacheControl('invalid');
      self::fail ('Expected TInvalidDataValueException not thrown');
    } catch (TInvalidDataValueException $e) {}
    
  }

  public function testSetContentType() {
    $response=new THttpResponse ();
    $response->init (null);
    $response->setContentType('image/jpeg');
    self::assertEquals('image/jpeg', $response->getContentType());
    $response->setContentType('text/plain');
    self::assertEquals('text/plain', $response->getContentType());
  }

  public function testSetCharset() {
    $response=new THttpResponse ();
    $response->init (null);
    $response->setCharset ('UTF-8');
    self::assertEquals('UTF-8', $response->getCharset());
    $response->setCharset ('ISO8859-1');
    self::assertEquals('ISO8859-1', $response->getCharset());
    
  }

  public function testSetBufferOutput() {
    $response=new THttpResponse ();
    $response->setBufferOutput(true);
    self::assertTrue($response->getBufferOutput());
    $response->init (null);
    try {
      $response->setBufferOutput(false);
      self::fail ('Expected TInvalidOperationException not thrown');
    } catch (TInvalidOperationException $e) {}
  }

  public function testSetStatusCode() {
    $response=new THttpResponse ();
    $response->init (null);
    $response->setStatusCode(401);
    self::assertEquals(401, $response->getStatusCode());
    $response->setStatusCode(200);
    self::assertEquals(200, $response->getStatusCode());
  }

  public function testGetCookies() {
    $response=new THttpResponse ();
    $response->init (null);
    self::assertType('THttpCookieCollection', $response->getCookies());
    self::assertEquals(0, $response->getCookies()->getCount());
  }

  public function testWrite() {
    $response=new THttpResponse ();
	//self::expectOutputString("test string");
    $response->write("test string");
    self::assertContains ('test string', ob_get_clean());

  }

  public function testWriteFile() {
    
  	 // Don't know how to test headers :(( ...
	throw new PHPUnit_Framework_IncompleteTestError();
  	
    $response=new THttpResponse ();
    $response->setBufferOutput(true);
    // Suppress warning with headers
    $response->writeFile(dirname(__FILE__).'/data/aTarFile.md5', null, 'text/plain', array ('Pragma: public', 'Expires: 0'));
	
    self::assertContains('4b1ecb0b243918a8bbfbb4515937be98  aTarFile.tar', ob_get_clean());
	
  }

  public function testRedirect() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testReload() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testFlush() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testSendContentTypeHeader() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testClear() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testAppendHeader() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testAppendLog() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testAddCookie() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testRemoveCookie() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testSetHtmlWriterType() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testCreateHtmlWriter() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }
}
?>