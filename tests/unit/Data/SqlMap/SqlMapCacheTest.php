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

	public function testFifoDelete()
	{
		$fifo = new TSqlMapFifoCache();
		$object1 = new TComponent;
		$object2 = new TSqlMapManager;
		$fifo->set('k1', $object1);
		$fifo->set('k2', $object2);

		// delete returns the removed object
		$removed = $fifo->delete('k1');
		$this->assertSame($object1, $removed);

		// key is gone
		$this->assertNull($fifo->get('k1'));
		// other key still present
		$this->assertSame($object2, $fifo->get('k2'));
	}

	public function testLruDelete()
	{
		$lru = new TSqlMapLruCache();
		$object1 = new TComponent;
		$object2 = new TSqlMapManager;
		$lru->set('k1', $object1);
		$lru->set('k2', $object2);

		$removed = $lru->delete('k1');
		$this->assertSame($object1, $removed);
		$this->assertNull($lru->get('k1'));
		$this->assertSame($object2, $lru->get('k2'));
	}

	public function testDeleteExistingReturnsObjectAndRemovesIt()
	{
		$fifo = new TSqlMapFifoCache();
		$obj = new TComponent();
		$fifo->set('k', $obj);

		$removed = $fifo->delete('k');
		$this->assertSame($obj, $removed);
		$this->assertNull($fifo->get('k'));
	}

	public function testFifoFlush()
	{
		$fifo = new TSqlMapFifoCache();
		$fifo->set('k1', new TComponent);
		$fifo->set('k2', new TSqlMapManager);

		$fifo->flush();
		$this->assertNull($fifo->get('k1'));
		$this->assertNull($fifo->get('k2'));
	}

	public function testLruFlush()
	{
		$lru = new TSqlMapLruCache();
		$lru->set('k1', new TComponent);
		$lru->set('k2', new TSqlMapManager);

		$lru->flush();
		$this->assertNull($lru->get('k1'));
		$this->assertNull($lru->get('k2'));
	}

	public function testAddThrowsException()
	{
		$fifo = new TSqlMapFifoCache();
		$this->expectException(\Prado\Data\SqlMap\DataMapper\TSqlMapException::class);
		$fifo->add('key', 'value');
	}

	public function testLruAddThrowsException()
	{
		$lru = new TSqlMapLruCache();
		$this->expectException(\Prado\Data\SqlMap\DataMapper\TSqlMapException::class);
		$lru->add('key', 'value');
	}

	public function testSetCacheSizeZeroResetsToHundred()
	{
		$fifo = new TSqlMapFifoCache();
		$fifo->setCacheSize(0);
		$this->assertSame(100, $fifo->getCacheSize());
	}

	public function testSetCacheSizePositive()
	{
		$fifo = new TSqlMapFifoCache();
		$fifo->setCacheSize(50);
		$this->assertSame(50, $fifo->getCacheSize());
	}

	public function testDefaultCacheSizeIsHundred()
	{
		$fifo = new TSqlMapFifoCache();
		$this->assertSame(100, $fifo->getCacheSize());
	}
}
