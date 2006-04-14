<?php

require_once(dirname(__FILE__).'/BaseTest.php');

/**
 * @package System.DataAccess.SQLMap
 */
class SqlMapCacheTest extends PHPUnit2_Framework_TestCase
{
	function testFIFOCache()
	{
		$fifo = new TSqlMapFifoCache(2);
		$object1 = new TSqlMapper;
		$object2 = new TComponent;
		$object3 = new TMapper;
		
		$key1 = 'key1';
		$key2 = 'key2';
		$key3 = 'key3';

		$fifo->set($key1, $object1);
		$fifo->set($key2, $object2);
		
		$this->assertTrue($object1 === $fifo->get($key1));
		$this->assertTrue($object2 === $fifo->get($key2));

		//object 1 should be removed
		$fifo->set($key3, $object3);

		$this->assertNull($fifo->get($key1));
		$this->assertTrue($object2 === $fifo->get($key2));
		$this->assertTrue($object3 === $fifo->get($key3));

		//object 2 should be removed
		$fifo->set($key1, $object1);

		$this->assertNull($fifo->get($key2));
		$this->assertTrue($object3 === $fifo->get($key3));
		$this->assertTrue($object1 === $fifo->get($key1));
	}

	function testLruCache()
	{
		$lru = new TSqlMapLruCache(2);

		$object1 = new TSqlMapper;
		$object2 = new TComponent;
		$object3 = new TMapper;
		
		$key1 = 'key1';
		$key2 = 'key2';
		$key3 = 'key3';

		$lru->set($key1, $object1);
		$lru->set($key2, $object2);

		$this->assertTrue($object2 === $lru->get($key2));
		$this->assertTrue($object1 === $lru->get($key1));
		
		//object 2 should be removed, i.e. least recently used
		$lru->set($key3, $object3);
		
		$this->assertNull($lru->get($key2));
		$this->assertTrue($object1 === $lru->get($key1));
		$this->assertTrue($object3 === $lru->get($key3));

		//object 1 will be removed
		$lru->set($key2, $object2);

		$this->assertNull($lru->get($key1));
		$this->assertTrue($object2 === $lru->get($key2));
		$this->assertTrue($object3 === $lru->get($key3));
	}
}


?>