<?php

use Prado\Collections\TMap;
use Prado\Collections\TWebAttributeCollection;

class TWebAttributeCollectionTest extends PHPUnit\Framework\TestCase
{
	protected $collection;

	protected function setUp(): void
	{
		$this->collection = new TWebAttributeCollection('data');
	}

	protected function tearDown(): void
	{
		$this->collection = null;
	}

	public function testConstruct()
	{
		$attrs = new TWebAttributeCollection('data');
		self::assertEquals('data', $attrs->getPrefix());
		self::assertEquals('data-', $attrs->getPrefixDash());

		$attrs2 = new TWebAttributeCollection('aria');
		self::assertEquals('aria', $attrs2->getPrefix());
		self::assertEquals('aria-', $attrs2->getPrefixDash());

		$attrs3 = new TWebAttributeCollection('');
		self::assertEquals('', $attrs3->getPrefix());
		self::assertEquals('', $attrs3->getPrefixDash());

		$attrs4 = new TWebAttributeCollection('data', ['key' => 'value']);
		self::assertEquals('value', $attrs4->itemAt('key'));
	}
	
	public function testPrefixLowercase()
	{
		$attrs = new TWebAttributeCollection('DATA');
		self::assertEquals('data', $attrs->getPrefix());
		self::assertEquals('data-', $attrs->getPrefixDash());
	}
	
	public function testPrefixSuffixDash()
	{
		$attrs = new TWebAttributeCollection('DATA-');
		self::assertEquals('data', $attrs->getPrefix());
		self::assertEquals('data-', $attrs->getPrefixDash());
	}

	public function testItemAtWithPrefix()
	{
		$this->collection->add('id', 'myElement');
		self::assertEquals('myElement', $this->collection->itemAt('data-id'));
	}

	public function testAddWithPrefix()
	{
		$key = $this->collection->add('id', 'myElement');
		self::assertEquals('data-id', $key);
		self::assertTrue($this->collection->contains('data-id'));

		$key2 = $this->collection->add('data-custom', 'value');
		self::assertEquals('data-custom', $key2);
	}

	public function testAddWithoutPrefix()
	{
		$attrs = new TWebAttributeCollection('');
		$key = $attrs->add('id', 'myElement');
		self::assertEquals('id', $key);
		self::assertTrue($attrs->contains('id'));
	}

	public function testRemoveWithPrefix()
	{
		$this->collection->add('id', 'myElement');
		$removed = $this->collection->remove('id');
		self::assertEquals('myElement', $removed);
		self::assertFalse($this->collection->contains('data-id'));
	}

	public function testContainsWithPrefix()
	{
		$this->collection->add('id', 'myElement');
		self::assertTrue($this->collection->contains('data-id'));
		self::assertTrue($this->collection->contains('id'));
		self::assertFalse($this->collection->contains('nonexistent'));
	}

	public function testGetPrefix()
	{
		self::assertEquals('data', $this->collection->getPrefix());
		$attrs = new TWebAttributeCollection('');
		self::assertEquals('', $attrs->getPrefix());
	}

	public function testGetPrefixDash()
	{
		self::assertEquals('data-', $this->collection->getPrefixDash());
		$attrs = new TWebAttributeCollection('');
		self::assertEquals('', $attrs->getPrefixDash());
	}

	public function testHasAttribute()
	{
		$this->collection->add('id', 'myElement');
		self::assertTrue($this->collection->hasAttribute('id'));
		self::assertTrue($this->collection->hasAttribute('data-id'));

		$this->collection->setAttribute('empty', 'value');
		self::assertTrue($this->collection->hasAttribute('empty'));
		self::assertTrue($this->collection->hasAttribute('data-empty'));

		self::assertFalse($this->collection->hasAttribute('nonexistent'));
		self::assertFalse($this->collection->hasAttribute('data-nonexistent'));
	}

	public function testGetAttribute()
	{
		$this->collection->add('id', 'myElement');
		self::assertEquals('myElement', $this->collection->getAttribute('id'));
		self::assertEquals('myElement', $this->collection->getAttribute('data-id'));

		self::assertNull($this->collection->getAttribute('nonexistent'));
	}

	public function testSetAttribute()
	{
		$this->collection->setAttribute('id', 'myElement');
		self::assertEquals('myElement', $this->collection->getAttribute('data-id'));

		$this->collection->setAttribute('name', '  trimmed  ');
		self::assertEquals('trimmed', $this->collection->getAttribute('data-name'));

		$this->collection->setAttribute('toRemove', '');
		self::assertFalse($this->collection->contains('data-to-remove'));
	}

