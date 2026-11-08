<?php

namespace Prado\Test\Unit\Data\ActiveRecord\Scaffold;

use Prado\Data\ActiveRecord\Scaffold\TScaffoldBase;
use Prado\Exceptions\TConfigurationException;
use Prado\Prado;

/**
 * Exposes the protected record helpers of TScaffoldBase. The class has no template.
 */
class ScaffoldBaseProbe extends TScaffoldBase
{
	public function __call($method, $args)
	{
		if (str_starts_with($method, 'call')) {
			return $this->{lcfirst(substr($method, 4))}(...$args);
		}
		return parent::__call($method, $args);
	}
}

class TScaffoldBaseTest extends ScaffoldTestCase
{
	private function newProbe(): ScaffoldBaseProbe
	{
		$probe = new ScaffoldBaseProbe();
		$probe->setID('probe');
		$probe->setRecordClass(ScaffoldItemRecord::class);
		$this->add($probe);
		return $probe;
	}

	public function testRecordClass()
	{
		$probe = new ScaffoldBaseProbe();
		$this->assertNull($probe->getRecordClass());
		$probe->setRecordClass(ScaffoldItemRecord::class);
		$this->assertSame(ScaffoldItemRecord::class, $probe->getRecordClass());
	}

	public function testDefaultStyleProperties()
	{
		$probe = new ScaffoldBaseProbe();
		$this->assertSame('style', $probe->getDefaultStyle());
		$this->assertTrue($probe->getEnableDefaultStyle());

		$probe->setDefaultStyle('custom');
		$probe->setEnableDefaultStyle('false');
		$this->assertSame('custom', $probe->getDefaultStyle());
		$this->assertFalse($probe->getEnableDefaultStyle());

		$probe->setDefaultStyle('style');
		$probe->setEnableDefaultStyle(true);
		$this->assertSame('style', $probe->getDefaultStyle());
		$this->assertTrue($probe->getEnableDefaultStyle());
	}

	public function testPreRender_registersTheDefaultStyleSheet()
	{
		$probe = $this->newProbe();
		$probe->onPreRender(null);

		$sheets = $this->styleSheets();
		$this->assertCount(1, $sheets);
		$this->assertStringStartsWith('/assets/', $sheets[0]);
		$this->assertStringEndsWith('/style.css', $sheets[0]);
		$this->assertTrue($this->page->getClientScript()->isStyleSheetFileRegistered($sheets[0]));
	}

	public function testPreRender_prefersTheStyleSheetNextToTheSubclass()
	{
		$this->newProbe()->onPreRender(null);
		$styled = new Styled\StyledScaffoldProbe();
		$this->add($styled);
		$styled->onPreRender(null);

		$sheets = $this->styleSheets();
		$this->assertCount(2, $sheets);
		$this->assertNotSame(dirname($sheets[0]), dirname($sheets[1]));
		$published = Prado::getPathOfAlias('ScaffoldTestAssets') . substr($sheets[1], strlen('/assets'));
		$this->assertFileEquals(__DIR__ . '/Styled/style.css', $published);
	}

	public function testPreRender_withoutDefaultStyle()
	{
		$probe = $this->newProbe();
		$probe->setEnableDefaultStyle(false);
		$probe->onPreRender(null);

		$this->assertSame([], $this->styleSheets());
	}

	public function testRecordValues()
	{
		$probe = $this->newProbe();
		$record = ScaffoldItemRecord::finder()->findByPk(2);

		$this->assertSame([2, 'item 02', 'note 2', 2.5], $probe->callGetRecordPropertyValues($record));
		$this->assertSame([2], $probe->callGetRecordPkValues($record));
		$this->assertSame('scaffold_items', $probe->callGetTableInfo()->getTableName());
	}

	public function testRecordObject_newRecord()
	{
		$probe = $this->newProbe();

		$record = $probe->callGetRecordObject();
		$this->assertInstanceOf(ScaffoldItemRecord::class, $record);
		$this->assertNull($record->id);
		$this->assertSame($record, $probe->callGetRecordObject([3]), 'the record is kept until cleared');

		$probe->callClearRecordObject();
		$this->assertSame('item 03', $probe->callGetRecordObject([3])->item_name);
	}

	public function testRecordObject_setRecordObject()
	{
		$probe = $this->newProbe();
		$record = new ScaffoldItemRecord();

		$probe->callSetRecordObject($record);

		$this->assertSame($record, $probe->callGetRecordObject([1]));
	}

	public function testRecordObject_unknownKeyThrows()
	{
		$probe = $this->newProbe();

		$this->expectException(TConfigurationException::class);
		$this->expectExceptionMessage("primary key '42'");
		$probe->callGetRecordObject(42);
	}

	public function testRecordObject_withoutRecordClassThrows()
	{
		$probe = $this->newProbe();
		$probe->setRecordClass(null);

		$this->expectException(TConfigurationException::class);
		$this->expectExceptionMessage("Scaffold control 'probe' requires a RecordClass");
		$probe->callGetRecordObject();
	}

	public function testCopyFrom()
	{
		$source = $this->newProbe();
		$source->setEnableDefaultStyle(false);
		$record = new ScaffoldItemRecord();
		$source->callSetRecordObject($record);
		$target = new ScaffoldBaseProbe();

		$target->callCopyFrom($source);

		$this->assertSame(ScaffoldItemRecord::class, $target->getRecordClass());
		$this->assertFalse($target->getEnableDefaultStyle());
		$this->assertSame($record, $target->callGetRecordObject());
	}
}
