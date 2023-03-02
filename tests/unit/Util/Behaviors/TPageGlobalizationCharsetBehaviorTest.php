<?php

use Prado\I18N\TGlobalization;
use Prado\Prado;
use Prado\Util\Behaviors\TPageGlobalizationCharsetBehavior;

class TPageGlobalizationCharsetBehaviorTest extends PHPUnit\Framework\TestCase
{
	protected $obj;

	protected function setUp(): void
	{
		$this->obj = new TPageGlobalizationCharsetBehavior();
	}

	protected function tearDown(): void
	{
	}

	public function testConstruct()
	{
		$this->assertInstanceOf('\\Prado\\Util\\Behaviors\\TPageGlobalizationCharsetBehavior', $this->obj);
	}

	public function testAddCharsetMeta()
	{
		$app = Prado::getApplication();
		$page = new TPage();
		$head = new THead();
		$page->getControls()[] = $head;
		$page->setHead($head);
		
		self::assertEquals(0, $head->getMetaTags()->count());
		$this->obj->setEnabled(false);
		$this->obj->addCharsetMeta($page, null);
		self::assertEquals(0, $head->getMetaTags()->count());

		$this->obj->setEnabled(true);
		$this->obj->addCharsetMeta($page, null);
		self::assertEquals(1, $head->getMetaTags()->count());
		self::assertEquals('utf-8', strtolower($head->getMetaTags()[0]->getCharset()));
		
		$this->obj->setCheckMetaCharset(true);
		$this->obj->addCharsetMeta($page, null);
		self::assertEquals(1, $head->getMetaTags()->count());
		
		$this->obj->setCheckMetaCharset(false);
		
		$globalization = new TGlobalization();
		$globalization->setCharset('fr');
		$app->setGlobalization($globalization);
		
		$this->obj->addCharsetMeta($page, null);
		self::assertEquals(2, $head->getMetaTags()->count());
		self::assertEquals('fr', strtolower($head->getMetaTags()[1]->getCharset()));
		
	}

	public function testCheckMetaNoCache()
	{
		$this->obj->setCheckMetaCharset(true);
		self::assertTrue($this->obj->getCheckMetaCharset());
		$this->obj->setCheckMetaCharset(false);
		self::assertFalse($this->obj->getCheckMetaCharset());
		$this->obj->setCheckMetaCharset('true');
		self::assertTrue($this->obj->getCheckMetaCharset());
		$this->obj->setCheckMetaCharset('false');
		self::assertFalse($this->obj->getCheckMetaCharset());
	}

}
