<?php

use Prado\Exceptions\TInvalidDataTypeException;
use Prado\TComponent;
use Prado\Util\Behaviors\TMapLazyLoadBehavior;


class TMapLazyLoadBehaviorTest extends PHPUnit\Framework\TestCase
{
	public const BEHAVIOR_NAME = 'lazyload';
	protected $map;
	protected $behavior;
	
	protected $random;

	protected function setUp(): void
	{
		$this->random = null;
		$this->map = new TMap;
		$this->behavior = new TMapLazyLoadBehavior([$this, 'getRandom']);
	}
	
	public function getRandom($key)
	{
		if ($key == 'random') {
			return $this->random = rand();
		}
		return null;
	}

	protected function tearDown(): void
	{
		$this->map = null;
		$this->behavior = null;
	}

	public function testConstruct()
	{
		$this->assertInstanceOf('\\Prado\\Util\\Behaviors\\TMapLazyLoadBehavior', $this->behavior);
		
		try {
			$b = new TMapLazyLoadBehavior(null);
			self::fail('Expected TInvalidDataTypeException not thrown');
		} catch (TInvalidDataTypeException $e) {
		}
	}
	
	public function testDyNoItem()
	{
		self::assertNull($this->map['random']);
		self::assertNull($this->map['key1']);
		self::assertNull($this->random);
		
		$this->map->attachBehavior(self::BEHAVIOR_NAME, $this->behavior);
		
		self::assertNull($this->map['key1']);
		
		$v = $this->map['random'];
		self::assertNotNull($v);
		self::assertNotNull($this->random);
		self::assertEquals($this->random, $v);
		
		$this->map->detachBehavior(self::BEHAVIOR_NAME);
		
		self::assertNull($this->map['random']);
	}
	
}
