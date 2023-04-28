<?php

use Prado\Prado;

class MethodExistsTestClassA
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
	public function methodExistsAAccessPublicPropertyA()
	{
		return method_exists($this, 'getPublicPropertyA');
	}
	public function methodExistsAAccessProtectedPropertyA()
	{
		return method_exists($this, 'getProtectedPropertyA');
	}
	public function methodExistsAAccessPrivatePropertyA()
	{
		return method_exists($this, 'getPrivatePropertyA');
	}
	public function pradoMethodExistsAAccessPublicPropertyA()
	{
		return Prado::method_exists($this, 'getPublicPropertyA');
	}
	public function pradoMethodExistsAAccessProtectedPropertyA()
	{
		return Prado::method_exists($this, 'getProtectedPropertyA');
	}
	public function pradoMethodExistsAAccessPrivatePropertyA()
	{
		return Prado::method_exists($this, 'getPrivatePropertyA');
	}
	
	//Access Child
	public function methodExistsAAccessPublicPropertyB()
	{
		return method_exists($this, 'getPublicPropertyB');
	}
	public function methodExistsAAccessProtectedPropertyB()
	{
		return method_exists($this, 'getProtectedPropertyB');
	}
	public function methodExistsAAccessPrivatePropertyB()
	{
		return method_exists($this, 'getPrivatePropertyB');
	}
	public function pradoMethodExistsAAccessPublicPropertyB()
	{
		return Prado::method_exists($this, 'getPublicPropertyB');
	}
	public function pradoMethodExistsAAccessProtectedPropertyB()
	{
		return Prado::method_exists($this, 'getProtectedPropertyB');
	}
	public function pradoMethodExistsAAccessPrivatePropertyB()
	{
		return Prado::method_exists($this, 'getPrivatePropertyB');
	}
	
	public function testFromClassA($tester, $instance)
	{
		//  calling self from parent
		{ // Parent calls Parent Accesses Parent
			//	Normal method_exists
			$tester->assertTrue($instance->methodExistsAAccessPublicPropertyA());
			$tester->assertTrue($instance->methodExistsAAccessProtectedPropertyA());
			$tester->assertTrue($instance->methodExistsAAccessPrivatePropertyA());
			
			//	Prado method_exists
			$tester->assertTrue($instance->pradoMethodExistsAAccessPublicPropertyA());
			$tester->assertTrue($instance->pradoMethodExistsAAccessProtectedPropertyA());
			$tester->assertTrue($instance->pradoMethodExistsAAccessPrivatePropertyA());
		}
		
		{ // Parent calls Child Accesses child
			//	Normal method_exists
			$tester->assertTrue($instance->methodExistsBAccessPublicPropertyB());
			$tester->assertTrue($instance->methodExistsBAccessProtectedPropertyB());
			$tester->assertTrue($instance->methodExistsBAccessPrivatePropertyB());
			
			//	Prado method_exists
			$tester->assertTrue($instance->pradoMethodExistsBAccessPublicPropertyB());
			$tester->assertTrue($instance->pradoMethodExistsBAccessProtectedPropertyB());
			$tester->assertFalse($instance->pradoMethodExistsBAccessPrivatePropertyB(), "Parent cannot access child private method.");
		}
		
		
		{ // Parent calls Parent Accesses Child
			//	Normal method_exists
			$tester->assertTrue($instance->methodExistsAAccessPublicPropertyB());
			$tester->assertTrue($instance->methodExistsAAccessProtectedPropertyB());
			$tester->assertTrue($instance->methodExistsAAccessPrivatePropertyB());
			
			//	Prado method_exists
			$tester->assertTrue($instance->pradoMethodExistsAAccessPublicPropertyB());
			$tester->assertTrue($instance->pradoMethodExistsAAccessProtectedPropertyB());
			$tester->assertFalse($instance->pradoMethodExistsAAccessPrivatePropertyB());
		}
		
		
		{ // Parent calls Child Accesses Parent
			//	Normal method_exists
			$tester->assertTrue($instance->methodExistsBAccessPublicPropertyA());
			$tester->assertTrue($instance->methodExistsBAccessProtectedPropertyA());
			$tester->assertTrue($instance->methodExistsBAccessPrivatePropertyA());
			
			//	Prado method_exists
			$tester->assertTrue($instance->pradoMethodExistsBAccessPublicPropertyA());
			$tester->assertTrue($instance->pradoMethodExistsBAccessProtectedPropertyA());
			$tester->assertTrue($instance->pradoMethodExistsBAccessPrivatePropertyA());
		}
	}
}

