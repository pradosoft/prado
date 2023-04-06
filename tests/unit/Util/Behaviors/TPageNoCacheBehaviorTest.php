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
		$this->assertInstanceOf(TPageNoCacheBehavior::class, $this->obj);
	}

	public function testAddNoCacheMeta()
	{
		$page = new TPage();
		$head = new THead();
		$page->getControls()[] = $head;
		$page->setHead($head);
		
		self::assertEquals(0, $head->getMetaTags()->count());
		$this->obj->setEnabled(false);
		$this->obj->addNoCacheMeta($page, null);
		self::assertEquals(0, $head->getMetaTags()->count());
		
		$this->obj->setEnabled(true);
		$this->obj->addNoCacheMeta($page, null);
		self::assertEquals(3, $head->getMetaTags()->count());
		
		$hasExpires = $hasPragma = $hasCacheControl = false;
		
		foreach ($head->getMetaTags() as $meta) {
			$httpEquiv = strtolower($meta->getHttpEquiv());
			$content = strtolower($meta->getContent());
			if ($httpEquiv == 'expires') {
				$hasExpires = true;
			} elseif ($httpEquiv == 'pragma' && $content == 'no-cache') {
				$hasPragma = true;
			} elseif ($httpEquiv == 'cache-control' && $content == 'no-cache') {
				$hasCacheControl = true;
			}
		}
		self::assertTrue($hasExpires);
		self::assertTrue($hasPragma);
		self::assertTrue($hasCacheControl);
		
		$this->obj->setCheckMetaNoCache(true);
		
		$this->obj->addNoCacheMeta($page, null);
		self::assertEquals(3, $head->getMetaTags()->count());
		
		self::assertEquals(3, $head->getMetaTags()->count());
		$this->obj->setCheckMetaNoCache(false);
		
		$this->obj->addNoCacheMeta($page, null);
		self::assertEquals(6, $head->getMetaTags()->count());
		$metas = $head->getMetaTags();
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
