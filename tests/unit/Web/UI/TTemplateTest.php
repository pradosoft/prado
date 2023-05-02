<?php



class TTemplateTest extends PHPUnit\Framework\TestCase
{
	protected $obj;
	
	private $_baseClass;

	protected function setUp(): void
	{
		$this->_baseClass = $this->getTestClass();
		$this->obj = new $this->_baseClass('', '');
	}

	protected function tearDown(): void
	{
		$this->obj = null;
	}
	
	protected function getTestClass()
	{
		return \Prado\Web\UI\TTemplate::class;
	}
	
	public function testConstruct()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testAttributeValidation()
	{
		$this->obj->setAttributeValidation(false);
		self::assertFalse($this->obj->getAttributeValidation());
		$this->obj->setAttributeValidation(true);
		self::assertTrue($this->obj->getAttributeValidation());
	}

	public function testGetTemplateFile()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testGetIsSourceTemplate()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testGetContextPath()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testGetDirective()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testGetHashCode()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testGetItems()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testInstantiateIn()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testGetIncludedFiles()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}
}
