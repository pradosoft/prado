<?php

require_once(dirname(__FILE__).'/../common.php');

class CacheTestCase extends UnitTestCase
{
	private $_cache;

	public function getCache()
	{
		return $this->_cache;
	}

	public function setCache($cache)
	{
		$this->_cache=$cache;
	}

	public function basicOperations()
	{
		$object=new TComponent;
		$number=12345;
		$string='12345\'"';
		$array=array('123'=>123,'abc'=>'def');

		// test set (first time)
		$this->assertFalse($this->_cache->get('object'));
		$this->assertTrue($this->_cache->set('object',$object));
		$this->assertTrue($this->_cache->get('object') instanceof TComponent);
		$this->assertFalse($this->_cache->get('number'));
		$this->assertTrue($this->_cache->set('number',$number));
		$this->assertTrue($this->_cache->get('number')===$number);
		$this->assertFalse($this->_cache->get('string'));
		$this->assertTrue($this->_cache->set('string',$string));
		$this->assertTrue($this->_cache->get('string')===$string);
		$this->assertFalse($this->_cache->get('array'));
		$this->assertTrue($this->_cache->set('array',$array));
		$this->assertTrue($this->_cache->get('array')===$array);

		// test set (second time)
		$this->assertTrue($this->_cache->set('object',$array));
		$this->assertTrue($this->_cache->get('object')===$array);

		// test delete
		$this->assertTrue($this->_cache->delete('object'));
		$this->assertFalse($this->_cache->get('object'));
		$this->assertTrue($this->_cache->delete('number'));
		$this->assertFalse($this->_cache->get('number'));
		$this->assertTrue($this->_cache->delete('string'));
		$this->assertFalse($this->_cache->get('string'));
		$this->assertTrue($this->_cache->delete('array'));
		$this->assertFalse($this->_cache->get('array'));

		// test add (first time)
		$this->assertFalse($this->_cache->get('object'));
		$this->assertTrue($this->_cache->add('object',$object));
		$this->assertTrue($this->_cache->get('object') instanceof TComponent);
		$this->assertFalse($this->_cache->get('number'));
		$this->assertTrue($this->_cache->add('number',$number));
		$this->assertTrue($this->_cache->get('number')===$number);
		$this->assertFalse($this->_cache->get('string'));
		$this->assertTrue($this->_cache->add('string',$string));
		$this->assertTrue($this->_cache->get('string')===$string);
		$this->assertFalse($this->_cache->get('array'));
		$this->assertTrue($this->_cache->add('array',$array));
		$this->assertTrue($this->_cache->get('array')===$array);

		// test add (second time)
		$this->assertFalse($this->_cache->add('object',$array));
		$this->assertTrue($this->_cache->get('object') instanceof TComponent);

		// test replace
		$this->assertTrue($this->_cache->replace('object',$array));
		$this->assertTrue($this->_cache->get('object')===$array);
		$this->assertFalse($this->_cache->replace('object2',$array));
		$this->assertFalse($this->_cache->get('object2'));

		// test flush
		$this->assertTrue($this->_cache->set('number',$number));
		$this->assertTrue($this->_cache->get('number')===$number);
		$this->assertTrue($this->_cache->flush());
		$this->assertFalse($this->_cache->get('number'));

		// test expiring
		// set a value with 5sec valid time
		$this->_cache->set('expiring',123,3);
		$this->assertTrue($this->_cache->get('expiring')===123);
		$this->_cache->set('nonexpiring',456);
		$this->assertTrue($this->_cache->get('nonexpiring')===456);

		// wait 6sec to see if the value still exists
		sleep(4);
		$this->assertFalse($this->_cache->get('expiring'));
		$this->assertTrue($this->_cache->get('nonexpiring')===456);
	}
}

?>