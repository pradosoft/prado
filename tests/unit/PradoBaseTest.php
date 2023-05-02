<?php

use Prado\Prado;

class MethodVisibleTestClassA
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
	public function methodVisibleAAccessPublicPropertyA()
	{
		return method_exists($this, 'getPublicPropertyA');
	}
	public function methodVisibleAAccessProtectedPropertyA()
	{
		return method_exists($this, 'getProtectedPropertyA');
	}
	public function methodVisibleAAccessPrivatePropertyA()
	{
		return method_exists($this, 'getPrivatePropertyA');
	}
	public function pradoMethodVisibleAAccessPublicPropertyA()
	{
		return Prado::method_visible($this, 'getPublicPropertyA');
	}
	public function pradoMethodVisibleAAccessProtectedPropertyA()
	{
		return Prado::method_visible($this, 'getProtectedPropertyA');
	}
	public function pradoMethodVisibleAAccessPrivatePropertyA()
	{
		return Prado::method_visible($this, 'getPrivatePropertyA');
	}
	
	//Access Child
	public function methodVisibleAAccessPublicPropertyB()
	{
		return method_exists($this, 'getPublicPropertyB');
	}
	public function methodVisibleAAccessProtectedPropertyB()
	{
		return method_exists($this, 'getProtectedPropertyB');
	}
	public function methodVisibleAAccessPrivatePropertyB()
	{
		return method_exists($this, 'getPrivatePropertyB');
	}
	public function pradoMethodVisibleAAccessPublicPropertyB()
	{
		return Prado::method_visible($this, 'getPublicPropertyB');
	}
	public function pradoMethodVisibleAAccessProtectedPropertyB()
	{
		return Prado::method_visible($this, 'getProtectedPropertyB');
	}
	public function pradoMethodVisibleAAccessPrivatePropertyB()
	{
		return Prado::method_visible($this, 'getPrivatePropertyB');
	}
	
	
	public function isCallingSelfInA()
	{
		return Prado::isCallingSelf();
	}
	public function isCallingSelfClassInA()
	{
		return Prado::isCallingSelfClass();
	}
	
	public function testMethodVisibleFromClassA($tester, $instance)
	{
		//  calling self from parent
		{ // Parent calls Parent Accesses Parent
			//	Normal method_exists
			$tester->assertTrue($instance->methodVisibleAAccessPublicPropertyA());
			$tester->assertTrue($instance->methodVisibleAAccessProtectedPropertyA());
			$tester->assertTrue($instance->methodVisibleAAccessPrivatePropertyA());
			
			//	Prado method_exists
			$tester->assertTrue($instance->pradoMethodVisibleAAccessPublicPropertyA());
			$tester->assertTrue($instance->pradoMethodVisibleAAccessProtectedPropertyA());
			$tester->assertTrue($instance->pradoMethodVisibleAAccessPrivatePropertyA());
		}
		
		{ // Parent calls Child Accesses child
			//	Normal method_exists
			$tester->assertTrue($instance->methodVisibleBAccessPublicPropertyB());
			$tester->assertTrue($instance->methodVisibleBAccessProtectedPropertyB());
			$tester->assertTrue($instance->methodVisibleBAccessPrivatePropertyB());
			
			//	Prado method_exists
			$tester->assertTrue($instance->pradoMethodVisibleBAccessPublicPropertyB());
			$tester->assertTrue($instance->pradoMethodVisibleBAccessProtectedPropertyB());
			$tester->assertFalse($instance->pradoMethodVisibleBAccessPrivatePropertyB(), "Parent cannot access child private method.");
		}
		
		
		{ // Parent calls Parent Accesses Child
			//	Normal method_exists
			$tester->assertTrue($instance->methodVisibleAAccessPublicPropertyB());
			$tester->assertTrue($instance->methodVisibleAAccessProtectedPropertyB());
			$tester->assertTrue($instance->methodVisibleAAccessPrivatePropertyB());
			
			//	Prado method_exists
			$tester->assertTrue($instance->pradoMethodVisibleAAccessPublicPropertyB());
			$tester->assertTrue($instance->pradoMethodVisibleAAccessProtectedPropertyB());
			$tester->assertFalse($instance->pradoMethodVisibleAAccessPrivatePropertyB());
		}
		
		
		{ // Parent calls Child Accesses Parent
			//	Normal method_exists
			$tester->assertTrue($instance->methodVisibleBAccessPublicPropertyA());
			$tester->assertTrue($instance->methodVisibleBAccessProtectedPropertyA());
			$tester->assertTrue($instance->methodVisibleBAccessPrivatePropertyA());
			
			//	Prado method_exists
			$tester->assertTrue($instance->pradoMethodVisibleBAccessPublicPropertyA());
			$tester->assertTrue($instance->pradoMethodVisibleBAccessProtectedPropertyA());
			$tester->assertTrue($instance->pradoMethodVisibleBAccessPrivatePropertyA());
		}
	}
	
	public function testIsCallingSelfFromClassA($tester, $instance)
	{
		$tester->assertTrue($instance->isCallingSelfInA());
		$tester->assertTrue($instance->isCallingSelfInB());
	}
	
	public function testIsCallingSelfClassFromClassA($tester, $instance)
	{
		$tester->assertTrue($instance->isCallingSelfClassInA());
		$tester->assertFalse($instance->isCallingSelfClassInB());
	}
}

