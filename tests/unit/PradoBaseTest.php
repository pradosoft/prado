<?php

use Prado\Prado;

/**
 * @package System
 */
class PradoBaseTest extends PHPUnit\Framework\TestCase
{
	const INTERFACE_FQN = 'Prado\\Web\\UI\\IValidatable';
	const INTERFACE_SHORT_NAME = 'IValidatable';
	const CLASS_FQN = 'Prado\\Web\\UI\\WebControls\\TButton';
	const CLASS_PRADO_FULLNAME = 'System.Web.UI.WebControls.TButton';

	public function testUsingNamespace()
	{
		$this->assertFalse(class_exists(self::CLASS_FQN, false));
		Prado::using(self::CLASS_FQN);
		$this->assertTrue(class_exists(self::CLASS_FQN, false));
	}

	public function testUsingInterface()
	{
		$this->assertFalse(interface_exists(self::INTERFACE_SHORT_NAME, false));
		Prado::using(self::INTERFACE_FQN);
		$this->assertTrue(interface_exists(self::INTERFACE_SHORT_NAME, false));
	}

	public function testCreateComponentWithNamespace()
	{
		$this->assertInstanceOf(self::CLASS_FQN, Prado::createComponent(self::CLASS_FQN));
	}

	public function testCreateComponentWithPradoNamespace()
	{
		$this->assertInstanceOf(self::CLASS_FQN, Prado::createComponent(self::CLASS_PRADO_FULLNAME));
	}
	

	public function testCreateComponentWithArray()
	{
		$this->assertInstanceOf(self::CLASS_FQN, $obj = Prado::createComponent(['class' =>self::CLASS_FQN, 'text' => 'my Title...']));
		$this->assertEquals('my Title...', $obj->getText());
	}
}