	public function testSetAttributeTrimsValue()
	{
		$this->collection->setAttribute('id', '  value  ');
		self::assertEquals('value', $this->collection->getAttribute('data-id'));
	}

	public function testClearAttribute()
	{
		$this->collection->setAttribute('id', 'myElement');
		$this->collection->clearAttribute('id');
		self::assertFalse($this->collection->contains('data-id'));
	}

	public function testGetAttributes()
	{
		$this->collection->setAttribute('id', 'myElement');
		$this->collection->setAttribute('name', 'test');
		$attrs = $this->collection->getAttributes();
		self::assertEquals(['data-id' => 'myElement', 'data-name' => 'test'], $attrs);
	}

	public function testReset()
	{
		$this->collection->setAttribute('id', 'myElement');
		$this->collection->setAttribute('name', 'test');
		$this->collection->reset();
		self::assertEquals(0, $this->collection->getCount());
	}

	public function testNormalizedDataAttributePrefix_NoCamelCase()
	{
		self::assertEquals('data-id', $this->collection->normalizeDataAttributePrefix('id'));
		self::assertEquals('data-id', $this->collection->normalizeDataAttributePrefix('Id'));
		
		//
		self::assertEquals('data--id', $this->collection->normalizeDataAttributePrefix('-id'));
		self::assertEquals('data--id', $this->collection->normalizeDataAttributePrefix('-Id'));
		self::assertEquals('data--id', $this->collection->normalizeDataAttributePrefix('_id'));
		self::assertEquals('data--id', $this->collection->normalizeDataAttributePrefix('_Id'));
		
		self::assertEquals('data-id', $this->collection->normalizeDataAttributePrefix('data-id'));
		self::assertEquals('data-id', $this->collection->normalizeDataAttributePrefix('data-Id'));
		self::assertEquals('data-id', $this->collection->normalizeDataAttributePrefix('data_id'));
		self::assertEquals('data-id', $this->collection->normalizeDataAttributePrefix('data_Id'));
		
		self::assertEquals('data-id', $this->collection->normalizeDataAttributePrefix('DATA-id'));
		self::assertEquals('data-id', $this->collection->normalizeDataAttributePrefix('DATA-Id'));
		self::assertEquals('data-id', $this->collection->normalizeDataAttributePrefix('DATA_id'));
		self::assertEquals('data-id', $this->collection->normalizeDataAttributePrefix('DATA_Id'));
		
		self::assertEquals('data-with-dash', $this->collection->normalizeDataAttributePrefix('with-dash'));
		self::assertEquals('data-with-under', $this->collection->normalizeDataAttributePrefix('with_under'));
		self::assertEquals('data-with-dash', $this->collection->normalizeDataAttributePrefix('data-with-dash'));
		self::assertEquals('data-with-under', $this->collection->normalizeDataAttributePrefix('data-with_under'));
		self::assertEquals('data-with-dash', $this->collection->normalizeDataAttributePrefix('data_with-dash'));
		self::assertEquals('data-with-under', $this->collection->normalizeDataAttributePrefix('data_with_under'));
		
		self::assertEquals('data-custom', $this->collection->normalizeDataAttributePrefix('custom'));
		self::assertEquals('data-custom', $this->collection->normalizeDataAttributePrefix('Custom'));
		self::assertEquals('data--custom', $this->collection->normalizeDataAttributePrefix('-custom'));
		self::assertEquals('data--custom', $this->collection->normalizeDataAttributePrefix('-Custom'));
		self::assertEquals('data--custom', $this->collection->normalizeDataAttributePrefix('_custom'));
		self::assertEquals('data--custom', $this->collection->normalizeDataAttributePrefix('_Custom'));
		
		self::assertEquals('data-camel-case', $this->collection->normalizeDataAttributePrefix('camelCase'));
		self::assertEquals('data-pascal-case', $this->collection->normalizeDataAttributePrefix('PascalCase'));
		self::assertEquals('data--camelcase', $this->collection->normalizeDataAttributePrefix('-camelCase'));
		self::assertEquals('data--pascalcase', $this->collection->normalizeDataAttributePrefix('-PascalCase'));
		self::assertEquals('data--camelcase', $this->collection->normalizeDataAttributePrefix('_camelCase'));
		self::assertEquals('data--pascalcase', $this->collection->normalizeDataAttributePrefix('_PascalCase'));
		
		self::assertEquals('data-camel-case', $this->collection->normalizeDataAttributePrefix('data-camelCase'));
		self::assertEquals('data-pascal-case', $this->collection->normalizeDataAttributePrefix('data-PascalCase'));
		self::assertEquals('data-camel-case', $this->collection->normalizeDataAttributePrefix('data_camelCase'));
		self::assertEquals('data-pascal-case', $this->collection->normalizeDataAttributePrefix('data_PascalCase'));
		self::assertEquals('data--camelcase', $this->collection->normalizeDataAttributePrefix('data--camelCase'));
		self::assertEquals('data--pascalcase', $this->collection->normalizeDataAttributePrefix('data--PascalCase'));
		self::assertEquals('data--camelcase', $this->collection->normalizeDataAttributePrefix('data-_camelCase'));
		self::assertEquals('data--pascalcase', $this->collection->normalizeDataAttributePrefix('data-_PascalCase'));
		self::assertEquals('data--camelcase', $this->collection->normalizeDataAttributePrefix('data_-camelCase'));
		self::assertEquals('data--pascalcase', $this->collection->normalizeDataAttributePrefix('data_-PascalCase'));
		self::assertEquals('data--camelcase', $this->collection->normalizeDataAttributePrefix('data__camelCase'));
		self::assertEquals('data--pascalcase', $this->collection->normalizeDataAttributePrefix('data__PascalCase'));

		$attrs = new TWebAttributeCollection('');
		self::assertEquals('id', $attrs->normalizeDataAttributePrefix('id'));
	}
	