class MethodVisibleTestClassB extends MethodVisibleTestClassA
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
	public function methodVisibleBAccessPublicPropertyB()
	{
		return method_exists($this, 'getPublicPropertyB');
	}
	public function methodVisibleBAccessProtectedPropertyB()
	{
		return method_exists($this, 'getProtectedPropertyB');
	}
	public function methodVisibleBAccessPrivatePropertyB()
	{
		return method_exists($this, 'getPrivatePropertyB');
	}
	public function pradoMethodVisibleBAccessPublicPropertyB()
	{
		return Prado::method_visible($this, 'getPublicPropertyB');
	}
	public function pradoMethodVisibleBAccessProtectedPropertyB()
	{
		return Prado::method_visible($this, 'getProtectedPropertyB');
	}
	public function pradoMethodVisibleBAccessPrivatePropertyB()
	{
		return Prado::method_visible($this, 'getPrivatePropertyB');
	}
	
	// Access Parent
	public function methodVisibleBAccessPublicPropertyA()
	{
		return method_exists($this, 'getPublicPropertyA');
	}
	public function methodVisibleBAccessProtectedPropertyA()
	{
		return method_exists($this, 'getProtectedPropertyA');
	}
	public function methodVisibleBAccessPrivatePropertyA()
	{
		return method_exists($this, 'getPrivatePropertyA');
	}
	public function pradoMethodVisibleBAccessPublicPropertyA()
	{
		return Prado::method_visible($this, 'getPublicPropertyA');
	}
	public function pradoMethodVisibleBAccessProtectedPropertyA()
	{
		return Prado::method_visible($this, 'getProtectedPropertyA');
	}
	public function pradoMethodVisibleBAccessPrivatePropertyA()
	{
		return Prado::method_visible($this, 'getPrivatePropertyA');
	}
	
	
	public function isCallingSelfInB()
	{
		return Prado::isCallingSelf();
	}
	public function isCallingSelfClassInB()
	{
		return Prado::isCallingSelfClass();
	}
	
	public function testMethodVisibleFromClassB($tester, $instance)
	{
		//  calling self from child
		{ // Child calls Parent Accesses Parent
			//	Normal method_exists
			$tester->assertTrue($instance->methodVisibleAAccessPublicPropertyA());
			$tester->assertTrue($instance->methodVisibleAAccessProtectedPropertyA());
			$tester->assertTrue($instance->methodVisibleAAccessPrivatePropertyA());
			
			//	Prado method_exists
			$tester->assertTrue($instance->pradoMethodVisibleAAccessPublicPropertyA());
			$tester->assertTrue($instance->pradoMethodVisibleAAccessProtectedPropertyA());
			$tester->assertFalse($instance->pradoMethodVisibleAAccessPrivatePropertyA());
		}
		
		{ // Child calls Child Accesses child
			//	Normal method_exists
			$tester->assertTrue($instance->methodVisibleBAccessPublicPropertyB());
			$tester->assertTrue($instance->methodVisibleBAccessProtectedPropertyB());
			$tester->assertTrue($instance->methodVisibleBAccessPrivatePropertyB());
			
			//	Prado method_exists
			$tester->assertTrue($instance->pradoMethodVisibleBAccessPublicPropertyB());
			$tester->assertTrue($instance->pradoMethodVisibleBAccessProtectedPropertyB());
			$tester->assertTrue($instance->pradoMethodVisibleBAccessPrivatePropertyB());
		}
		
		
		{ // Child calls Parent Accesses Child
			//	Normal method_exists
			$tester->assertTrue($instance->methodVisibleAAccessPublicPropertyB());
			$tester->assertTrue($instance->methodVisibleAAccessProtectedPropertyB());
			$tester->assertTrue($instance->methodVisibleAAccessPrivatePropertyB());
			
			//	Prado method_exists
			$tester->assertTrue($instance->pradoMethodVisibleAAccessPublicPropertyB());
			$tester->assertTrue($instance->pradoMethodVisibleAAccessProtectedPropertyB());
			$tester->assertTrue($instance->pradoMethodVisibleAAccessPrivatePropertyB());
		}
		
		
		{ // Child calls Child Accesses Parent
			//	Normal method_exists
			$tester->assertTrue($instance->methodVisibleBAccessPublicPropertyA());
			$tester->assertTrue($instance->methodVisibleBAccessProtectedPropertyA());
			$tester->assertTrue($instance->methodVisibleBAccessPrivatePropertyA());
			
			//	Prado method_exists
			$tester->assertTrue($instance->pradoMethodVisibleBAccessPublicPropertyA());
			$tester->assertTrue($instance->pradoMethodVisibleBAccessProtectedPropertyA());
			$tester->assertFalse($instance->pradoMethodVisibleBAccessPrivatePropertyA());
		}
	}
	
	public function testIsCallingSelfFromClassB($tester, $instance)
	{
		$tester->assertTrue($instance->isCallingSelfInA());
		$tester->assertTrue($instance->isCallingSelfInB());
	}
	
	public function testIsCallingSelfClassFromClassB($tester, $instance)
	{
		$tester->assertFalse($instance->isCallingSelfClassInA());
		$tester->assertTrue($instance->isCallingSelfClassInB());
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
	
	public function testMethod_Visible()
	{
		$instance = new MethodVisibleTestClassB();
		
		// calling instance from external
		{ //Parent Accesses Parent
			//	Normal method_exists
			$this->assertTrue($instance->methodVisibleAAccessPublicPropertyA());
			$this->assertTrue($instance->methodVisibleAAccessProtectedPropertyA());
			$this->assertTrue($instance->methodVisibleAAccessPrivatePropertyA());
			
			//	Prado method_exists
			$this->assertTrue($instance->pradoMethodVisibleAAccessPublicPropertyA());
			$this->assertFalse($instance->pradoMethodVisibleAAccessProtectedPropertyA());
			$this->assertFalse($instance->pradoMethodVisibleAAccessPrivatePropertyA());
		}
		
		{ // Child Accesses child
			//	Normal method_exists
			$this->assertTrue($instance->methodVisibleBAccessPublicPropertyB());
			$this->assertTrue($instance->methodVisibleBAccessProtectedPropertyB());
			$this->assertTrue($instance->methodVisibleBAccessPrivatePropertyB());
			
			//	Prado method_exists
			$this->assertTrue($instance->pradoMethodVisibleBAccessPublicPropertyB());
			$this->assertFalse($instance->pradoMethodVisibleBAccessProtectedPropertyB());
			$this->assertFalse($instance->pradoMethodVisibleBAccessPrivatePropertyB());
		}
		
		
		{ //Parent Accesses Child
			//	Normal method_exists
			$this->assertTrue($instance->methodVisibleAAccessPublicPropertyB());
			$this->assertTrue($instance->methodVisibleAAccessProtectedPropertyB());
			$this->assertTrue($instance->methodVisibleAAccessPrivatePropertyB());
			
			//	Prado method_exists
			$this->assertTrue($instance->pradoMethodVisibleAAccessPublicPropertyB());
			$this->assertFalse($instance->pradoMethodVisibleAAccessProtectedPropertyB());
			$this->assertFalse($instance->pradoMethodVisibleAAccessPrivatePropertyB());
		}
		
		
		{ //Child Accesses Parent
			//	Normal method_exists
			$this->assertTrue($instance->methodVisibleBAccessPublicPropertyA());
			$this->assertTrue($instance->methodVisibleBAccessProtectedPropertyA());
			$this->assertTrue($instance->methodVisibleBAccessPrivatePropertyA());
			
			//	Prado method_exists
			$this->assertTrue($instance->pradoMethodVisibleBAccessPublicPropertyA());
			$this->assertFalse($instance->pradoMethodVisibleBAccessProtectedPropertyA());
			$this->assertFalse($instance->pradoMethodVisibleBAccessPrivatePropertyA());
		}
		
		$instance->testMethodVisibleFromClassA($this, $instance);
		$instance->testMethodVisibleFromClassB($this, $instance);
	}
	
	public function testIsCallingSelf()
	{
		$instance = new MethodVisibleTestClassB();
		
		$this->assertFalse($instance->isCallingSelfInA());
		$this->assertFalse($instance->isCallingSelfInB());
		
		$instance->testIsCallingSelfFromClassA($this, $instance);
		$instance->testIsCallingSelfFromClassB($this, $instance);
	}
	
	public function testIsCallingSelfClass()
	{
		$instance = new MethodVisibleTestClassB();
		
		$this->assertFalse($instance->isCallingSelfClassInA());
		$this->assertFalse($instance->isCallingSelfClassInB());
		
		$instance->testIsCallingSelfClassFromClassA($this, $instance);
		$instance->testIsCallingSelfClassFromClassB($this, $instance);
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
