<?php

require_once(__DIR__ . '/BaseCase.php');

class SqlMapCacheTest extends PHPUnit\Framework\TestCase
{
	public function testFIFOCache()
	{
		$fifo = new TSqlMapFifoCache();
		$fifo->setCacheSize(2);
		$object1 = new TSqlMapManager;
		$object2 = new TComponent;
		$object3 = new TSqlMapGateway(null);

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

	public function testLruCache()
	{
		$lru = new TSqlMapLruCache();
		$lru->setCacheSize(2);
		$object1 = new TSqlMapManager;
		$object2 = new TComponent;
		$object3 = new TSqlMapGateway(null);

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
