<?php

require_once(dirname(__FILE__).'/common.php');

class testNode
{
	public $data='';
	public $parent=null;
	public $child=null;
	public function __construct($data)
	{
		$this->data=$data;
	}
}

class utPradoBase extends UnitTestCase
{
	public function testFrameworkPath()
	{
		$this->assertTrue(FRAMEWORK_DIR===Prado::getFrameworkPath());
	}

	public function testSerialization()
	{
		$object=new TComponent;
		$number=12345.123;
		$string='12345\'"';
		$array=array('123'=>123,'abc'=>'def');
		$this->assertFalse($object===Prado::unserialize(Prado::serialize($object)));
		$this->assertTrue(Prado::unserialize(Prado::serialize($object)) instanceof TComponent);
		$this->assertTrue($number===Prado::unserialize(Prado::serialize($number)));
		$this->assertTrue($string===Prado::unserialize(Prado::serialize($string)));
		$this->assertTrue($array===Prado::unserialize(Prado::serialize($array)));

		// test complex object reference structure  grandparent <-> parent <-> child
		$grandp=new testNode('grandp');
		$parent=new testNode('parent');
		$child=new testNode('child');
		$grandp->child=$parent;
		$parent->child=$child;
		$child->parent=$parent;
		$parent->parent=$grandp;

		$newGrandp=Prado::unserialize(Prado::serialize($grandp));
		$this->assertTrue($newGrandp!==$grandp);
		$this->assertTrue($newGrandp instanceof testNode);
		$this->assertTrue($newGrandp->parent===null);
		$this->assertTrue($newGrandp->data==='grandp');

		$newParent=$newGrandp->child;
		$this->assertTrue($newParent!==$parent);
		$this->assertTrue($newParent instanceof testNode);
		$this->assertTrue($newParent->parent===$newGrandp);
		$this->assertTrue($newParent->data==='parent');

		$newChild=$newParent->child;
		$this->assertTrue($newChild!==$child);
		$this->assertTrue($newChild instanceof testNode);
		$this->assertTrue($newChild->parent===$newParent);
		$this->assertTrue($newChild->child===null);
		$this->assertTrue($newChild->data==='child');
	}

	public function testCreateComponent()
	{
		$this->assertTrue(Prado::createComponent('TComponent') instanceof TComponent);
		$this->assertTrue(Prado::createComponent('System.TComponent') instanceof TComponent);
		try
		{
			Prado::createComponent('System2.TComponent');
			$this->fail('exception not raised when creating a nonexistent component');
		}
		catch(TInvalidDataValueException $e)
		{
			$this->pass();
		}
	}

	public function testNamespace()
	{
		$this->assertTrue(FRAMEWORK_DIR===Prado::getPathOfAlias('System'));
		$this->assertTrue(Prado::getPathOfAlias('System2')===null);
		$testSystem=dirname(__FILE__).'/TestSystem';

		Prado::setPathOfAlias('TestSystem',$testSystem);
		$this->assertTrue(realpath($testSystem)===Prado::getPathOfAlias('TestSystem'));

		$this->assertTrue(Prado::getPathOfNamespace('TestSystem.*')===realpath($testSystem));
		$this->assertTrue(Prado::getPathOfNamespace('TestSystem.protected.*')===realpath($testSystem).'/protected');

		// test repeatedly using the same namespaces
		Prado::using('System.*');
		Prado::using('System.*');
		Prado::using('System.TComponent');
		Prado::using('System.TComponent');

		try
		{
			Prado::using('System');
			$this->fail('no exception raised when using an invalid namespace for a directory');
		}
		catch(TInvalidDataValueException $e)
		{
			$this->pass();
		}
		// TODO: using new namespaces to see if classes can be automatically loaded or found
	}
}

?>