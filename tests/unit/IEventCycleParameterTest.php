<?php

use Prado\IEventCycleParameter;
use Prado\IEventParameter;
use Prado\TComponent;
use Prado\TEventParameter;
use Prado\TEventResults;
use PHPUnit\Framework\TestCase;

/**
 * A minimal TComponent subclass that exposes a single 'OnTestEvent' event,
 * used to drive raiseEvent in all lifecycle tests.
 */
class EventCycleHost extends TComponent
{
	public function onTestEvent($param)
	{
		return $this->raiseEvent('OnTestEvent', $this, $param);
	}

	public function onTestEventWith($param, $responsetype = null, $postfunction = null)
	{
		return $this->raiseEvent('OnTestEvent', $this, $param, $responsetype, $postfunction);
	}
}

/**
 * A TEventParameter subclass that records every lifecycle call made to it,
 * so tests can assert call order, arguments, and counts.
 */
class RecordingEventParameter extends TEventParameter implements IEventCycleParameter
{
	public array $preCalls = [];
	public array $postCalls = [];

	public function preRaiseEvent($name, $sender, $param, $responsetype, $postfunction)
	{
		$this->preCalls[] = compact('name', 'sender', 'param', 'responsetype', 'postfunction');
	}

	public function postRaiseEvent($responses, $name, $sender, $param, $responsetype, $postfunction)
	{
		$this->postCalls[] = compact('responses', 'name', 'sender', 'param', 'responsetype', 'postfunction');
	}
}

/**
 * A plain PHP class (not a TComponent) that implements IEventCycleParameter directly,
 * used to test the non-TComponent branch of the raiseEvent check.
 */
class PlainCycleParameter implements IEventCycleParameter
{
	public string $_eventName = '';
	public array $preCalls = [];
	public array $postCalls = [];

	public function getEventName(): string
	{
		return $this->_eventName;
	}

	public function setEventName(string $value)
	{
		$this->_eventName = $value;
	}

	public function preRaiseEvent($name, $sender, $param, $responsetype, $postfunction)
	{
		$this->preCalls[] = compact('name', 'sender', 'param', 'responsetype', 'postfunction');
	}

	public function postRaiseEvent($responses, $name, $sender, $param, $responsetype, $postfunction)
	{
		$this->postCalls[] = compact('responses', 'name', 'sender', 'param', 'responsetype', 'postfunction');
	}
}

class IEventCycleParameterTest extends TestCase
{
	private EventCycleHost $host;

	protected function setUp(): void
	{
		$this->host = new EventCycleHost();
	}

	protected function tearDown(): void
	{
		$this->host->onTestEvent(null);
	}

	// ================================================================================
	// Interface Contract Tests
	// ================================================================================

	public function testInterfaceExtendsIEventParameter()
	{
		$this->assertTrue(is_a(IEventCycleParameter::class, IEventParameter::class, true));
	}

	public function testTEventParameterDoesNotImplementIEventCycleParameter()
	{
		$param = new TEventParameter();
		$this->assertNotInstanceOf(IEventCycleParameter::class, $param);
	}

	public function testPlainClassImplementsIEventCycleParameter()
	{
		$param = new PlainCycleParameter();
		$this->assertInstanceOf(IEventCycleParameter::class, $param);
	}

	// ================================================================================
	// IEventCycleParameter Concrete Implementation Tests
	// ================================================================================

	public function testPreRaiseEventStubDoesNotThrow()
	{
		// TEventParameter has no stubs — only subclasses implementing IEventCycleParameter
		// define pre/postRaiseEvent. Verify a concrete implementor can be called without throwing.
		$param = new RecordingEventParameter();
		$param->preRaiseEvent('ontestevent', $this->host, $param, null, null);
		$this->assertTrue(true);
	}

	public function testPostRaiseEventStubDoesNotThrow()
	{
		$param = new RecordingEventParameter();
		$param->postRaiseEvent([], 'ontestevent', $this->host, $param, null, null);
		$this->assertTrue(true);
	}

	// ================================================================================
	// raiseEvent calls preRaiseEvent and postRaiseEvent (TComponent param)
	// ================================================================================

