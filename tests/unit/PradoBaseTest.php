<?php

use Prado\Prado;

class MethodAccessibleTestClassA
{
	public function getPublicPropertyA()
	{
		return 'publicDataA';
	}
	protected function getProtectedPropertyA()
	{
		return 'protectedDataA';
	}
	private function getPrivatePropertyA()
	{
		return 'privateDataA';
	}
	
	//Access Self
	public function methodAccessibleAAccessPublicPropertyA()
	{
		return method_exists($this, 'getPublicPropertyA');
	}
	public function methodAccessibleAAccessProtectedPropertyA()
	{
		return method_exists($this, 'getProtectedPropertyA');
	}
	public function methodAccessibleAAccessPrivatePropertyA()
	{
		return method_exists($this, 'getPrivatePropertyA');
	}
	public function pradoMethodAccessibleAAccessPublicPropertyA()
	{
		return Prado::method_accessible($this, 'getPublicPropertyA');
	}
	public function pradoMethodAccessibleAAccessProtectedPropertyA()
	{
		return Prado::method_accessible($this, 'getProtectedPropertyA');
	}
	public function pradoMethodAccessibleAAccessPrivatePropertyA()
	{
		return Prado::method_accessible($this, 'getPrivatePropertyA');
	}
	
	//Access Child
	public function methodAccessibleAAccessPublicPropertyB()
	{
		return method_exists($this, 'getPublicPropertyB');
	}
	public function methodAccessibleAAccessProtectedPropertyB()
	{
		return method_exists($this, 'getProtectedPropertyB');
	}
	public function methodAccessibleAAccessPrivatePropertyB()
	{
		return method_exists($this, 'getPrivatePropertyB');
	}
	public function pradoMethodAccessibleAAccessPublicPropertyB()
	{
		return Prado::method_accessible($this, 'getPublicPropertyB');
	}
	public function pradoMethodAccessibleAAccessProtectedPropertyB()
	{
		return Prado::method_accessible($this, 'getProtectedPropertyB');
	}
	public function pradoMethodAccessibleAAccessPrivatePropertyB()
	{
		return Prado::method_accessible($this, 'getPrivatePropertyB');
	}
	
	public function testFromClassA($tester, $instance)
	{
		//  calling self from parent
		{ // Parent calls Parent Accesses Parent
			//	Normal method_exists
			$tester->assertTrue($instance->methodAccessibleAAccessPublicPropertyA());
			$tester->assertTrue($instance->methodAccessibleAAccessProtectedPropertyA());
			$tester->assertTrue($instance->methodAccessibleAAccessPrivatePropertyA());
			
			//	Prado method_exists
			$tester->assertTrue($instance->pradoMethodAccessibleAAccessPublicPropertyA());
			$tester->assertTrue($instance->pradoMethodAccessibleAAccessProtectedPropertyA());
			$tester->assertTrue($instance->pradoMethodAccessibleAAccessPrivatePropertyA());
		}
		
		{ // Parent calls Child Accesses child
			//	Normal method_exists
			$tester->assertTrue($instance->methodAccessibleBAccessPublicPropertyB());
			$tester->assertTrue($instance->methodAccessibleBAccessProtectedPropertyB());
			$tester->assertTrue($instance->methodAccessibleBAccessPrivatePropertyB());
			
			//	Prado method_exists
			$tester->assertTrue($instance->pradoMethodAccessibleBAccessPublicPropertyB());
			$tester->assertTrue($instance->pradoMethodAccessibleBAccessProtectedPropertyB());
			$tester->assertFalse($instance->pradoMethodAccessibleBAccessPrivatePropertyB(), "Parent cannot access child private method.");
		}
		
		
		{ // Parent calls Parent Accesses Child
			//	Normal method_exists
			$tester->assertTrue($instance->methodAccessibleAAccessPublicPropertyB());
			$tester->assertTrue($instance->methodAccessibleAAccessProtectedPropertyB());
			$tester->assertTrue($instance->methodAccessibleAAccessPrivatePropertyB());
			
			//	Prado method_exists
			$tester->assertTrue($instance->pradoMethodAccessibleAAccessPublicPropertyB());
			$tester->assertTrue($instance->pradoMethodAccessibleAAccessProtectedPropertyB());
			$tester->assertFalse($instance->pradoMethodAccessibleAAccessPrivatePropertyB());
		}
		
		
		{ // Parent calls Child Accesses Parent
			//	Normal method_exists
			$tester->assertTrue($instance->methodAccessibleBAccessPublicPropertyA());
			$tester->assertTrue($instance->methodAccessibleBAccessProtectedPropertyA());
			$tester->assertTrue($instance->methodAccessibleBAccessPrivatePropertyA());
			
			//	Prado method_exists
			$tester->assertTrue($instance->pradoMethodAccessibleBAccessPublicPropertyA());
			$tester->assertTrue($instance->pradoMethodAccessibleBAccessProtectedPropertyA());
			$tester->assertTrue($instance->pradoMethodAccessibleBAccessPrivatePropertyA());
		}
	}
}

