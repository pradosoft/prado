<?php

Prado::using('System.Web.THttpSession');

/**
 * @package System.Web
 */
class THttpSessionTest extends PHPUnit_Framework_TestCase {

  public function testInit() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testOpen() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testClose() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testDestroy() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testGetIsStarted() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testSetSessionID() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testSetSessionName() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testSetSavePath() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testSetUseCustomStorage() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testSetCookieModeNone() {
    $session = new THttpSession();
    $session->CookieMode = THttpSessionCookieMode::None;

    $this->assertEquals(0, ini_get('session.use_cookies_only'));
    $this->assertEquals(0, ini_get('session.use_cookies'));
    $this->assertEquals(THttpSessionCookieMode::None, $session->CookieMode);
  }

  public function testSetCookieModeAllow() {
    $session = new THttpSession();
    $session->CookieMode = THttpSessionCookieMode::Allow;

    $this->assertEquals(0, ini_get('session.use_only_cookies'));
    $this->assertEquals(1, ini_get('session.use_cookies'));
    $this->assertEquals(THttpSessionCookieMode::Allow, $session->CookieMode);
  }

  public function testSetCookieModeAlways() {
    $session = new THttpSession();
    $session->CookieMode = THttpSessionCookieMode::Only;

    $this->assertEquals(1, ini_get('session.use_only_cookies'));
    $this->assertEquals(1, ini_get('session.use_cookies'));
    $this->assertEquals(0, ini_get('session.use_trans_sid'));
    $this->assertEquals(THttpSessionCookieMode::Only, $session->CookieMode);
  }

  public function testSetAutoStart() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testSetGProbability() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testSetUseTransparentSessionID() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testSetTimeout() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testGetIterator() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testGetCount() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testGetKeys() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testItemAt() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testAdd() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testRemove() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testContains() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testToArray() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testOffsetExists() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testOffsetGet() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testOffsetSet() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }

  public function testOffsetUnset() {
    throw new PHPUnit_Framework_IncompleteTestError();
  }
}
