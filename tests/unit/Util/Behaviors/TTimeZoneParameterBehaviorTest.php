<?php

namespace Prado\Test\Unit\Util\Behaviors;

use Prado\Util\Behaviors\TTimeZoneParameterBehavior;
use Prado\Prado;

class TTimeZoneParameterBehaviorTest extends \PHPUnit\Framework\TestCase
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
		$this->obj->setEnabled('false');
		self::assertFalse($this->obj->getEnabled());
		$params['test:TimeZone'] = 'UTC';
		self::assertEquals('Europe/Rome', $this->obj->getTimeZone());
		$this->obj->setEnabled('true');
		$params['test:TimeZone'] = 'Europe/Rome';
		self::assertEquals('Europe/Rome', $this->obj->getTimeZone());
		
		// And test the detach method too
		$app->detachBehavior($behaviorName);
		$params['test:TimeZone'] = 'Europe/London';
		self::assertEquals('Europe/Rome', $this->obj->getTimeZone());
		
		unset($params['test:TimeZone']);
	}

	public function testSetEnabled_withoutOwnerWaitsForAttach()
	{
		$app = Prado::getApplication();
		$params = $app->getParameters();
		$this->obj->setTimeZoneParameter('test:TimeZone');

		$this->obj->setEnabled(true);
		self::assertNull($params->asa(TTimeZoneParameterBehavior::APP_PARAM_ROUTE_BEHAVIOR_NAME));

		$app->attachBehavior('timezonetest', $this->obj);
		self::assertNotNull($params->asa(TTimeZoneParameterBehavior::APP_PARAM_ROUTE_BEHAVIOR_NAME));
		$app->detachBehavior('timezonetest');
		self::assertNull($params->asa(TTimeZoneParameterBehavior::APP_PARAM_ROUTE_BEHAVIOR_NAME));
	}

	public function testDetach_releasesTheRoute()
	{
		$app = Prado::getApplication();
		$params = $app->getParameters();
		$this->obj->setTimeZoneParameter('test:TimeZone');
		$app->attachBehavior('timezonetest', $this->obj);
		$app->detachBehavior('timezonetest');

		$other = new TTimeZoneParameterBehavior();
		$other->setTimeZoneParameter('test:TimeZone');
		$app->attachBehavior('timezonetest2', $other);

		// The detached behavior no longer owns the route, so disabling it keeps the other route.
		$this->obj->setEnabled(false);
		$params['test:TimeZone'] = 'Europe/Rome';
		self::assertEquals('Europe/Rome', $this->obj->getTimeZone());

		$app->detachBehavior('timezonetest2');
		unset($params['test:TimeZone']);
	}

	public function testAttach_disabledOrWithoutParameter()
	{
		$app = Prado::getApplication();
		$params = $app->getParameters();
		$params['test:TimeZone'] = 'Europe/Rome';
		$this->obj->setTimeZoneParameter('test:TimeZone');
		$this->obj->setEnabled(false);
		$app->attachBehavior('timezonetest', $this->obj);
		self::assertEquals('UTC', $this->obj->getTimeZone());
		self::assertNull($params->asa(TTimeZoneParameterBehavior::APP_PARAM_ROUTE_BEHAVIOR_NAME));
		$app->detachBehavior('timezonetest');

		$behavior = new TTimeZoneParameterBehavior();
		$behavior->setTimeZoneParameter('');
		$app->attachBehavior('timezonetest', $behavior);
		self::assertEquals('UTC', $behavior->getTimeZone());
		self::assertNull($params->asa(TTimeZoneParameterBehavior::APP_PARAM_ROUTE_BEHAVIOR_NAME));
		$app->detachBehavior('timezonetest');
		unset($params['test:TimeZone']);
	}

	public function testSetEnabled_appliesTheTimeZoneParameter()
	{
		$app = Prado::getApplication();
		$params = $app->getParameters();
		$params['test:TimeZone'] = 'Europe/Rome';
		$this->obj->setTimeZoneParameter('test:TimeZone');
		$this->obj->setEnabled(false);
		$app->attachBehavior('timezonetest', $this->obj);
		self::assertEquals('UTC', $this->obj->getTimeZone());

		$this->obj->setEnabled(true);
		self::assertEquals('Europe/Rome', $this->obj->getTimeZone());
		$this->obj->setEnabled(false);
		$params['test:TimeZone'] = 'America/Los_Angeles';
		self::assertEquals('Europe/Rome', $this->obj->getTimeZone());
		$this->obj->setEnabled(true);
		self::assertEquals('America/Los_Angeles', $this->obj->getTimeZone());

		$app->detachBehavior('timezonetest');
		unset($params['test:TimeZone']);
	}

	public function testSetEnabled_withoutParameterAttachesNoRoute()
	{
		$app = Prado::getApplication();
		$params = $app->getParameters();
		$this->obj->setTimeZoneParameter('');
		$this->obj->setEnabled(false);
		$app->attachBehavior('timezonetest', $this->obj);

		$this->obj->setEnabled(true);
		self::assertEquals('UTC', $this->obj->getTimeZone());
		self::assertNull($params->asa(TTimeZoneParameterBehavior::APP_PARAM_ROUTE_BEHAVIOR_NAME));
		$app->detachBehavior('timezonetest');
	}

	public function testSetTimeZoneParameter_whileAttached()
	{
		$app = Prado::getApplication();
		$params = $app->getParameters();
		$this->obj->setTimeZoneParameter('test:TimeZone');
		$app->attachBehavior('timezonetest', $this->obj);

		$this->obj->setTimeZoneParameter('test:OtherZone');
		$params['test:TimeZone'] = 'Europe/Rome';
		self::assertEquals('UTC', $this->obj->getTimeZone());
		$params['test:OtherZone'] = 'Europe/London';
		self::assertEquals('Europe/London', $this->obj->getTimeZone());

		$app->detachBehavior('timezonetest');
		unset($params['test:TimeZone'], $params['test:OtherZone']);
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
