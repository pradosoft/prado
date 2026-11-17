<?php

namespace Prado\Test\Unit\Util;

use Prado\Util\TBehavior;
use Prado\TComponent;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Test\Unit\PradoUnit;

class TestCloneableBehavior extends TBehavior
{
    public function cloneAndReturnOwnerState(): array
    {
        $cloned = clone $this;
        return [
            'owner' => $cloned->getOwner(),
            'hasOwner' => $cloned->hasOwner(),
        ];
    }
}

class TBehaviorTestOwner extends TComponent
{
    public function onMyEvent($param)
    {
        return $this->raiseEvent('onMyEvent', $this, $param);
    }
}

class TBehaviorTestHandlers extends TBehavior
{
    public function events()
    {
        return ['onMyEvent' => 'myHandler'];
    }

    public function myHandler($sender, $param)
    {
    }
}

class TBehaviorTest extends \PHPUnit\Framework\TestCase
{
    public function testAttachDetachBasic()
    {
        $b = new TestCloneableBehavior();
        $c = new TComponent();
        $b->attach($c);
        $this->assertSame($c, $b->getOwner());
        $this->assertTrue($b->hasOwner());
        $b->detach($c);
        $this->assertNull($b->getOwner());
        $this->assertFalse($b->hasOwner());
    }

    public function testAttachWrongOwnerThrows()
    {
        $b = new TestCloneableBehavior();
        $this->expectException(TInvalidOperationException::class);
        $b->attach(new \stdClass());
    }

    public function testAttachTwiceSameOwnerThrows()
    {
        $b = new TestCloneableBehavior();
        $c = new TComponent();
        $b->attach($c);
        $this->expectException(TInvalidOperationException::class);
        $b->attach($c);
    }

    public function testDetachWithoutOwnerThrows()
    {
        $b = new TestCloneableBehavior();
        $this->expectException(TInvalidOperationException::class);
        $b->detach(new TComponent());
    }

    public function testDetachWrongOwnerThrows()
    {
        $b = new TestCloneableBehavior();
        $c1 = new TComponent();
        $c2 = new TComponent();
        $b->attach($c1);
        $this->expectException(TInvalidOperationException::class);
        $b->detach($c2);
    }

    public function testGetOwnersAndIsOwner()
    {
        $b = new TestCloneableBehavior();
        $c = new TComponent();
        $b->attach($c);
        $owners = $b->getOwners();
        $this->assertEquals([$c], $owners);
        $this->assertTrue($b->isOwner($c));
        $this->assertFalse($b->isOwner(new TComponent()));
    }

    public function testCloneResetsOwner()
    {
        $b = new class extends TestCloneableBehavior {
            public function exposeClone(): array
            {
                return $this->cloneAndReturnOwnerState();
            }
        };
        $c = new TComponent();
        $b->attach($c);
        $state = $b->exposeClone();
        $this->assertNull($state['owner']);
        $this->assertFalse($state['hasOwner']);
    }

    public function testKeyOverrideDefaultKeyGetter()
    {
        $b = new class extends TestCloneableBehavior {
            protected function getWithoutOwnerExceptionKey(): string
            {
                return 'my_key';
            }
            public function getKeyPublic(): string
            {
                return $this->getWithoutOwnerExceptionKey();
            }
        };
        $this->assertEquals('my_key', $b->getKeyPublic());
    }

    public function testGetOwnersWithoutOwner()
    {
        $b = new TBehavior();
        $this->assertSame([], $b->getOwners());
        $this->assertNull($b->getOwner());
        $this->assertFalse($b->isOwner(new TComponent()));
    }

    public function testAttachNonComponentThrows()
    {
        $b = new TBehavior();
        try {
            $b->attach(new \stdClass());
            $this->fail('attach() did not throw for an object that is not a TComponent.');
        } catch (TInvalidOperationException $e) {
            $this->assertSame('behavior_bad_attach_component', $e->getErrorCode());
        }
    }

    public function testEnableDisableBehaviorsSyncsHandlers()
    {
        $owner = new TBehaviorTestOwner();
        $b = new TBehaviorTestHandlers();
        $owner->attachBehavior('b', $b);
        $this->assertSame(1, $owner->getEventHandlers('onMyEvent')->getCount());

        $owner->disableBehaviors();
        $this->assertSame(0, $owner->getEventHandlers('onMyEvent')->getCount());
        $owner->enableBehaviors();
        $this->assertSame(1, $owner->getEventHandlers('onMyEvent')->getCount());

        $owner->disableBehaviors();
        $b->dyEnableBehaviors();
        $this->assertSame(0, $owner->getEventHandlers('onMyEvent')->getCount());
        $b->setRetainDisabledHandlers(true);
        $b->dyDisableBehaviors();
        $this->assertSame(1, $owner->getEventHandlers('onMyEvent')->getCount());
    }

    public function testHandlersStatusOfAnotherComponent()
    {
        $owner = new TBehaviorTestOwner();
        $other = new TComponent();
        $b = new TBehaviorTestHandlers();
        $owner->attachBehavior('b', $b);

        $this->assertTrue(PradoUnit::invoke($b, 'getHandlersStatus'));
        $this->assertTrue(PradoUnit::invoke($b, 'getHandlersStatus', $owner));
        $this->assertNull(PradoUnit::invoke($b, 'getHandlersStatus', $other));
        $this->assertFalse(PradoUnit::invoke($b, 'setHandlersStatus', $other, false));
        $this->assertFalse(PradoUnit::invoke($b, 'setHandlersStatus', $owner, true));
    }

    public function testSerializeDropsTheOwner()
    {
        $owner = new TComponent();
        $b = new TBehavior();
        $owner->attachBehavior('b', $b);

        $copy = unserialize(serialize($b));
        $this->assertFalse($copy->hasOwner());
        $this->assertNull($copy->getName());
        $this->assertFalse(PradoUnit::invoke($copy, 'getHandlersStatus'));
    }

    public function testDetachNullOwnerThrows()
    {
        $owner = new TComponent();
        $b = new TBehavior();
        $owner->attachBehavior('b', $b);
        try {
            $b->detach(null);
            $this->fail('detach() did not throw for a null owner.');
        } catch (TInvalidOperationException $e) {
            $this->assertSame('behavior_detach_wrong_owner', $e->getErrorCode());
        }
    }
}
