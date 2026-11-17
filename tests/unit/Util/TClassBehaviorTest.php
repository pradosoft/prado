<?php

namespace Prado\Test\Unit\Util;

use Prado\Util\TClassBehavior;
use Prado\TComponent;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Test\Unit\PradoUnit;

class TestClassBehaviorKey extends TClassBehavior
{
    public function getKeyPublic(): string
    {
        return $this->getWithoutOwnerExceptionKey();
    }
}

class TClassBehaviorTestOwner extends TComponent
{
    public function onMyEvent($param)
    {
        return $this->raiseEvent('onMyEvent', $this, $param);
    }
}

class TClassBehaviorTestHandlers extends TClassBehavior
{
    public function events()
    {
        return ['onMyEvent' => 'myHandler'];
    }

    public function myHandler($sender, $param)
    {
    }
}

class TClassBehaviorTest extends \PHPUnit\Framework\TestCase
{
    public function testAttachMultipleOwnersGetOwnersIsOwner()
    {
        $cb = new class extends TClassBehavior {};
        $c1 = new TComponent();
        $c2 = new TComponent();
        $cb->attach($c1);
        $cb->attach($c2);
        $owners = $cb->getOwners();
        $this->assertCount(2, $owners);
        $this->assertContains($c1, $owners);
        $this->assertContains($c2, $owners);
        $this->assertTrue($cb->isOwner($c1));
        $this->assertTrue($cb->isOwner($c2));
    }

    public function testDetachWrongOwnerThrows()
    {
        $cb = new class extends TClassBehavior {};
        $c1 = new TComponent();
        $c2 = new TComponent();
        $cb->attach($c1);
        $this->expectException(TInvalidOperationException::class);
        $cb->detach($c2);
    }

    public function testDetachWithoutOwnerThrows()
    {
        $cb = new class extends TClassBehavior {};
        $this->expectException(TInvalidOperationException::class);
        $cb->detach(new TComponent());
    }

    public function testDetachAllOwnersRemovesState()
    {
        $cb = new class extends TClassBehavior {};
        $c1 = new TComponent();
        $c2 = new TComponent();
        $cb->attach($c1);
        $cb->attach($c2);
        $cb->detach($c1);
        $owners = $cb->getOwners();
        $this->assertCount(1, $owners);
        $this->assertTrue($cb->hasOwner());
        $cb->detach($c2);
        $this->assertFalse($cb->hasOwner());
    }

    public function testGetWithoutOwnerExceptionKeyOverride()
    {
        $cb = new class extends TestClassBehaviorKey {};
        $this->assertEquals('behavior_property_unchangeable', $cb->getKeyPublic());
    }

    public function testDefaultKeyGetter()
    {
        $cb = new class extends TClassBehavior {};
        // expose default key via a wrapper
        $wrapper = new class extends TestClassBehaviorKey {
            public function __construct() {
                // no-op
            }
        };
        $this->assertEquals('behavior_property_unchangeable', $wrapper->getKeyPublic());
    }

    public function testAttachNonComponentThrows()
    {
        $cb = new TClassBehavior();
        try {
            $cb->attach(new \stdClass());
            $this->fail('attach() did not throw for an object that is not a TComponent.');
        } catch (TInvalidOperationException $e) {
            $this->assertSame('classbehavior_bad_attach_component', $e->getErrorCode());
        }
    }

    public function testAttachSameOwnerTwiceThrows()
    {
        $cb = new TClassBehavior();
        $c1 = new TComponent();
        $cb->attach($c1);
        try {
            $cb->attach($c1);
            $this->fail('attach() did not throw for an owner attached twice.');
        } catch (TInvalidOperationException $e) {
            $this->assertSame('classbehavior_owner_attach_once', $e->getErrorCode());
        }
    }

    public function testGetOwnersWithoutOwner()
    {
        $cb = new TClassBehavior();
        $this->assertSame([], $cb->getOwners());
        $this->assertFalse($cb->hasOwner());
        $this->assertFalse($cb->isOwner(new TComponent()));
    }

    public function testCloneHasNoOwner()
    {
        $cb = new TClassBehavior();
        $cb->attach(new TComponent());
        $clone = clone $cb;
        $this->assertFalse($clone->hasOwner());
        $this->assertSame([], $clone->getOwners());
        $this->assertNull(PradoUnit::invoke($clone, 'getHandlersStatus'));
    }

    public function testEnableDisableBehaviorsSyncsHandlers()
    {
        $owner = new TClassBehaviorTestOwner();
        $cb = new TClassBehaviorTestHandlers();
        $owner->attachBehavior('cb', $cb);
        $this->assertSame(1, $owner->getEventHandlers('onMyEvent')->getCount());

        $owner->disableBehaviors();
        $this->assertSame(0, $owner->getEventHandlers('onMyEvent')->getCount());
        $owner->enableBehaviors();
        $this->assertSame(1, $owner->getEventHandlers('onMyEvent')->getCount());

        $owner->disableBehaviors();
        $cb->dyEnableBehaviors($owner);
        $this->assertSame(0, $owner->getEventHandlers('onMyEvent')->getCount());
        $cb->setRetainDisabledHandlers(true);
        $cb->dyDisableBehaviors($owner);
        $this->assertSame(1, $owner->getEventHandlers('onMyEvent')->getCount());
    }

    public function testHandlersStatus()
    {
        $owner = new TClassBehaviorTestOwner();
        $other = new TComponent();
        $cb = new TClassBehaviorTestHandlers();
        $owner->attachBehavior('cb', $cb);

        $this->assertNull(PradoUnit::invoke($cb, 'getHandlersStatus'));
        $this->assertTrue(PradoUnit::invoke($cb, 'getHandlersStatus', $owner));
        $this->assertFalse(PradoUnit::invoke($cb, 'getHandlersStatus', $other));
        $this->assertFalse(PradoUnit::invoke($cb, 'setHandlersStatus', $owner, true));
        $this->assertFalse(PradoUnit::invoke($cb, 'setHandlersStatus', $other, true));
        $this->assertFalse(PradoUnit::invoke($cb, 'setHandlersStatus', $other, false));
        $this->assertTrue(PradoUnit::invoke($cb, 'setHandlersStatus', $owner, false));
    }

    public function testSerializeDropsTheOwners()
    {
        $cb = new TClassBehavior();
        $owner = new TComponent();
        $cb->attach($owner);

        $copy = unserialize(serialize($cb));
        $this->assertFalse($copy->hasOwner());
        $this->assertFalse(PradoUnit::invoke($copy, 'getHandlersStatus', $owner));
    }

    public function testDetachNullOwnerThrows()
    {
        $cb = new TClassBehavior();
        $cb->attach(new TComponent());
        try {
            $cb->detach(null);
            $this->fail('detach() did not throw for a null owner.');
        } catch (TInvalidOperationException $e) {
            $this->assertSame('classbehavior_detach_wrong_owner', $e->getErrorCode());
        }
    }

    public function testDetachTheLaterOwnerKeepsTheFirst()
    {
        $cb = new TClassBehavior();
        $c1 = new TComponent();
        $c2 = new TComponent();
        $cb->attach($c1);
        $cb->attach($c2);
        $cb->detach($c2);
        $this->assertSame([$c1], $cb->getOwners());
        $this->assertTrue($cb->hasOwner());
    }

    public function testSetHandlersStatusWithoutOwners()
    {
        $cb = new TClassBehavior();
        $this->assertFalse(PradoUnit::invoke($cb, 'setHandlersStatus', new TComponent(), true));
        $this->assertFalse(PradoUnit::invoke($cb, 'setHandlersStatus', new TComponent(), false));
    }
}
