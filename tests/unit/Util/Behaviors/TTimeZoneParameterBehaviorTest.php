<?php

use Prado\Util\Behaviors\TTimeZoneParameterBehavior;

class TTimeZoneParameterBehaviorTest extends PHPUnit\Framework\TestCase
{
	protected $obj;

	protected function setUp(): void
	{
		date_default_timezone_set('UTC');
		$this->obj = new TTimeZoneParameterBehavior();
	}

	protected function tearDown(): void
	{
		date_default_timezone_set('UTC');
	}

	public function testConstruct()
	{
		$this->assertInstanceOf(TTimeZoneParameterBehavior::class, $this->obj);
	}
	
	// test the attach _and_ detach methods
	public function testAttachBehavior()
	{
		$app = Prado::getApplication();
		$params = $app->getParameters();
		$behaviorName = 'timezonetest';
		
		//sanity check
		self::assertEquals('UTC', $this->obj->getTimeZone());
		
		$this->obj->setTimeZoneParameter('test:TimeZone');
		$params['test:TimeZone'] = 'Europe/Rome';
		
		self::assertEquals('UTC', $this->obj->getTimeZone());
		
		// sets the parameter timezone on attach
		$app->attachBehavior($behaviorName, $this->obj);
		$app->detachBehavior($behaviorName);
		
		// sets the parameter timezone on attach
		$app->attachBehavior($behaviorName, $this->obj);
		
		self::assertEquals('Europe/Rome', $this->obj->getTimeZone());
		$params['test:TimeZone'] = 'America/Los_Angeles';
		self::assertEquals('America/Los_Angeles', $this->obj->getTimeZone());
		$this->obj->setEnabled(false);
		$params['test:TimeZone'] = 'UTC';
		self::assertEquals('America/Los_Angeles', $this->obj->getTimeZone());
		$this->obj->setEnabled(true);
		$params['test:TimeZone'] = 'Europe/Rome';
		self::assertEquals('Europe/Rome', $this->obj->getTimeZone());
		
		// And test the detach method too
		$app->detachBehavior($behaviorName);
		$params['test:TimeZone'] = 'Europe/London';
		self::assertEquals('Europe/Rome', $this->obj->getTimeZone());
		
		unset($params['test:TimeZone']);
	}

	public function testTimeZoneParameter()
	{
		self::assertTrue(is_string($this->obj->getTimeZoneParameter()));
		self::assertTrue(strlen($this->obj->getTimeZoneParameter()) > 0);
		$this->obj->setTimeZoneParameter('TimeZone');
		self::assertEquals('TimeZone', $this->obj->getTimeZoneParameter());
		$this->obj->setTimeZoneParameter('prop:timezone');
		self::assertEquals('prop:timezone', $this->obj->getTimeZoneParameter());
	}

	public function testTimeZone()
	{
		self::assertEquals('UTC', $this->obj->getTimeZone());
		self::assertTrue($this->obj->setTimeZone('Europe/London'));
		self::assertEquals('Europe/London', $this->obj->getTimeZone());
		
		self::assertFalse($this->obj->setTimeZone('abc zone'));
		self::assertEquals('Europe/London', $this->obj->getTimeZone());
	}

}