	public function testNormalizedDataAttributePrefix_NonDefaultCamelCase()
	{
		$altCamelCase = false;

		self::assertEquals('data-id', $this->collection->normalizeDataAttributePrefix('id', $altCamelCase));
		self::assertEquals('data-id', $this->collection->normalizeDataAttributePrefix('Id', $altCamelCase));
		
		//
		self::assertEquals('data--id', $this->collection->normalizeDataAttributePrefix('-id', $altCamelCase));
		self::assertEquals('data--id', $this->collection->normalizeDataAttributePrefix('-Id', $altCamelCase));
		self::assertEquals('data--id', $this->collection->normalizeDataAttributePrefix('_id', $altCamelCase));
		self::assertEquals('data--id', $this->collection->normalizeDataAttributePrefix('_Id', $altCamelCase));
		
		self::assertEquals('data-id', $this->collection->normalizeDataAttributePrefix('data-id', $altCamelCase));
		self::assertEquals('data-id', $this->collection->normalizeDataAttributePrefix('data-Id', $altCamelCase));
		self::assertEquals('data-id', $this->collection->normalizeDataAttributePrefix('data_id', $altCamelCase));
		self::assertEquals('data-id', $this->collection->normalizeDataAttributePrefix('data_Id', $altCamelCase));
		
		self::assertEquals('data-id', $this->collection->normalizeDataAttributePrefix('DATA-id', $altCamelCase));
		self::assertEquals('data-id', $this->collection->normalizeDataAttributePrefix('DATA-Id', $altCamelCase));
		self::assertEquals('data-id', $this->collection->normalizeDataAttributePrefix('DATA_id', $altCamelCase));
		self::assertEquals('data-id', $this->collection->normalizeDataAttributePrefix('DATA_Id', $altCamelCase));
		
		self::assertEquals('data-with-dash', $this->collection->normalizeDataAttributePrefix('with-dash', $altCamelCase));
		self::assertEquals('data-with-under', $this->collection->normalizeDataAttributePrefix('with_under', $altCamelCase));
		self::assertEquals('data-with-dash', $this->collection->normalizeDataAttributePrefix('data-with-dash', $altCamelCase));
		self::assertEquals('data-with-under', $this->collection->normalizeDataAttributePrefix('data-with_under', $altCamelCase));
		self::assertEquals('data-with-dash', $this->collection->normalizeDataAttributePrefix('data_with-dash', $altCamelCase));
		self::assertEquals('data-with-under', $this->collection->normalizeDataAttributePrefix('data_with_under', $altCamelCase));
		
		self::assertEquals('data-custom', $this->collection->normalizeDataAttributePrefix('custom', $altCamelCase));
		self::assertEquals('data-custom', $this->collection->normalizeDataAttributePrefix('Custom', $altCamelCase));
		self::assertEquals('data--custom', $this->collection->normalizeDataAttributePrefix('-custom', $altCamelCase));
		self::assertEquals('data--custom', $this->collection->normalizeDataAttributePrefix('-Custom', $altCamelCase));
		self::assertEquals('data--custom', $this->collection->normalizeDataAttributePrefix('_custom', $altCamelCase));
		self::assertEquals('data--custom', $this->collection->normalizeDataAttributePrefix('_Custom', $altCamelCase));
		
		self::assertEquals('data-camelcase', $this->collection->normalizeDataAttributePrefix('camelCase', $altCamelCase));
		self::assertEquals('data-pascalcase', $this->collection->normalizeDataAttributePrefix('PascalCase', $altCamelCase));
		self::assertEquals('data--camelcase', $this->collection->normalizeDataAttributePrefix('-camelCase', $altCamelCase));
		self::assertEquals('data--pascalcase', $this->collection->normalizeDataAttributePrefix('-PascalCase', $altCamelCase));
		self::assertEquals('data--camelcase', $this->collection->normalizeDataAttributePrefix('_camelCase', $altCamelCase));
		self::assertEquals('data--pascalcase', $this->collection->normalizeDataAttributePrefix('_PascalCase', $altCamelCase));
		
		self::assertEquals('data-camelcase', $this->collection->normalizeDataAttributePrefix('data-camelCase', $altCamelCase));
		self::assertEquals('data-pascalcase', $this->collection->normalizeDataAttributePrefix('data-PascalCase', $altCamelCase));
		self::assertEquals('data-camelcase', $this->collection->normalizeDataAttributePrefix('data_camelCase', $altCamelCase));
		self::assertEquals('data-pascalcase', $this->collection->normalizeDataAttributePrefix('data_PascalCase', $altCamelCase));
		self::assertEquals('data--camelcase', $this->collection->normalizeDataAttributePrefix('data--camelCase', $altCamelCase));
		self::assertEquals('data--pascalcase', $this->collection->normalizeDataAttributePrefix('data--PascalCase', $altCamelCase));
		self::assertEquals('data--camelcase', $this->collection->normalizeDataAttributePrefix('data-_camelCase', $altCamelCase));
		self::assertEquals('data--pascalcase', $this->collection->normalizeDataAttributePrefix('data-_PascalCase', $altCamelCase));
		self::assertEquals('data--camelcase', $this->collection->normalizeDataAttributePrefix('data_-camelCase', $altCamelCase));
		self::assertEquals('data--pascalcase', $this->collection->normalizeDataAttributePrefix('data_-PascalCase', $altCamelCase));
		self::assertEquals('data--camelcase', $this->collection->normalizeDataAttributePrefix('data__camelCase', $altCamelCase));
		self::assertEquals('data--pascalcase', $this->collection->normalizeDataAttributePrefix('data__PascalCase', $altCamelCase));
	
		$attrs = new TWebAttributeCollection('');
		self::assertEquals('id', $attrs->normalizeDataAttributePrefix('id', $altCamelCase));
	}

