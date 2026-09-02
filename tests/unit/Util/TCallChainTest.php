<?php


use Prado\Exceptions\TApplicationException;
use Prado\Util\TCallChain;

class TCallChainTest extends PHPUnit\Framework\TestCase
{	
	protected function setUp(): void
	{
	}

	protected function tearDown(): void
	{
	}
	
	public function testException()
	{
		$chain = new TCallChain('dySimpleMethod');
		
		$chain->dySimpleMethod();
		
		self::assertTrue(true);
		try {
			$chain->dyNoMethod();
			self::fail('Expected TApplicationException not thrown when invalid dynamic event raised.');
		} catch (TApplicationException $e) {
		}
	}

	protected $_order = [];
	public function testOrdering()
	{
		$chain = new TCallChain('dyMyMethod');
		
		$chain->addCall([$this, 'myTestCallback1'], [1, 1, 1]);
		$chain->addCall([$this, 'myTestCallback2'], [2, 2, 2]);
		$chain->addCall([$this, 'myTestCallback3'], [3, 3, 3]);
		$chain->addCall([$this, 'myTestCallbackCall4'], [4, 4, 4]);
		$chain->addCall([$this, 'myTestCallbackCall5'], [5, 5, 5]);
		$chain->addCall([$this, 'myTestCallbackCall6'], [6, 6, 6]);
		$chain->addCall([$this, 'myTestCallbackCall7'], [7, 7, 7]);
		$chain->addCall([$this, 'myTestCallbackCall8'], [8, 8, 8]);
		$chain->addCall([$this, 'myTestCallbackCall9'], [9, 9, 9]);
		$chain->call(0, 0, 0);
		
		$this->assertEquals([0, 0, 0, 1], $this->_order[0]);
		$this->assertEquals([0, 0, 0, 2], $this->_order[1]);
		$this->assertEquals([0, 0, 0, 3], $this->_order[2]);
		$this->assertEquals([0, 0, 0, 4], $this->_order[3]);
		$this->assertEquals([0, 0, 5, 5], $this->_order[4]);
		$this->assertEquals([0, 0, 6, 6], $this->_order[5]);
		$this->assertEquals([0, 0, 6, 7], $this->_order[6]);
		$this->assertEquals([0, 0, 6, 8], $this->_order[7]);
		$this->assertEquals([0, 0, 6, 9], $this->_order[8]);
	}
	
	public function testStopped()
	{
		$chain = new TCallChain('dyMyMethod');
		self::assertFalse($chain->getStopped());
		$chain->setStopped(true);
		self::assertTrue($chain->getStopped());
		$chain->setStopped(false);
		self::assertFalse($chain->getStopped());
		$chain->stopImmediatePropagation();
		self::assertTrue($chain->getStopped());
	}

	protected $_stopOrder = [];
	public function testStopChain()
	{
		$chain = new TCallChain('dyMyMethod');

		$chain->addCall([$this, 'myTestStopCallback1'], [1]);
		$chain->addCall([$this, 'myTestStopCallback2'], [2]);
		$chain->addCall([$this, 'myTestStopCallback3'], [3]);
		$chain->call(0);

		$this->assertEquals([1, 2], $this->_stopOrder);
	}

	public function myTestStopCallback1($param1, $callchain)
	{
		$this->_stopOrder[] = 1;
		return $callchain->dyMyMethod($param1);
	}

	public function myTestStopCallback2($param1, $callchain)
	{
		$this->_stopOrder[] = 2;
		$callchain->stopImmediatePropagation();
		return $callchain->dyMyMethod($param1);
	}

	public function myTestStopCallback3($param1, $callchain)
	{
		$this->_stopOrder[] = 3;
		return $callchain->dyMyMethod($param1);
	}

	protected $_filterOrder = [];
	public function testStopFilterChain()
	{
		$chain = new TCallChain('dyMyMethod');

		$chain->addCall([$this, 'myTestFilterCallback1'], [1]);
		$chain->addCall([$this, 'myTestFilterCallback2'], [2]);
		$chain->addCall([$this, 'myTestFilterCallback3'], [3]);
		$chain->call(0);

		// Handlers do not chain, so the loop would normally call all three; the
		// second stops it, so the third is never reached.
		$this->assertEquals([1, 2], $this->_filterOrder);
	}

	public function myTestFilterCallback1($param1, $callchain)
	{
		$this->_filterOrder[] = 1;
	}

	public function myTestFilterCallback2($param1, $callchain)
	{
		$this->_filterOrder[] = 2;
		$callchain->setStopped(true);
	}

	public function myTestFilterCallback3($param1, $callchain)
	{
		$this->_filterOrder[] = 3;
	}

	public function myTestCallback1($param1, $param2, $param3, $callchain)
	{
		$args = func_get_args();
		array_pop($args);
		array_push($args, 1);
		$this->_order[] = $args;
	}
	
	public function myTestCallback2($param1, $param2, $param3, $callchain)
	{
		$args = func_get_args();
		array_pop($args);
		array_push($args, 2);
		$this->_order[] = $args;
	}
	
	public function myTestCallback3($param1, $param2, $param3, $callchain)
	{
		$args = func_get_args();
		array_pop($args);
		array_push($args, 3);
		$this->_order[] = $args;
	}
	
	public function myTestCallbackCall4($param1, $param2, $param3, $callchain)
	{
		$args = func_get_args();
		array_pop($args);
		array_push($args, 4);
		$this->_order[] = $args;
		return $callchain->dyMyMethod($param1, $param2);
	}
	
	public function myTestCallbackCall5($param1, $param2, $param3, $callchain)
	{
		$args = func_get_args();
		array_pop($args);
		array_push($args, 5);
		$this->_order[] = $args;
		return $callchain->dyMyMethod($param1, $param2);
	}
	
	public function myTestCallbackCall6($param1, $param2, $param3, $callchain)
	{
		$args = func_get_args();
		array_pop($args);
		array_push($args, 6);
		$this->_order[] = $args;
		return $callchain->dyMyMethod($param1, $param2, $param3);
	}
	
	public function myTestCallbackCall7($param1, $param2, $param3, $callchain)
	{
		$args = func_get_args();
		array_pop($args);
		array_push($args, 7);
		$this->_order[] = $args;
		return $callchain->dyMyMethod($param1, $param2, $param3);
	}
	
	public function myTestCallbackCall8($param1, $param2, $param3, $callchain)
	{
		$args = func_get_args();
		array_pop($args);
		array_push($args, 8);
		$this->_order[] = $args;
		return $callchain->dyMyMethod($param1, $param2, $param3);
	}
	
	public function myTestCallbackCall9($param1, $param2, $param3, $callchain)
	{
		$args = func_get_args();
		array_pop($args);
		array_push($args, 9);
		$this->_order[] = $args;
		return $callchain->dyMyMethod($param1, $param2, $param3);
	}
}
