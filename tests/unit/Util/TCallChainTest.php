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
