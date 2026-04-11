<?php

use Prado\Exceptions\TInvalidOperationException;
use Prado\Util\TBaseBehavior;
use Prado\Util\TBehavior;

class StrictEventsBehavior extends TBehavior
{
	public function events()
	{
		return ['onMyEvent' => 'myHandler'];
	}
	public function myHandler($sender, $param)
	{
	}
}

// New helper/test scaffolding for assertWithoutOwner and default/override keys
class TestAssertBehavior extends TBehavior
{
    public function testAssertProp(string $prop, ?string $exceptionKey = null): void
    {
        $this->assertWithoutOwner($prop, $exceptionKey);
    }
    public function getDefaultKeyPublic(): string
    {
        return $this->getWithoutOwnerExceptionKey();
    }
}

class TestAssertBehaviorWithCustomKey extends TestAssertBehavior
{
    protected function getWithoutOwnerExceptionKey(): string
    {
        return 'custom_behavior_key';
    }
    public function getCustomKeyPublic(): string
    {
        return $this->getWithoutOwnerExceptionKey();
    }
    public function testAssertPropWithCustomKey(string $prop): void
    {
        $this->assertWithoutOwner($prop);
    }
}

class TestAssertClassBehavior extends Prado\Util\TClassBehavior
{
    public function testAssertProp(string $prop, ?string $exceptionKey = null): void
    {
        $this->assertWithoutOwner($prop, $exceptionKey);
    }
}

class TestAssertClassBehaviorWithCustomKey extends TestAssertClassBehavior
{
    protected function getWithoutOwnerExceptionKey(): string
    {
        return 'custom_classbehavior_key';
    }
    public function testAssertPropPublic(string $prop): void
    {
        $this->assertWithoutOwner($prop);
    }
    public function getKeyPublic(): string
    {
        return $this->getWithoutOwnerExceptionKey();
    }
}

class TestAssertClassBehaviorDefaultKey extends TestAssertClassBehavior
{
    public function getDefaultKeyPublic(): string
    {
        return $this->getWithoutOwnerExceptionKey();
    }
}

class NonStrictEventsBehavior extends StrictEventsBehavior
{
	public function getStrictEvents(): bool
	{
		return false;
	}
    
    public function events()
    {
        return ['onMyEvent' => 'myHandler'];
    }
    public function myHandler($sender, $param)
    {
    }
}

class TBaseBehaviorTest extends PHPUnit\Framework\TestCase
{	
	public function testMergeHandlers()
	{
		self::assertEquals([], TBaseBehavior::mergeHandlers());
		self::assertEquals([], TBaseBehavior::mergeHandlers([]));
		self::assertEquals([
			'onEvent1' => [ 'behaviorHandler' ],
			'onEvent2' => [$closure = function ($sender, $param) {}, [$this, 'testMergeHandlers']],
			'onEvent3' => ['behaviorHandler2', [$this, 'testMergeHandlers']],
		], TBaseBehavior::mergeHandlers( ['onEvent2' => $closure],
			['onEvent1' => 'behaviorHandler', 'onEvent2' => [$this, 'testMergeHandlers'], 'onEvent3' => ['behaviorHandler2', [$this, 'testMergeHandlers']]]));
	}
	
	public function testStrictEvents() 
	{
		// We cannot attach when behavior event handlers are strict.
		$component = new TComponent();
		$strictBehavior = new StrictEventsBehavior();
		self::assertTrue($strictBehavior->getStrictEvents());
		try {
			$component->attachBehavior('strict', $strictBehavior);
			self::fail("TInvalidOperationException not thrown when attaching strict behavior event handlers");
		} catch(TInvalidOperationException $e){
		}
	}
	
    public function testNonStrictEvents()
    {
        $component = new TComponent();
        // We can attach when behavior event handlers are not strict.
        $nonStrictBehavior = new NonStrictEventsBehavior();
        self::assertFalse($nonStrictBehavior->getStrictEvents());
        $component->attachBehavior('nonstrict', $nonStrictBehavior);
    }

    // New tests for assertWithoutOwner and default/override exception keys
    public function testAssertWithoutOwner_NoOwnerDoesNotThrow()
    {
        $b = new TestAssertBehavior();
        // Should not throw when there is no owner
        $b->testAssertProp('testProperty');
        $this->assertTrue(true);
    }

    public function testAssertWithoutOwner_WithOwnerThrowsDefaultKey()
    {
        $b = new TestAssertBehavior();
        $component = new TComponent();
        $b->attach($component);
        try {
            $b->testAssertProp('testProperty');
            $this->fail('Expected TInvalidOperationException to be thrown');
        } catch (TInvalidOperationException $e) {
            $this->assertInstanceOf(TInvalidOperationException::class, $e);
        }
    }

    public function testAssertWithoutOwner_WithOwnerThrowsCustomKey()
    {
        $b = new TestAssertBehaviorWithCustomKey();
        $component = new TComponent();
        $b->attach($component);
        try {
            $b->testAssertPropWithCustomKey('testProperty');
            $this->fail('Expected TInvalidOperationException to be thrown');
        } catch (TInvalidOperationException $e) {
            $this->assertInstanceOf(TInvalidOperationException::class, $e);
            $msg = $e->getMessage();
            $this->assertTrue(str_contains($msg, 'custom_behavior_key') || strpos($msg, 'testProperty') !== false);
        }
    }

    public function testDefaultKeyGetter()
    {
        $b = new TestAssertBehavior();
        $this->assertEquals('behavior_property_unchangeable', $b->getDefaultKeyPublic());
    }

    public function testCustomKeyGetter()
    {
        $b = new TestAssertBehaviorWithCustomKey();
        $this->assertEquals('custom_behavior_key', $b->getCustomKeyPublic());
    }

    public function testClassBehaviorAssert_NoOwner_DoesNotThrow()
    {
        $cb = new TestAssertClassBehavior();
        $cb->testAssertProp('prop');
        $this->assertTrue(true);
    }

    public function testClassBehaviorAssert_WithOwnerThrowsDefaultKey()
    {
        $cb = new TestAssertClassBehavior();
        $component = new TComponent();
        $cb->attach($component);
        try {
            $cb->testAssertProp('prop');
            $this->fail('Expected TInvalidOperationException');
        } catch (TInvalidOperationException $e) {
            $this->assertInstanceOf(TInvalidOperationException::class, $e);
        }
    }

    public function testClassBehaviorOverrideKey()
    {
        $cb = new TestAssertClassBehaviorWithCustomKey();
        $component = new TComponent();
        $cb->attach($component);
        try {
            $cb->testAssertPropPublic('prop');
            $this->fail('Expected TInvalidOperationException');
        } catch (TInvalidOperationException $e) {
            $this->assertInstanceOf(TInvalidOperationException::class, $e);
            $msg = $e->getMessage();
            $this->assertTrue(str_contains($msg, 'custom_classbehavior_key') || strpos($msg, 'prop') !== false);
        }
    }

    public function testClassBehaviorDefaultKeyGetter()
    {
        $cb = new TestAssertClassBehaviorDefaultKey();
        $this->assertEquals('behavior_property_unchangeable', $cb->getDefaultKeyPublic());
    }
}