	public function testStripDataAttributePrefix()
	{
		self::assertEquals('id', $this->collection->stripDataAttributePrefix('data-id'));
		self::assertEquals('custom', $this->collection->stripDataAttributePrefix('data-custom'));
		self::assertEquals('custom-', $this->collection->stripDataAttributePrefix('data-custom-'));
		self::assertEquals('custom-id', $this->collection->stripDataAttributePrefix('data-custom-id'));
		self::assertEquals('custom-case', $this->collection->stripDataAttributePrefix('data-custom-Case'));
		self::assertEquals('without', $this->collection->stripDataAttributePrefix('without'));
		self::assertEquals('without-dash', $this->collection->stripDataAttributePrefix('without-dash'));
		
		self::assertEquals('without', $this->collection->stripDataAttributePrefix('without'));
		self::assertEquals('without-dash', $this->collection->stripDataAttributePrefix('without-dash'));
		
		self::assertEquals('id', $this->collection->stripDataAttributePrefix('data-id', true));
		self::assertEquals('custom', $this->collection->stripDataAttributePrefix('data-custom', true));
		self::assertEquals('custom', $this->collection->stripDataAttributePrefix('data-custom-', true));
		self::assertEquals('customId', $this->collection->stripDataAttributePrefix('data-custom-id', true));
		self::assertEquals('customCase', $this->collection->stripDataAttributePrefix('data-custom-Case', true));
		self::assertEquals('customCaseAttributeWidth', $this->collection->stripDataAttributePrefix('data-custom-Case-attribute-width', true));
		self::assertEquals('without', $this->collection->stripDataAttributePrefix('without', true));
		self::assertEquals('withoutDash', $this->collection->stripDataAttributePrefix('without-dash', true));

		$attrs = new TWebAttributeCollection('');
		self::assertEquals('data-id', $attrs->stripDataAttributePrefix('data-id'));
		self::assertEquals('id', $attrs->stripDataAttributePrefix('id'));
	}

