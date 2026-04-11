<?php

use Prado\Util\TClassBehavior;
use Prado\TComponent;
use Prado\Exceptions\TInvalidOperationException;

class TestClassBehaviorKey extends TClassBehavior
{
    public function getKeyPublic(): string
    {
        return $this->getWithoutOwnerExceptionKey();
    }
}

class TClassBehaviorTest extends PHPUnit\Framework\TestCase
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
}
