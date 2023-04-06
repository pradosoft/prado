<?php

use Prado\Util\Behaviors\TPageTopAnchorBehavior;
use Prado\Web\UI\TPage;
use Prado\Web\UI\TForm;

class TPageTopAnchorBehaviorTest extends PHPUnit\Framework\TestCase
{
	protected $obj;

	protected function setUp(): void
	{
		$this->obj = new TPageTopAnchorBehavior();
	}

	protected function tearDown(): void
	{
	}

	public function testConstruct()
	{
		$this->assertInstanceOf(TPageTopAnchorBehavior::class, $this->obj);
	}
	
	public function testAddFormANameAnchor()
	{
		$page = new TPage;
		$page->getControls()[] = $form = new TForm();
		$page->setForm($form);
		
		self::assertEquals(1, $page->getControls()->count());
		$this->obj->setEnabled(false);
		$this->obj->addFormANameAnchor($page, null);
		self::assertEquals(1, $page->getControls()->count());
		
		$this->obj->setEnabled(true);
		$this->obj->addFormANameAnchor($page, null);
		self::assertEquals(2, $page->getControls()->count());
	}

	public function testTopAnchor()
	{
		$this->obj->setTopAnchor('main');
		self::assertEquals('main', $this->obj->getTopAnchor());
		$this->obj->setTopAnchor('top');
		self::assertEquals('top', $this->obj->getTopAnchor());
	}

}