	public function testPreRaiseEventCalledBeforeHandlers()
	{
		$order = [];
		$param = new RecordingEventParameter();
		// Record original pre so we can prepend to $order
		$param->preCalls = [];

		// Override preRaiseEvent via anonymous subclass to capture call order
		$param2 = new class extends RecordingEventParameter {
			public array $order = [];
			public function preRaiseEvent($name, $sender, $param, $responsetype, $postfunction)
			{
				parent::preRaiseEvent($name, $sender, $param, $responsetype, $postfunction);
				$this->order[] = 'pre';
			}
		};
		$this->host->attachEventHandler('OnTestEvent', function ($sender, $param) use (&$param2) {
			$param2->order[] = 'handler';
		});
		$this->host->onTestEvent($param2);

		$this->assertEquals(['pre', 'handler'], $param2->order);
	}

	public function testPostRaiseEventCalledAfterHandlers()
	{
		$param = new class extends RecordingEventParameter {
			public array $order = [];
			public function postRaiseEvent($responses, $name, $sender, $param, $responsetype, $postfunction)
			{
				parent::postRaiseEvent($responses, $name, $sender, $param, $responsetype, $postfunction);
				$this->order[] = 'post';
			}
		};
		$this->host->attachEventHandler('OnTestEvent', function ($sender, $p) use (&$param) {
			$param->order[] = 'handler';
		});
		$this->host->onTestEvent($param);

		$this->assertEquals(['handler', 'post'], $param->order);
	}

	public function testPreThenHandlersThenPost()
	{
		$param = new class extends RecordingEventParameter {
			public array $order = [];
			public function preRaiseEvent($name, $sender, $p, $responsetype, $postfunction)
			{
				$this->order[] = 'pre';
			}
			public function postRaiseEvent($responses, $name, $sender, $p, $responsetype, $postfunction)
			{
				$this->order[] = 'post';
			}
		};
		$this->host->attachEventHandler('OnTestEvent', function ($sender, $p) use (&$param) {
			$param->order[] = 'handler1';
		});
		$this->host->attachEventHandler('OnTestEvent', function ($sender, $p) use (&$param) {
			$param->order[] = 'handler2';
		});
		$this->host->onTestEvent($param);

		$this->assertEquals(['pre', 'handler1', 'handler2', 'post'], $param->order);
	}

	public function testPreAndPostCalledExactlyOnce()
	{
		$param = new RecordingEventParameter();
		$this->host->attachEventHandler('OnTestEvent', function ($sender, $p) {});
		$this->host->attachEventHandler('OnTestEvent', function ($sender, $p) {});
		$this->host->onTestEvent($param);

		$this->assertCount(1, $param->preCalls);
		$this->assertCount(1, $param->postCalls);
	}

	public function testPreAndPostCalledEvenWithNoHandlers()
	{
		$param = new RecordingEventParameter();
		$this->host->onTestEvent($param);

		$this->assertCount(1, $param->preCalls);
		$this->assertCount(1, $param->postCalls);
	}

	// ================================================================================
	// Arguments passed to preRaiseEvent
	// ================================================================================

	public function testPreRaiseEventReceivesCorrectName()
	{
		$param = new RecordingEventParameter();
		$this->host->onTestEvent($param);

		$this->assertEquals('ontestevent', $param->preCalls[0]['name']);
	}

	public function testPreRaiseEventReceivesCorrectSender()
	{
		$param = new RecordingEventParameter();
		$this->host->onTestEvent($param);

		$this->assertSame($this->host, $param->preCalls[0]['sender']);
	}

	public function testPreRaiseEventReceivesParamInstance()
	{
		$param = new RecordingEventParameter();
		$this->host->onTestEvent($param);

		$this->assertSame($param, $param->preCalls[0]['param']);
	}

	public function testPreRaiseEventReceivesResponsetype()
	{
		$param = new RecordingEventParameter();
		$this->host->onTestEventWith($param, TEventResults::EVENT_RESULT_ALL);

		$this->assertSame(TEventResults::EVENT_RESULT_ALL, $param->preCalls[0]['responsetype']);
	}

