<?php
require_once dirname(__FILE__).'/../phpunit.php';

Prado::using('System.Xml.TRssFeedDocument');

/**
 * @package System.Xml.TRssFeedDocument
 */
class TRssFeedDocumentTest extends PHPUnit_Framework_TestCase {

  public function testConstruct() {
    $feed = new TRssFeedDocument();
    self::assertEquals('0.91', $feed->getVersion());
    $feed = new TRssFeedDocument('UTF-8');
    self::assertEquals('UTF-8', $feed->getEncoding());
  }
  
  public function testSetVersion() {
    $feed = new TRssFeedDocument();
    $feed->setVersion('0.92');
    self::assertEquals('0.92', $feed->getVersion());
    $feed->setVersion('2.0');
    self::assertEquals('2.0', $feed->getVersion());
  }

  public function testSetTitle() {
    $expected = "This is a title";
    $feed = new TRssFeedDocument();
    $feed->setTitle($expected);
    self::assertEquals($expected, $feed->getTitle());
  }

  public function testSetLink() {
    $expected = "http://www.pradosoft.com";
    $feed = new TRssFeedDocument();
    $feed->setLink($expected);
    self::assertEquals($expected, $feed->getLink());
  }

  public function testSetDescription() {
    $expected = "This is a description";
    $feed = new TRssFeedDocument();
    $feed->setDescription($expected);
    self::assertEquals($expected, $feed->getDescription());
  }

  public function testSetLanguage() {
    $expected = "en-us";
    $feed = new TRssFeedDocument();
    $feed->setLanguage($expected);
    self::assertEquals($expected, $feed->getLanguage());
  }

  public function testSetCopyright() {
    $expected = "Copyright (C) 2006 PradoSoft";
    $feed = new TRssFeedDocument();
    $feed->setCopyright($expected);
    self::assertEquals($expected, $feed->getCopyright());
  }

  public function testSetManagingEditor() {
    $expected = "test@gmail.com";
    $feed = new TRssFeedDocument();
    $feed->setManagingEditor($expected);
    self::assertEquals($expected, $feed->getManagingEditor());
  }

  public function testSetWebMaster() {
    $expected = "test@gmail.com";
    $feed = new TRssFeedDocument();
    $feed->setWebMaster($expected);
    self::assertEquals($expected, $feed->getWebMaster());
  }
  
  public function testSetRating() {
    $expected = '(PICS-1.1 "http://www.classify.org/safesurf/" l r (SS~~000 1))';
    $feed = new TRssFeedDocument();
    $feed->setRating($expected);
    self::assertEquals($expected, $feed->getRating());
  }

  public function testSetPublicationDate() {
    $expected = 'Fri, 13 Apr 2001 19:23:02 GMT';
    $feed = new TRssFeedDocument();
    $feed->setPublicationDate($expected);
    self::assertEquals($expected, $feed->getPublicationDate());
  }

  public function testSetLastBuildDate() {
    $expected = 'Fri, 13 Apr 2001 19:23:02 GMT';
    $feed = new TRssFeedDocument();
    $feed->setLastBuildDate($expected);
    self::assertEquals($expected, $feed->getLastBuildDate());
  }

  public function testSetDocumentation() {
    $expected = 'http://backend.userland.com/rss092';
    $feed = new TRssFeedDocument();
    $feed->setDocumentation($expected);
    self::assertEquals($expected, $feed->getDocumentation());
  }

  public function testSetSkipDays() {
    $expected = array('Saturday', 'Sunday');
    $feed = new TRssFeedDocument();
    $feed->setSkipDays($expected);
    self::assertEquals($expected, $feed->getSkipDays());
  }

  public function testSetSkipHours() {
    $expected = array(1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21, 23);
    $feed = new TRssFeedDocument();
    $feed->setSkipHours($expected);
    self::assertEquals($expected, $feed->getSkipHours());
  }

  public function testAddItem() {
    $feed = new TRssFeedDocument();
    $item = new TRssFeedItem();
    $feed->addItem($item);
    $items = $feed->getItems();
    /*    var_dump($items);
    self::assertType($items[0], 'TRssItem');*/
  }

  public function testSetImage() {
    throw new PHPUnit2_Framework_IncompleteTestError();
  }

  public function testSetTextInput() {
    throw new PHPUnit2_Framework_IncompleteTestError();
  }

  public function testSetCloud() {
    throw new PHPUnit2_Framework_IncompleteTestError();
  }

  public function testSetCategory() {
    $expected = 'Business/Industries/Publishing/Publishers/Nonfiction/';
    $feed = new TRssFeedDocument();
    $feed->setCategory($expected, 'http://www.pradosoft.com');
    self::assertEquals($expected, $feed->getCategory());
  }

  public function testSetGenerator() {
    $expected = 'PRADO 3.0';
    $feed = new TRssFeedDocument();
    $feed->setVersion('2.0');
    $feed->setGenerator($expected);
    self::assertEquals($expected, $feed->getGenerator());
  }

  public function testSetTimeToLive() {
    throw new PHPUnit2_Framework_IncompleteTestError();
  }
}

?>