	public function testAddAttributesToRender()
	{
		$writer = new class () {
			public $attributes = [];
			public function addAttributes($attrs)
			{
				$this->attributes = $attrs;
			}
		};
		$this->collection->setAttribute('id', 'myElement');
		$this->collection->addAttributesToRender($writer);
		self::assertEquals(['data-id' => 'myElement'], $writer->attributes);
	}

	public function testMagicGetSetMethods()
	{
		$this->collection->setAttributeId('myId');
		self::assertEquals('myId', $this->collection->getAttributeId());

		$this->collection->setCustomValue('custom');
		self::assertEquals('custom', $this->collection->getCustomValue());
	}

	public function testMagicPropertyAccess()
	{
		$this->collection->id = 'myElement';
		self::assertEquals('myElement', $this->collection->id);

		$this->collection->name = 'test';
		self::assertEquals('test', $this->collection->name);
	}

	public function testMagicPropertyAccessUnderscores()
	{
		$this->collection->custom_value = 'underScore';
		self::assertEquals('underScore', $this->collection->custom_value);

		$this->collection->another_value = 'test2';
		self::assertEquals('test2', $this->collection->another_value);
	}

	public function testMagicPropertyAccessCamelCase()
	{
		$this->collection->customValue = 'camelCase';
		self::assertEquals('camelCase', $this->collection->customValue);

		$this->collection->anotherValue = 'test2';
		self::assertEquals('test2', $this->collection->anotherValue);
	}

	public function testCanGetProperty()
	{
		self::assertTrue($this->collection->canGetProperty('anyProperty'));
	}

	public function testCanSetProperty()
	{
		self::assertTrue($this->collection->canSetProperty('anyProperty'));
	}

	public function testHasMethod()
	{
		self::assertTrue($this->collection->hasMethod('getPrefixed'));
		self::assertTrue($this->collection->hasMethod('setPrefixed'));
		self::assertFalse($this->collection->hasMethod('someMethod'));
	}

	public function testArrayAccess()
	{
		$this->collection['id'] = 'myElement';
		self::assertEquals('myElement', $this->collection['id']);
		self::assertEquals('myElement', $this->collection['data-id']);

		unset($this->collection['id']);
		self::assertFalse($this->collection->contains('data-id'));
	}

	public function testSerialization()
	{
		$attrs = new TWebAttributeCollection('data');
		$attrs->setAttribute('id', 'test');
		$attrs->setAttribute('name', 'value');

		$serialized = serialize($attrs);
		$unserialized = unserialize($serialized);

		self::assertEquals('test', $unserialized->getAttribute('id'));
		self::assertEquals('value', $unserialized->getAttribute('name'));
		self::assertEquals('data', $unserialized->getPrefix());
	}

	public function testAriaPrefix()
	{
		$attrs = new TWebAttributeCollection('aria');
		$attrs->setAttribute('label', 'Test Label');
		self::assertEquals('Test Label', $attrs->getAttribute('aria-label'));
		self::assertEquals('Test Label', $attrs->getAttribute('label'));
	}

	public function testNoPrefixArrayConstructor()
	{
		$attrs = new TWebAttributeCollection('', ['id' => 'test', 'name' => 'value']);
		self::assertEquals('test', $attrs->getAttribute('id'));
		self::assertEquals('value', $attrs->getAttribute('name'));
	}

	public function testEmptyValueRemovesAttribute()
	{
		$this->collection->setAttribute('id', 'original');
		$this->collection->setAttribute('id', '');
		self::assertFalse($this->collection->hasAttribute('id'));
	}

	public function testReadOnlyMap()
	{
		$attrs = new TWebAttributeCollection('data');
		$attrs->setReadOnly(true);
		self::assertTrue($attrs->getReadOnly());
	}

	public function testMergeWith()
	{
		$this->collection->setAttribute('id', 'first');
		$array = ['data-name' => 'merged'];
		$this->collection->mergeWith($array);
		self::assertEquals('first', $this->collection->getAttribute('id'));
		self::assertEquals('merged', $this->collection->getAttribute('name'));
	}
}