	public function testPreRaiseEventReceivesPostfunction()
	{
		$param = new RecordingEventParameter();
		$fn = static fn($s, $p, $c, $r) => $r;
		$this->host->onTestEventWith($param, null, $fn);

		$this->assertSame($fn, $param->preCalls[0]['postfunction']);
	}

	// ================================================================================
	// Arguments passed to postRaiseEvent
	// ================================================================================

	public function testPostRaiseEventReceivesResponses()
	{
		$param = new RecordingEventParameter();
		$this->host->attachEventHandler('OnTestEvent', fn($s, $p) => 'response1');
		$this->host->attachEventHandler('OnTestEvent', fn($s, $p) => 'response2');
		$this->host->onTestEvent($param);

		$this->assertContains('response1', $param->postCalls[0]['responses']);
		$this->assertContains('response2', $param->postCalls[0]['responses']);
	}

	public function testPostRaiseEventReceivesCorrectName()
	{
		$param = new RecordingEventParameter();
		$this->host->onTestEvent($param);

		$this->assertEquals('ontestevent', $param->postCalls[0]['name']);
	}

	public function testPostRaiseEventReceivesCorrectSender()
	{
		$param = new RecordingEventParameter();
		$this->host->onTestEvent($param);

		$this->assertSame($this->host, $param->postCalls[0]['sender']);
	}

	public function testPostRaiseEventResponsesAreFiltered()
	{
		// Default responsetype is EVENT_RESULT_FILTER — null responses are stripped
		$param = new RecordingEventParameter();
		$this->host->attachEventHandler('OnTestEvent', fn($s, $p) => null);
		$this->host->attachEventHandler('OnTestEvent', fn($s, $p) => 'kept');
		$this->host->onTestEvent($param);

		$responses = $param->postCalls[0]['responses'];
		$this->assertNotContains(null, $responses);
		$this->assertContains('kept', $responses);
	}

	// ================================================================================
	// Plain (non-TComponent) IEventCycleParameter
	// ================================================================================

	public function testPlainParamPreRaiseEventCalled()
	{
		$param = new PlainCycleParameter();
		$this->host->onTestEvent($param);

		$this->assertCount(1, $param->preCalls);
	}

	public function testPlainParamPostRaiseEventCalled()
	{
		$param = new PlainCycleParameter();
		$this->host->onTestEvent($param);

		$this->assertCount(1, $param->postCalls);
	}

	public function testPlainParamReceivesCorrectName()
	{
		$param = new PlainCycleParameter();
		$this->host->onTestEvent($param);

		$this->assertEquals('ontestevent', $param->preCalls[0]['name']);
	}

	public function testPlainParamPreAndPostCalledInOrder()
	{
		$order = [];
		$param = new class implements IEventCycleParameter {
			public string $_eventName = '';
			public array $order = [];
			public function getEventName(): string { return $this->_eventName; }
			public function setEventName(string $value) { $this->_eventName = $value; }
			public function preRaiseEvent($name, $sender, $param, $responsetype, $postfunction)
			{
				$this->order[] = 'pre';
			}
			public function postRaiseEvent($responses, $name, $sender, $param, $responsetype, $postfunction)
			{
				$this->order[] = 'post';
			}
		};
		$this->host->attachEventHandler('OnTestEvent', function ($s, $p) use (&$param) {
			$param->order[] = 'handler';
		});
		$this->host->onTestEvent($param);

		$this->assertEquals(['pre', 'handler', 'post'], $param->order);
	}

	// ================================================================================
	// Null param — no lifecycle calls
	// ================================================================================

	public function testNullParamDoesNotTriggerLifecycle()
	{
		// Simply must not throw — no IEventCycleParameter to call
		$this->host->onTestEvent(null);
		$this->assertTrue(true);
	}

	public function testNonCycleParamDoesNotTriggerLifecycle()
	{
		// A plain TEventParameter does not implement IEventCycleParameter, so
		// raiseEvent will not call preRaiseEvent/postRaiseEvent on it. The stub
		// methods exist for subclasses to override but do not trigger the lifecycle.
		// Verify this does not throw.
		$param = new TEventParameter('value');
		$this->host->onTestEvent($param);
		$this->assertTrue(true);
	}
}