class MethodAccessibleTestClassB extends MethodAccessibleTestClassA
{
	public function getPublicPropertyB()
	{
		return 'publicDataB';
	}
	protected function getProtectedPropertyB()
	{
		return 'protectedDataB';
	}
	private function getPrivatePropertyB()
	{
		return 'privateDataB';
	}
	
	//Access Self
	public function methodAccessibleBAccessPublicPropertyB()
	{
		return method_exists($this, 'getPublicPropertyB');
	}
	public function methodAccessibleBAccessProtectedPropertyB()
	{
		return method_exists($this, 'getProtectedPropertyB');
	}
	public function methodAccessibleBAccessPrivatePropertyB()
	{
		return method_exists($this, 'getPrivatePropertyB');
	}
	public function pradoMethodAccessibleBAccessPublicPropertyB()
	{
		return Prado::method_accessible($this, 'getPublicPropertyB');
	}
	public function pradoMethodAccessibleBAccessProtectedPropertyB()
	{
		return Prado::method_accessible($this, 'getProtectedPropertyB');
	}
	public function pradoMethodAccessibleBAccessPrivatePropertyB()
	{
		return Prado::method_accessible($this, 'getPrivatePropertyB');
	}
	
	// Access Parent
	public function methodAccessibleBAccessPublicPropertyA()
	{
		return method_exists($this, 'getPublicPropertyA');
	}
	public function methodAccessibleBAccessProtectedPropertyA()
	{
		return method_exists($this, 'getProtectedPropertyA');
	}
	public function methodAccessibleBAccessPrivatePropertyA()
	{
		return method_exists($this, 'getPrivatePropertyA');
	}
	public function pradoMethodAccessibleBAccessPublicPropertyA()
	{
		return Prado::method_accessible($this, 'getPublicPropertyA');
	}
	public function pradoMethodAccessibleBAccessProtectedPropertyA()
	{
		return Prado::method_accessible($this, 'getProtectedPropertyA');
	}
	public function pradoMethodAccessibleBAccessPrivatePropertyA()
	{
		return Prado::method_accessible($this, 'getPrivatePropertyA');
	}
	
