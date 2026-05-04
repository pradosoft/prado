<?php

use Prado\Util\TBehavior;
use Prado\TComponent;
use Prado\Exceptions\TInvalidOperationException;

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

class TBehaviorTest extends PHPUnit\Framework\TestCase
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
}