class MethodExistsTestClassB extends MethodExistsTestClassA
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
	public function methodExistsBAccessPublicPropertyB()
	{
		return method_exists($this, 'getPublicPropertyB');
	}
	public function methodExistsBAccessProtectedPropertyB()
	{
		return method_exists($this, 'getProtectedPropertyB');
	}
	public function methodExistsBAccessPrivatePropertyB()
	{
		return method_exists($this, 'getPrivatePropertyB');
	}
	public function pradoMethodExistsBAccessPublicPropertyB()
	{
		return Prado::method_exists($this, 'getPublicPropertyB');
	}
	public function pradoMethodExistsBAccessProtectedPropertyB()
	{
		return Prado::method_exists($this, 'getProtectedPropertyB');
	}
	public function pradoMethodExistsBAccessPrivatePropertyB()
	{
		return Prado::method_exists($this, 'getPrivatePropertyB');
	}
	
	// Access Parent
	public function methodExistsBAccessPublicPropertyA()
	{
		return method_exists($this, 'getPublicPropertyA');
	}
	public function methodExistsBAccessProtectedPropertyA()
	{
		return method_exists($this, 'getProtectedPropertyA');
	}
	public function methodExistsBAccessPrivatePropertyA()
	{
		return method_exists($this, 'getPrivatePropertyA');
	}
	public function pradoMethodExistsBAccessPublicPropertyA()
	{
		return Prado::method_exists($this, 'getPublicPropertyA');
	}
	public function pradoMethodExistsBAccessProtectedPropertyA()
	{
		return Prado::method_exists($this, 'getProtectedPropertyA');
	}
	public function pradoMethodExistsBAccessPrivatePropertyA()
	{
		return Prado::method_exists($this, 'getPrivatePropertyA');
	}
	
	public function testFromClassB($tester, $instance)
	{
		//  calling self from child
		{ // Child calls Parent Accesses Parent
			//	Normal method_exists
			$tester->assertTrue($instance->methodExistsAAccessPublicPropertyA());
			$tester->assertTrue($instance->methodExistsAAccessProtectedPropertyA());
			$tester->assertTrue($instance->methodExistsAAccessPrivatePropertyA());
			
			//	Prado method_exists
			$tester->assertTrue($instance->pradoMethodExistsAAccessPublicPropertyA());
			$tester->assertTrue($instance->pradoMethodExistsAAccessProtectedPropertyA());
			$tester->assertFalse($instance->pradoMethodExistsAAccessPrivatePropertyA());
		}
		
		{ // Child calls Child Accesses child
			//	Normal method_exists
			$tester->assertTrue($instance->methodExistsBAccessPublicPropertyB());
			$tester->assertTrue($instance->methodExistsBAccessProtectedPropertyB());
			$tester->assertTrue($instance->methodExistsBAccessPrivatePropertyB());
			
			//	Prado method_exists
			$tester->assertTrue($instance->pradoMethodExistsBAccessPublicPropertyB());
			$tester->assertTrue($instance->pradoMethodExistsBAccessProtectedPropertyB());
			$tester->assertTrue($instance->pradoMethodExistsBAccessPrivatePropertyB());
		}
		
		
		{ // Child calls Parent Accesses Child
			//	Normal method_exists
			$tester->assertTrue($instance->methodExistsAAccessPublicPropertyB());
			$tester->assertTrue($instance->methodExistsAAccessProtectedPropertyB());
			$tester->assertTrue($instance->methodExistsAAccessPrivatePropertyB());
			
			//	Prado method_exists
			$tester->assertTrue($instance->pradoMethodExistsAAccessPublicPropertyB());
			$tester->assertTrue($instance->pradoMethodExistsAAccessProtectedPropertyB());
			$tester->assertTrue($instance->pradoMethodExistsAAccessPrivatePropertyB());
		}
		
		
		{ // Child calls Child Accesses Parent
			//	Normal method_exists
			$tester->assertTrue($instance->methodExistsBAccessPublicPropertyA());
			$tester->assertTrue($instance->methodExistsBAccessProtectedPropertyA());
			$tester->assertTrue($instance->methodExistsBAccessPrivatePropertyA());
			
			//	Prado method_exists
			$tester->assertTrue($instance->pradoMethodExistsBAccessPublicPropertyA());
			$tester->assertTrue($instance->pradoMethodExistsBAccessProtectedPropertyA());
			$tester->assertFalse($instance->pradoMethodExistsBAccessPrivatePropertyA());
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
		$instance = new MethodExistsTestClassB();
		
		// calling instance from external
		{ //Parent Accesses Parent
			//	Normal method_exists
			$this->assertTrue($instance->methodExistsAAccessPublicPropertyA());
			$this->assertTrue($instance->methodExistsAAccessProtectedPropertyA());
			$this->assertTrue($instance->methodExistsAAccessPrivatePropertyA());
			
			//	Prado method_exists
			$this->assertTrue($instance->pradoMethodExistsAAccessPublicPropertyA());
			$this->assertFalse($instance->pradoMethodExistsAAccessProtectedPropertyA());
			$this->assertFalse($instance->pradoMethodExistsAAccessPrivatePropertyA());
		}
		
		{ // Child Accesses child
			//	Normal method_exists
			$this->assertTrue($instance->methodExistsBAccessPublicPropertyB());
			$this->assertTrue($instance->methodExistsBAccessProtectedPropertyB());
			$this->assertTrue($instance->methodExistsBAccessPrivatePropertyB());
			
			//	Prado method_exists
			$this->assertTrue($instance->pradoMethodExistsBAccessPublicPropertyB());
			$this->assertFalse($instance->pradoMethodExistsBAccessProtectedPropertyB());
			$this->assertFalse($instance->pradoMethodExistsBAccessPrivatePropertyB());
		}
		
		
		{ //Parent Accesses Child
			//	Normal method_exists
			$this->assertTrue($instance->methodExistsAAccessPublicPropertyB());
			$this->assertTrue($instance->methodExistsAAccessProtectedPropertyB());
			$this->assertTrue($instance->methodExistsAAccessPrivatePropertyB());
			
			//	Prado method_exists
			$this->assertTrue($instance->pradoMethodExistsAAccessPublicPropertyB());
			$this->assertFalse($instance->pradoMethodExistsAAccessProtectedPropertyB());
			$this->assertFalse($instance->pradoMethodExistsAAccessPrivatePropertyB());
		}
		
		
		{ //Child Accesses Parent
			//	Normal method_exists
			$this->assertTrue($instance->methodExistsBAccessPublicPropertyA());
			$this->assertTrue($instance->methodExistsBAccessProtectedPropertyA());
			$this->assertTrue($instance->methodExistsBAccessPrivatePropertyA());
			
			//	Prado method_exists
			$this->assertTrue($instance->pradoMethodExistsBAccessPublicPropertyA());
			$this->assertFalse($instance->pradoMethodExistsBAccessProtectedPropertyA());
			$this->assertFalse($instance->pradoMethodExistsBAccessPrivatePropertyA());
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