	public function testFromClassB($tester, $instance)
	{
		//  calling self from child
		{ // Child calls Parent Accesses Parent
			//	Normal method_exists
			$tester->assertTrue($instance->methodAccessibleAAccessPublicPropertyA());
			$tester->assertTrue($instance->methodAccessibleAAccessProtectedPropertyA());
			$tester->assertTrue($instance->methodAccessibleAAccessPrivatePropertyA());
			
			//	Prado method_exists
			$tester->assertTrue($instance->pradoMethodAccessibleAAccessPublicPropertyA());
			$tester->assertTrue($instance->pradoMethodAccessibleAAccessProtectedPropertyA());
			$tester->assertFalse($instance->pradoMethodAccessibleAAccessPrivatePropertyA());
		}
		
		{ // Child calls Child Accesses child
			//	Normal method_exists
			$tester->assertTrue($instance->methodAccessibleBAccessPublicPropertyB());
			$tester->assertTrue($instance->methodAccessibleBAccessProtectedPropertyB());
			$tester->assertTrue($instance->methodAccessibleBAccessPrivatePropertyB());
			
			//	Prado method_exists
			$tester->assertTrue($instance->pradoMethodAccessibleBAccessPublicPropertyB());
			$tester->assertTrue($instance->pradoMethodAccessibleBAccessProtectedPropertyB());
			$tester->assertTrue($instance->pradoMethodAccessibleBAccessPrivatePropertyB());
		}
		
		
		{ // Child calls Parent Accesses Child
			//	Normal method_exists
			$tester->assertTrue($instance->methodAccessibleAAccessPublicPropertyB());
			$tester->assertTrue($instance->methodAccessibleAAccessProtectedPropertyB());
			$tester->assertTrue($instance->methodAccessibleAAccessPrivatePropertyB());
			
			//	Prado method_exists
			$tester->assertTrue($instance->pradoMethodAccessibleAAccessPublicPropertyB());
			$tester->assertTrue($instance->pradoMethodAccessibleAAccessProtectedPropertyB());
			$tester->assertTrue($instance->pradoMethodAccessibleAAccessPrivatePropertyB());
		}
		
		
		{ // Child calls Child Accesses Parent
			//	Normal method_exists
			$tester->assertTrue($instance->methodAccessibleBAccessPublicPropertyA());
			$tester->assertTrue($instance->methodAccessibleBAccessProtectedPropertyA());
			$tester->assertTrue($instance->methodAccessibleBAccessPrivatePropertyA());
			
			//	Prado method_exists
			$tester->assertTrue($instance->pradoMethodAccessibleBAccessPublicPropertyA());
			$tester->assertTrue($instance->pradoMethodAccessibleBAccessProtectedPropertyA());
			$tester->assertFalse($instance->pradoMethodAccessibleBAccessPrivatePropertyA());
		}
	}
}

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
	
	public function testMethod_Exists()
	{
		$instance = new MethodAccessibleTestClassB();
		
		// calling instance from external
		{ //Parent Accesses Parent
			//	Normal method_exists
			$this->assertTrue($instance->methodAccessibleAAccessPublicPropertyA());
			$this->assertTrue($instance->methodAccessibleAAccessProtectedPropertyA());
			$this->assertTrue($instance->methodAccessibleAAccessPrivatePropertyA());
			
			//	Prado method_exists
			$this->assertTrue($instance->pradoMethodAccessibleAAccessPublicPropertyA());
			$this->assertFalse($instance->pradoMethodAccessibleAAccessProtectedPropertyA());
			$this->assertFalse($instance->pradoMethodAccessibleAAccessPrivatePropertyA());
		}
		
		{ // Child Accesses child
			//	Normal method_exists
			$this->assertTrue($instance->methodAccessibleBAccessPublicPropertyB());
			$this->assertTrue($instance->methodAccessibleBAccessProtectedPropertyB());
			$this->assertTrue($instance->methodAccessibleBAccessPrivatePropertyB());
			
			//	Prado method_exists
			$this->assertTrue($instance->pradoMethodAccessibleBAccessPublicPropertyB());
			$this->assertFalse($instance->pradoMethodAccessibleBAccessProtectedPropertyB());
			$this->assertFalse($instance->pradoMethodAccessibleBAccessPrivatePropertyB());
		}
		
		
		{ //Parent Accesses Child
			//	Normal method_exists
			$this->assertTrue($instance->methodAccessibleAAccessPublicPropertyB());
			$this->assertTrue($instance->methodAccessibleAAccessProtectedPropertyB());
			$this->assertTrue($instance->methodAccessibleAAccessPrivatePropertyB());
			
			//	Prado method_exists
			$this->assertTrue($instance->pradoMethodAccessibleAAccessPublicPropertyB());
			$this->assertFalse($instance->pradoMethodAccessibleAAccessProtectedPropertyB());
			$this->assertFalse($instance->pradoMethodAccessibleAAccessPrivatePropertyB());
		}
		
		
		{ //Child Accesses Parent
			//	Normal method_exists
			$this->assertTrue($instance->methodAccessibleBAccessPublicPropertyA());
			$this->assertTrue($instance->methodAccessibleBAccessProtectedPropertyA());
			$this->assertTrue($instance->methodAccessibleBAccessPrivatePropertyA());
			
			//	Prado method_exists
			$this->assertTrue($instance->pradoMethodAccessibleBAccessPublicPropertyA());
			$this->assertFalse($instance->pradoMethodAccessibleBAccessProtectedPropertyA());
			$this->assertFalse($instance->pradoMethodAccessibleBAccessPrivatePropertyA());
		}
		
		$instance->testFromClassA($this, $instance);
		$instance->testFromClassB($this, $instance);
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
