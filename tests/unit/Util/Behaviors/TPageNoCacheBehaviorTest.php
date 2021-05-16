<?php

use Prado\Util\Behaviors\TPageNoCacheBehavior;

class TPageNoCacheBehaviorTest extends PHPUnit\Framework\TestCase
{
	protected $obj;

	protected function setUp(): void
	{
		$this->obj = new TPageNoCacheBehavior();
	}

	protected function tearDown(): void
	{
	}

	public function testConstruct()
	{
		$this->assertInstanceOf('\\Prado\\Util\\Behaviors\\TPageNoCacheBehavior', $this->obj);
	}

	public function testAddNoCacheMeta()
	{
		$page = new TPage();
		$head = new THead();
		$page->getControls()[] = $head;
		$page->setHead($head);
		
		self::assertEquals(0, $head->getMetaTags()->count());
		
		$this->obj->addNoCacheMeta($page, null);
		self::assertEquals(3, $head->getMetaTags()->count());
		
		$this->obj->setCheckMetaNoCache(true);
		
		$this->obj->addNoCacheMeta($page, null);
		self::assertEquals(3, $head->getMetaTags()->count());
		
		$this->obj->setCheckMetaNoCache(false);
		
		$this->obj->addNoCacheMeta($page, null);
		self::assertEquals(6, $head->getMetaTags()->count());
	}

	public function testCheckMetaNoCache()
	{
		$this->obj->setCheckMetaNoCache(true);
		self::assertTrue($this->obj->getCheckMetaNoCache());
		$this->obj->setCheckMetaNoCache(false);
		self::assertFalse($this->obj->getCheckMetaNoCache());
		$this->obj->setCheckMetaNoCache('true');
		self::assertTrue($this->obj->getCheckMetaNoCache());
		$this->obj->setCheckMetaNoCache('false');
		self::assertFalse($this->obj->getCheckMetaNoCache());
	}

}
