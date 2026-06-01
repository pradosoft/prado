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

	// -------------------------------------------------------------------------
	// Prado::using() — existing load behaviour
	// -------------------------------------------------------------------------

	public function testUsingNamespace()
	{
		Prado::using(self::CLASS_FQN);
		$this->assertTrue(class_exists(self::CLASS_FQN, false));
	}

	public function testUsingInterface()
	{
		Prado::using(self::INTERFACE_FQN);
		$this->assertTrue(interface_exists(self::INTERFACE_SHORT_NAME, false));
	}

	// -------------------------------------------------------------------------
	// Prado::using() — return-value contract (string|true|false)
	// -------------------------------------------------------------------------

	/**
	 * using() returns the PHP FQN string when it resolves a class that is
	 * already loaded (bootstrap guarantees TApplication is present).
	 */
	public function testUsing_withAlreadyLoadedClassFqn_returnsString(): void
	{
		$result = Prado::using(\Prado\TApplication::class);
		$this->assertIsString($result);
		$this->assertSame(\Prado\TApplication::class, $result);
	}

	/**
	 * using() returns the PHP FQN string when it resolves a loadable interface.
	 */
	public function testUsing_withLoadableInterfaceFqn_returnsString(): void
	{
		$result = Prado::using(self::INTERFACE_FQN);
		$this->assertIsString($result);
		$this->assertSame(self::INTERFACE_FQN, $result);
	}

	/**
	 * using() returns the registered PHP namespace string with a trailing '\' for a
	 * valid directory namespace (e.g. 'Prado\Web\UI\*' → 'Prado\Web\UI\').
	 */
	public function testUsing_withDirectoryNamespace_returnsNamespaceStringWithTrailingBackslash(): void
	{
		$result = Prado::using('Prado\\Web\\UI\\*');
		$this->assertIsString($result);
		$this->assertSame('Prado\\Web\\UI\\', $result);
	}

	/**
	 * using() accepts a Prado3 System.* directory notation and returns the
	 * equivalent PHP namespace string with a trailing '\'.
	 * 'System.Web.UI.*' → prado3ToPhp → 'Prado\Web\UI\*' → registers → 'Prado\Web\UI\'.
	 */
	public function testUsing_withPrado3SystemDirectoryNotation_returnsPhpNamespaceWithTrailingBackslash(): void
	{
		$result = Prado::using('System.Web.UI.*');
		$this->assertIsString($result);
		$this->assertSame('Prado\\Web\\UI\\', $result);
	}

	/**
	 * using() accepts a Prado3 Prado.* directory notation and returns the
	 * equivalent PHP namespace string with a trailing '\'.
	 * 'Prado.Web.UI.*' → prado3ToPhp → 'Prado\Web\UI\*' → registers → 'Prado\Web\UI\'.
	 */
	public function testUsing_withPrado3PradoDirectoryNotation_returnsPhpNamespaceWithTrailingBackslash(): void
	{
		$result = Prado::using('Prado.Web.UI.*');
		$this->assertIsString($result);
		$this->assertSame('Prado\\Web\\UI\\', $result);
	}

	/**
	 * using() returns a namespace string with trailing '\' for a namespace already
	 * registered in $_usings (e.g. the pre-registered 'Prado' root).
	 */
	public function testUsing_withPreRegisteredNamespace_returnsStringWithTrailingBackslash(): void
	{
		$result = Prado::using('Prado');
		$this->assertIsString($result);
		$this->assertSame('Prado\\', $result);
	}

	/**
	 * using() returns null when the namespace cannot be resolved at all.
	 */
	public function testUsing_withUnresolvableNamespace_returnsNull(): void
	{
		$result = Prado::using('Prado\\NonExistent\\TFakeClassXYZ99999');
		$this->assertNull($result);
	}

	// -------------------------------------------------------------------------
	// Prado::usingClass() — returns string (resolved PHP FQN)
	// -------------------------------------------------------------------------

	/**
	 * PHP FQN for an already-loaded class → same FQN returned.
	 * TApplication is loaded at bootstrap, exercising the fast path
	 * (class_exists() true before any file loading).
	 */
	public function testUsingClass_withAlreadyLoadedClassFqn_returnsString(): void
	{
		$result = Prado::usingClass(\Prado\TApplication::class);
		$this->assertIsString($result);
		$this->assertSame(\Prado\TApplication::class, $result);
	}

	/**
	 * PHP FQN for a loadable interface → same FQN returned.
	 */
	public function testUsingClass_withPhpFqnInterface_returnsString(): void
	{
		$result = Prado::usingClass(self::INTERFACE_FQN);
		$this->assertIsString($result);
		$this->assertSame(self::INTERFACE_FQN, $result);
	}

	/**
	 * PHP FQN for a loadable trait → same FQN returned.
	 */
	public function testUsingClass_withPhpFqnTrait_returnsString(): void
	{
		$result = Prado::usingClass(\Prado\Util\Traits\TInitializedTrait::class);
		$this->assertIsString($result);
		$this->assertSame(\Prado\Util\Traits\TInitializedTrait::class, $result);
	}

	/**
	 * Short class name in classMap → a usable class name string returned.
	 * 'TApplication' maps to 'Prado\TApplication' via classMap, but when the global
	 * alias has already been registered (e.g. by the bootstrap), using() returns the
	 * short alias name from its early-return path instead of the PHP FQN. Either
	 * form is a valid, usable PHP class name for the same class.
	 */
	public function testUsingClass_withShortClassName_returnsPhpFqn(): void
	{
		$result = Prado::usingClass('TApplication');
		$this->assertIsString($result);
		$this->assertTrue(is_a($result, \Prado\TApplication::class, true), 'Result must resolve to TApplication');
	}

	/**
	 * Short interface name in classMap → resolved PHP FQN returned.
	 * 'ICache' maps to 'Prado\Caching\ICache' via classMap.
	 */
	public function testUsingClass_withShortInterfaceName_returnsPhpFqn(): void
	{
		$result = Prado::usingClass('ICache');
		$this->assertIsString($result);
		$this->assertSame(\Prado\Caching\ICache::class, $result);
	}

	/**
	 * Short trait name in classMap → a usable trait name string returned.
	 * 'TInitializedTrait' maps to 'Prado\Util\Traits\TInitializedTrait' via classMap,
	 * but when the global alias has already been registered, using() returns the short
	 * alias name from its early-return path. Either form resolves to the same trait.
	 */
	public function testUsingClass_withShortTraitName_returnsPhpFqn(): void
	{
		$result = Prado::usingClass('TInitializedTrait');
		$this->assertIsString($result);
		$this->assertTrue(trait_exists($result, false), 'Result must resolve to an existing trait');
	}

	/**
	 * Prado3 System.* dot-notation for a class → PHP FQN returned.
	 * 'System.TApplication' → prado3ToPhp → 'Prado\TApplication'.
	 */
	public function testUsingClass_withPrado3SystemDotNotationClass_returnsPhpFqn(): void
	{
		$result = Prado::usingClass('System.TApplication');
		$this->assertIsString($result);
		$this->assertSame(\Prado\TApplication::class, $result);
	}

	/**
	 * Prado3 Prado.* dot-notation for a class → PHP FQN returned.
	 * 'Prado.TApplication' → prado3ToPhp → 'Prado\TApplication'.
	 */
	public function testUsingClass_withPrado3PradoDotNotationClass_returnsPhpFqn(): void
	{
		$result = Prado::usingClass('Prado.TApplication');
		$this->assertIsString($result);
		$this->assertSame(\Prado\TApplication::class, $result);
	}

	/**
	 * Full Prado3 System.* path (the canonical Prado3 form) → PHP FQN returned.
	 * CLASS_PRADO_FULLNAME = 'System.Web.UI.WebControls.TButton'
	 */
	public function testUsingClass_withPrado3SystemFullPath_returnsPhpFqn(): void
	{
		$result = Prado::usingClass(self::CLASS_PRADO_FULLNAME);
		$this->assertIsString($result);
		$this->assertSame(self::CLASS_FQN, $result);
	}

	/**
	 * Full Prado3 Prado.* path → PHP FQN returned.
	 */
	public function testUsingClass_withPrado3PradoFullPath_returnsPhpFqn(): void
	{
		$result = Prado::usingClass('Prado.Web.UI.WebControls.TButton');
		$this->assertIsString($result);
		$this->assertSame(self::CLASS_FQN, $result);
	}

	/**
	 * Prado3 System.* notation for an interface → PHP FQN returned.
	 * 'System.Caching.ICache' → 'Prado\Caching\ICache'.
	 */
	public function testUsingClass_withPrado3SystemDotNotationInterface_returnsPhpFqn(): void
	{
		$result = Prado::usingClass('System.Caching.ICache');
		$this->assertIsString($result);
		$this->assertSame(\Prado\Caching\ICache::class, $result);
	}

	/**
	 * Prado3 System.* notation for a trait → PHP FQN returned.
	 * 'System.Util.Traits.TInitializedTrait' → 'Prado\Util\Traits\TInitializedTrait'.
	 */
	public function testUsingClass_withPrado3SystemDotNotationTrait_returnsPhpFqn(): void
	{
		$result = Prado::usingClass('System.Util.Traits.TInitializedTrait');
		$this->assertIsString($result);
		$this->assertSame(\Prado\Util\Traits\TInitializedTrait::class, $result);
	}

	/**
	 * Calling usingClass() twice with the same FQN returns the same string
	 * (idempotency — the already-loaded fast-path is exercised on the second call).
	 */
	public function testUsingClass_calledTwiceWithFqn_returnsSameString(): void
	{
		$first = Prado::usingClass(\Prado\TApplication::class);
		$second = Prado::usingClass(\Prado\TApplication::class);
		$this->assertIsString($first);
		$this->assertSame($first, $second);
	}

	/**
	 * Calling usingClass() twice with the same Prado3 name returns the same string.
	 */
	public function testUsingClass_calledTwiceWithPrado3Name_returnsSameString(): void
	{
		$first = Prado::usingClass('System.TApplication');
		$second = Prado::usingClass('System.TApplication');
		$this->assertIsString($first);
		$this->assertSame($first, $second);
	}

	// -------------------------------------------------------------------------
	// Prado::usingClass() — returns false (directory namespace)
	// -------------------------------------------------------------------------

	/**
	 * A PHP directory namespace (ends with *) → strictly false, never null.
	 */
	public function testUsingClass_withPhpDirectoryNamespace_returnsFalse(): void
	{
		$result = Prado::usingClass('Prado\\Web\\UI\\*');
		$this->assertFalse($result);
	}

	/**
	 * A Prado3 System.* directory notation → false.
	 * 'System.Web.UI.*' → prado3ToPhp → 'Prado\Web\UI\*' → directory.
	 */
	public function testUsingClass_withPrado3SystemDirectoryNotation_returnsFalse(): void
	{
		$result = Prado::usingClass('System.Web.UI.*');
		$this->assertFalse($result);
	}

	/**
	 * A Prado3 Prado.* directory notation → false.
	 */
	public function testUsingClass_withPrado3PradoDirectoryNotation_returnsFalse(): void
	{
		$result = Prado::usingClass('Prado.Web.UI.*');
		$this->assertFalse($result);
	}

	/**
	 * A namespace prefix already registered in $_usings → false.
	 * 'Prado' is pre-registered at Prado class initialization time, so
	 * using() returns 'Prado\' immediately without touching the filesystem,
	 * and usingClass() maps that trailing-backslash string to false.
	 */
	public function testUsingClass_withRegisteredDirectoryPrefix_returnsFalse(): void
	{
		$result = Prado::usingClass('Prado');
		$this->assertFalse($result);
	}

	// -------------------------------------------------------------------------
	// Prado::usingClass() — returns null (namespace could not be resolved)
	// -------------------------------------------------------------------------

	/**
	 * A well-formed PHP FQN that resolves to no file → null.
	 */
	public function testUsingClass_withUnknownPhpFqn_returnsNull(): void
	{
		$result = Prado::usingClass('Prado\\NonExistent\\TFakeClassXYZ99999');
		$this->assertNull($result);
	}

	/**
	 * A Prado3 System.* name that converts to a non-existent class → null.
	 */
	public function testUsingClass_withUnknownPrado3SystemDotNotation_returnsNull(): void
	{
		$result = Prado::usingClass('System.NonExistent.TFakeClassXYZ99999');
		$this->assertNull($result);
	}

	/**
	 * A Prado3 Prado.* name that converts to a non-existent class → null.
	 */
	public function testUsingClass_withUnknownPrado3PradoDotNotation_returnsNull(): void
	{
		$result = Prado::usingClass('Prado.NonExistent.TFakeClassXYZ99999');
		$this->assertNull($result);
	}

	/**
	 * A short name not in classMap and not found in any registered directory → null.
	 */
	public function testUsingClass_withUnknownShortName_returnsNull(): void
	{
		$result = Prado::usingClass('TFakeClassThatDoesNotExistXYZ99999');
		$this->assertNull($result);
	}

	// -------------------------------------------------------------------------
	// Prado::usingClass() — type-distinctness of false vs null
	//
	// The call sites all use !is_string() to guard against both directory and
	// not-found results. These tests verify that the two non-string return
	// values are strictly distinct from each other AND both satisfy the guard.
	// -------------------------------------------------------------------------

	/**
	 * The directory result is strictly false, not null.
	 */
	public function testUsingClass_directoryResult_isStrictlyFalseNotNull(): void
	{
		$result = Prado::usingClass('Prado\\Web\\UI\\*');
		$this->assertFalse($result);
		$this->assertNotNull($result);
		$this->assertFalse(is_string($result), '!is_string() guard must catch false');
	}

	/**
	 * The not-found result is strictly null, not false.
	 */
	public function testUsingClass_notFoundResult_isStrictlyNullNotFalse(): void
	{
		$result = Prado::usingClass('Prado\\NonExistent\\TFakeClassXYZ99999');
		$this->assertNull($result);
		$this->assertNotFalse($result);
		$this->assertFalse(is_string($result), '!is_string() guard must catch null');
	}

	/**
	 * Both false and null satisfy the !is_string() guard used at all call sites,
	 * while a resolved string does not.
	 */
	public function testUsingClass_isStringGuard_distinguishesAllThreeOutcomes(): void
	{
		$resolved = Prado::usingClass(\Prado\TApplication::class);
		$directory = Prado::usingClass('Prado\\Web\\UI\\*');
		$notFound = Prado::usingClass('Prado\\NonExistent\\TFakeClassXYZ99999');

		$this->assertTrue(is_string($resolved), 'Resolved FQN must satisfy is_string()');
		$this->assertFalse(is_string($directory), 'Directory false must not satisfy is_string()');
		$this->assertFalse(is_string($notFound), 'Not-found null must not satisfy is_string()');

		// Strict distinctness between the two non-string values
		$this->assertNotSame($directory, $notFound, 'false and null must be strictly different');
	}

	// -------------------------------------------------------------------------
	// Prado::using() — Prado3 global-namespace reverse alias
	//
	// When a class file defines its class in global namespace (Prado3 style),
	// using() must create a reverse alias class_alias($shortName, $fqn) so the
	// returned FQN string is a valid, usable PHP class name.
	// -------------------------------------------------------------------------

	/**
	 * Returns the path to the Prado3-style fixture directory and ensures its
	 * path alias 'Prado3Fixture' is registered, so tests are self-contained.
	 */
	private function registerPrado3FixtureAlias(): string
	{
		$fixturePath = __DIR__ . '/Security/app/prado3stubs';
		Prado::setPathOfAlias('Prado3Fixture', $fixturePath);
		return $fixturePath;
	}

	/**
	 * using() with a path-alias dot-notation pointing to a global-namespace class
	 * must return the path-derived FQN AND make that FQN usable via class_exists.
	 * @since 4.3.3
	 */
	public function testUsing_withPrado3GlobalNamespaceClass_returnsFqnAndCreatesAlias(): void
	{
		// Register a self-contained alias pointing directly at the fixture dir.
		// The fixture file defines class GlobalNsComponent in global namespace.
		$this->registerPrado3FixtureAlias();
		$fqn = Prado::using('Prado3Fixture.GlobalNsComponent');
		$this->assertSame('Prado3Fixture\\GlobalNsComponent', $fqn);
		// The returned FQN must exist as a real (aliased) PHP class.
		$this->assertTrue(class_exists($fqn, false), 'FQN must be resolvable via class_exists(false)');
	}

	/**
	 * usingClass() with a Prado3 dot-notation that points to a global-namespace
	 * class must return the FQN, and that FQN must satisfy is_subclass_of.
	 * @since 4.3.3
	 */
	public function testUsingClass_withPrado3GlobalNamespaceClass_fqnPassesIsSubclassOf(): void
	{
		$this->registerPrado3FixtureAlias();
		$fqn = Prado::usingClass('Prado3Fixture.GlobalNsComponent');
		$this->assertIsString($fqn);
		$this->assertSame('Prado3Fixture\\GlobalNsComponent', $fqn);
		// is_subclass_of must work via the reverse alias — this is what createPage
		// and validateAttributes rely on for Prado3-style base classes.
		$this->assertTrue(
			is_subclass_of($fqn, \Prado\TComponent::class),
			'FQN alias must satisfy is_subclass_of against the real parent class'
		);
	}

	/**
	 * When using() is called a second time for the same global-namespace class
	 * (already loaded), it must still return the correct FQN and the alias must
	 * still be valid (early-return branch in using()).
	 * @since 4.3.3
	 */
	public function testUsing_withAlreadyLoadedGlobalNamespaceClass_returnsFqnFromEarlyReturn(): void
	{
		// First call loads the class; second call hits the early-return branch.
		$this->registerPrado3FixtureAlias();
		Prado::using('Prado3Fixture.GlobalNsComponent');
		$fqn = Prado::using('Prado3Fixture.GlobalNsComponent');
		$this->assertSame('Prado3Fixture\\GlobalNsComponent', $fqn);
		$this->assertTrue(class_exists($fqn, false));
		$this->assertTrue(is_subclass_of($fqn, \Prado\TComponent::class));
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

	/**
	 * Regression: {@see \Prado\Prado::method_visible()} scopes its reflection
	 * lookup to the class hierarchy.  When an instance is supplied,
	 * {@see \Prado\TComponentReflection::getReflectionMethodByType()} also
	 * walks the object's enabled behaviors and recurses through
	 * `method_visible()` for each behavior method.  Passing the class name
	 * skips that walk.
	 *
	 * Without the class-name guard, the call below returns `true` for a
	 * method that lives on a sub-behavior, which then causes
	 * `new \ReflectionMethod($behavior, $method)` to throw because the
	 * behavior class does not declare the method.  Restoring the object
	 * argument reintroduces the failure observed in
	 * `TComponentPropertyTest::testHasMethod` at the sub-behavior assertion.
	 * @since 4.4.0
	 */
	public function testMethodVisible_doesNotWalkBehaviorChain()
	{
		require_once __DIR__ . '/TComponentTestFixtures.php';

		$component = new NewComponent();
		$behavior = new BehaviorTestBehavior();
		$subBehavior = new FooFooClassBehavior();

		// Sanity: the sub-behavior class itself declares faafaaEverMore.
		$this->assertTrue(Prado::method_visible($subBehavior, 'faafaaEverMore'));
		// Sanity: the outer behavior class does not declare it.
		$this->assertFalse(Prado::method_visible($behavior, 'faafaaEverMore'));
		// Sanity: a fresh component class does not declare it.
		$this->assertFalse(Prado::method_visible($component, 'faafaaEverMore'));

		// Attach the behavior to the component and the sub-behavior to the behavior.
		$component->attachBehavior('inner', $behavior);
		$behavior->attachBehavior('SubBehavior', $subBehavior);

		// Both must remain false after attachment: method_visible reports
		// only on the class hierarchy, not on attached behaviors.
		$this->assertFalse(
			Prado::method_visible($component, 'faafaaEverMore'),
			'method_visible(component, ...) must not discover sub-behavior methods'
		);
		$this->assertFalse(
			Prado::method_visible($behavior, 'faafaaEverMore'),
			'method_visible(behavior, ...) must not discover sub-behavior methods'
		);

		// And the class-name form is identical to the object form.
		$this->assertFalse(Prado::method_visible(NewComponent::class, 'faafaaEverMore'));
		$this->assertFalse(Prado::method_visible(BehaviorTestBehavior::class, 'faafaaEverMore'));
		$this->assertTrue(Prado::method_visible(FooFooClassBehavior::class, 'faafaaEverMore'));

		// hasMethod composes one behavior layer on top of method_visible.
		// The component sees the behavior's own method; the sub-behavior
		// method is NOT visible through the component (only one layer).
		$this->assertTrue($component->hasMethod('getExcitement'));
		$this->assertFalse(
			$component->hasMethod('faafaaEverMore'),
			'hasMethod must not throw and must report false for sub-behavior methods'
		);
		// The intermediate behavior, however, exposes its own sub-behavior method.
		$this->assertTrue($behavior->hasMethod('faafaaEverMore'));
	}

	public function testCallingObject()
	{
		// Create a new object that calls Prado::callingObject()
		$object = new class {
			public function getCallingObject()
			{
				return Prado::callingObject();
			}
		};
		$this->assertEquals($this, $object->getCallingObject());
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
	
	public function testProfileBegin()
	{
		$logger = Prado::getLogger();
			
		$logger->deleteLogs();
		Prado::profileBegin('token');
		$this->assertEquals(1, $logger->getLogCount());
		$this->assertEquals(1, count($logs = $logger->getLogs()));
		$this->assertEquals('token', $logs[0][0]);
		$this->assertEquals(TLogger::PROFILE_BEGIN, $logs[0][1]);
		$this->assertEquals($this::class, $logs[0][2]);
		$this->assertNull($logs[0][5]);
		
		Prado::profileBegin('token', \Prado\TApplication::class, 'ctl1');
		$this->assertEquals(1, $logger->getLogCount());
		$this->assertEquals(1, count($logs = $logger->getLogs()));
		$this->assertEquals('token', $logs[0][0]);
		$this->assertEquals(TLogger::PROFILE_BEGIN, $logs[0][1]);
		$this->assertEquals(\Prado\TApplication::class, $logs[0][2]);
		$this->assertEquals('ctl1', $logs[0][5]);
		
		$logger->deleteProfileLogs();
	}
	
	public function testProfileEnd()
	{
		$logger = Prado::getLogger();
			
		$logger->deleteLogs();
		$this->assertNull(Prado::profileBegin('token'));
		usleep(10);
		$this->assertNotNull($profileTime = Prado::profileEnd('token'));
		
		$this->assertEquals(2, $logger->getLogCount());
		$this->assertEquals(2, count($logs = $logger->getLogs()));
		$this->assertEquals('token', $logs[0][0]);
		$this->assertEquals(TLogger::PROFILE_BEGIN, $logs[0][1]);
		$this->assertEquals($this::class, $logs[0][2]);
		$this->assertNull($logs[0][5]);
		
		$this->assertEquals('token', $logs[1][0]);
		$this->assertEquals(TLogger::PROFILE_END, $logs[1][1]);
		$this->assertEquals($this::class, $logs[1][2]);
		$this->assertNull($logs[1][5]);
		
		$this->assertNotNull($profileTime2 = Prado::profileEnd('token'));
		$this->assertGreaterThan($profileTime, $profileTime2);
		
		$this->assertEquals(0, $logger->getLogCount(false));
	}
	
	public function testTrace()
	{
		$app = Prado::getApplication();
		$mode = $app->getMode();
		$logger = Prado::getLogger();
		$logger->deleteLogs();
		
		Prado::trace('msg', 'Category', 'ctlClass');
		$app->setMode(TApplicationMode::Normal);
		Prado::trace('msg2');
		$logs = $logger->getLogs();
		$this->assertTrue(str_starts_with($logs[0][0], 'msg'));
		$this->assertEquals(TLogger::DEBUG, $logs[0][1]);
		$this->assertEquals('Category', $logs[0][2]);
		$this->assertEquals('ctlClass', $logs[0][5]);
		
		$this->assertEquals('msg2', $logs[1][0]);
		$this->assertEquals(TLogger::INFO, $logs[1][1]);
		$this->assertEquals($this::class, $logs[1][2]);
		$this->assertEquals(null, $logs[1][5]);
		$this->assertEquals(getmypid(), $logs[1][7]);
		$app->setMode($mode);
	}
	
	public function testDebug()
	{
		$app = Prado::getApplication();
		$mode = $app->getMode();
		$logger = Prado::getLogger();
		$logger->deleteLogs();
		$app->setMode(TApplicationMode::Debug);
		
		Prado::debug('msg', 'Category', 'ctlClass');
		Prado::debug('msg2');
		$app->setMode(TApplicationMode::Normal);
		Prado::debug('msg3');
		$logs = $logger->getLogs();
		$this->assertEquals(2, count($logs));
		$this->assertTrue(str_starts_with($logs[0][0], 'msg'));
		$this->assertEquals(TLogger::DEBUG, $logs[0][1]);
		$this->assertEquals('Category', $logs[0][2]);
		$this->assertEquals('ctlClass', $logs[0][5]);
		
		$this->assertTrue(str_starts_with($logs[1][0], 'msg2'));
		$this->assertEquals(TLogger::DEBUG, $logs[1][1]);
		$this->assertEquals($this::class, $logs[1][2]);
		$this->assertEquals(null, $logs[1][5]);
		$app->setMode($mode);
	}
	
	public function testInfo()
	{
		$logger = Prado::getLogger();
		$logger->deleteLogs();
		
		Prado::info('msg', 'Category', 'ctlClass');
		Prado::info('msg2');
		$logs = $logger->getLogs();
		$this->assertEquals(2, count($logs));
		$this->assertEquals('msg', $logs[0][0]);
		$this->assertEquals(TLogger::INFO, $logs[0][1]);
		$this->assertEquals('Category', $logs[0][2]);
		$this->assertEquals('ctlClass', $logs[0][5]);
		
		$this->assertEquals('msg2', $logs[1][0]);
		$this->assertEquals(TLogger::INFO, $logs[1][1]);
		$this->assertEquals($this::class, $logs[1][2]);
		$this->assertEquals(null, $logs[1][5]);
	}
	
	public function testNotice()
	{
		$logger = Prado::getLogger();
		$logger->deleteLogs();
		
		Prado::notice('msg', 'Category', 'ctlClass');
		Prado::notice('msg2');
		$logs = $logger->getLogs();
		$this->assertEquals(2, count($logs));
		$this->assertEquals('msg', $logs[0][0]);
		$this->assertEquals(TLogger::NOTICE, $logs[0][1]);
		$this->assertEquals('Category', $logs[0][2]);
		$this->assertEquals('ctlClass', $logs[0][5]);
		
		$this->assertEquals('msg2', $logs[1][0]);
		$this->assertEquals(TLogger::NOTICE, $logs[1][1]);
		$this->assertEquals($this::class, $logs[1][2]);
		$this->assertEquals(null, $logs[1][5]);
	}
	
	public function testWarning()
	{
		$logger = Prado::getLogger();
		$logger->deleteLogs();
		
		Prado::warning('msg', 'Category', 'ctlClass');
		Prado::warning('msg2');
		$logs = $logger->getLogs();
		$this->assertEquals(2, count($logs));
		$this->assertEquals('msg', $logs[0][0]);
		$this->assertEquals(TLogger::WARNING, $logs[0][1]);
		$this->assertEquals('Category', $logs[0][2]);
		$this->assertEquals('ctlClass', $logs[0][5]);
		
		$this->assertEquals('msg2', $logs[1][0]);
		$this->assertEquals(TLogger::WARNING, $logs[1][1]);
		$this->assertEquals($this::class, $logs[1][2]);
		$this->assertEquals(null, $logs[1][5]);
	}
	
	public function testError()
	{
		$logger = Prado::getLogger();
		$logger->deleteLogs();
		
		Prado::error('msg', 'Category', 'ctlClass');
		Prado::error('msg2');
		$logs = $logger->getLogs();
		$this->assertEquals(2, count($logs));
		$this->assertEquals('msg', $logs[0][0]);
		$this->assertEquals(TLogger::ERROR, $logs[0][1]);
		$this->assertEquals('Category', $logs[0][2]);
		$this->assertEquals('ctlClass', $logs[0][5]);
		
		$this->assertEquals('msg2', $logs[1][0]);
		$this->assertEquals(TLogger::ERROR, $logs[1][1]);
		$this->assertEquals($this::class, $logs[1][2]);
		$this->assertEquals(null, $logs[1][5]);
	}
	
	public function testAlert()
	{
		$logger = Prado::getLogger();
		$logger->deleteLogs();
		
		Prado::alert('msg', 'Category', 'ctlClass');
		Prado::alert('msg2');
		$logs = $logger->getLogs();
		$this->assertEquals(2, count($logs));
		$this->assertEquals('msg', $logs[0][0]);
		$this->assertEquals(TLogger::ALERT, $logs[0][1]);
		$this->assertEquals('Category', $logs[0][2]);
		$this->assertEquals('ctlClass', $logs[0][5]);
		
		$this->assertEquals('msg2', $logs[1][0]);
		$this->assertEquals(TLogger::ALERT, $logs[1][1]);
		$this->assertEquals($this::class, $logs[1][2]);
		$this->assertEquals(null, $logs[1][5]);
	}
	
	public function testFatal()
	{
		$logger = Prado::getLogger();
		$logger->deleteLogs();
		
		Prado::fatal('msg', 'Category', 'ctlClass');
		Prado::fatal('msg2');
		$logs = $logger->getLogs();
		$this->assertEquals(2, count($logs));
		$this->assertEquals('msg', $logs[0][0]);
		$this->assertEquals(TLogger::FATAL, $logs[0][1]);
		$this->assertEquals('Category', $logs[0][2]);
		$this->assertEquals('ctlClass', $logs[0][5]);
		
		$this->assertEquals('msg2', $logs[1][0]);
		$this->assertEquals(TLogger::FATAL, $logs[1][1]);
		$this->assertEquals($this::class, $logs[1][2]);
		$this->assertEquals(null, $logs[1][5]);
	}
	
	public function testLog()
	{
		$logger = Prado::getLogger();
		$logger->deleteLogs();
		
		Prado::log('msg', TLogger::WARNING, 'My Category', 'ctlClass');
		Prado::log('msg2', TLogger::DEBUG, null);
		$logs = $logger->getLogs();
		$this->assertEquals('msg', $logs[0][0]);
		$this->assertEquals(TLogger::WARNING, $logs[0][1]);
		$this->assertEquals('My Category', $logs[0][2]);
		$this->assertEquals('ctlClass', $logs[0][5]);
		
		$this->assertEquals('msg2', $logs[1][0]);
		$this->assertEquals(TLogger::DEBUG, $logs[1][1]);
		$this->assertEquals($this::class, $logs[1][2]);
		$this->assertEquals(null, $logs[1][5]);
	}
	
	public function testGetLogger()
	{
		$this->assertInstanceOf(\Prado\Util\TLogger::class, Prado::getLogger());
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

	// -------------------------------------------------------------------------
	// Prado::getVersion()
	// -------------------------------------------------------------------------

	/**
	 * getVersion() returns a semantic-version string of the form X.Y.Z.
	 */
	public function testGetVersion(): void
	{
		$version = Prado::getVersion();
		$this->assertIsString($version);
		$this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', $version);
	}

	// -------------------------------------------------------------------------
	// Prado::getFrameworkPath()
	// -------------------------------------------------------------------------

	/**
	 * getFrameworkPath() returns PRADO_DIR — a readable, existing directory.
	 */
	public function testGetFrameworkPath(): void
	{
		$path = Prado::getFrameworkPath();
		$this->assertIsString($path);
		$this->assertSame(PRADO_DIR, $path);
		$this->assertDirectoryExists($path);
	}

	// -------------------------------------------------------------------------
	// Prado::getDefaultPermissions() / getDefaultDirPermissions() / getDefaultFilePermissions()
	// -------------------------------------------------------------------------

	/**
	 * getDefaultPermissions() returns PRADO_CHMOD (0o777).
	 * @deprecated since 4.2.2
	 */
	public function testGetDefaultPermissions(): void
	{
		$this->assertSame(PRADO_CHMOD, Prado::getDefaultPermissions());
		$this->assertSame(0o777, Prado::getDefaultPermissions());
	}

	/**
	 * getDefaultDirPermissions() returns PRADO_DIR_CHMOD (0o755).
	 */
	public function testGetDefaultDirPermissions(): void
	{
		$this->assertSame(PRADO_DIR_CHMOD, Prado::getDefaultDirPermissions());
		$this->assertSame(0o755, Prado::getDefaultDirPermissions());
	}

	/**
	 * getDefaultFilePermissions() returns PRADO_FILE_CHMOD (0o644).
	 */
	public function testGetDefaultFilePermissions(): void
	{
		$this->assertSame(PRADO_FILE_CHMOD, Prado::getDefaultFilePermissions());
		$this->assertSame(0o644, Prado::getDefaultFilePermissions());
	}

	// -------------------------------------------------------------------------
	// Prado::getApplication()
	// -------------------------------------------------------------------------

	/**
	 * getApplication() returns the bootstrapped TApplication singleton.
	 */
	public function testGetApplication(): void
	{
		$app = Prado::getApplication();
		$this->assertInstanceOf(\Prado\TApplication::class, $app);
	}

	// -------------------------------------------------------------------------
	// Prado::getPathOfAlias()
	// -------------------------------------------------------------------------

	/**
	 * getPathOfAlias() with the built-in 'Prado' alias returns PRADO_DIR.
	 */
	public function testGetPathOfAlias_withPradoAlias(): void
	{
		$this->assertSame(PRADO_DIR, Prado::getPathOfAlias('Prado'));
	}

	/**
	 * getPathOfAlias() with the built-in 'Vendor' alias returns PRADO_VENDORDIR.
	 */
	public function testGetPathOfAlias_withVendorAlias(): void
	{
		$this->assertSame(PRADO_VENDORDIR, Prado::getPathOfAlias('Vendor'));
	}

	/**
	 * getPathOfAlias() with an unknown alias returns null.
	 */
	public function testGetPathOfAlias_withUnknownAlias(): void
	{
		$this->assertNull(Prado::getPathOfAlias('NonExistentAliasXYZ99999'));
	}

	// -------------------------------------------------------------------------
	// Prado::setPathOfAlias()
	// -------------------------------------------------------------------------

	/**
	 * setPathOfAlias() registers a new alias; getPathOfAlias() retrieves its realpath.
	 */
	public function testSetPathOfAlias_newAlias(): void
	{
		$alias = 'PradoTestAlias' . uniqid();
		Prado::setPathOfAlias($alias, PRADO_DIR);
		$this->assertSame(realpath(PRADO_DIR), Prado::getPathOfAlias($alias));
	}

	/**
	 * setPathOfAlias() throws TInvalidDataValueException when the alias name contains a dot.
	 */
	public function testSetPathOfAlias_dottedName_throwsException(): void
	{
		$this->expectException(\Prado\Exceptions\TInvalidDataValueException::class);
		Prado::setPathOfAlias('my.alias', PRADO_DIR);
	}

	/**
	 * setPathOfAlias() throws TInvalidDataValueException when the path does not exist.
	 */
	public function testSetPathOfAlias_invalidPath_throwsException(): void
	{
		$this->expectException(\Prado\Exceptions\TInvalidDataValueException::class);
		Prado::setPathOfAlias('ValidAliasName', '/nonexistent/path/xyz99999/does/not/exist');
	}

	// -------------------------------------------------------------------------
	// Prado::getPathOfNamespace()
	// -------------------------------------------------------------------------

	/**
	 * getPathOfNamespace() resolves a class namespace to its .php file path.
	 */
	public function testGetPathOfNamespace_withKnownClass(): void
	{
		$path = Prado::getPathOfNamespace('Prado\TApplication', '.php');
		$this->assertIsString($path);
		$this->assertStringEndsWith('TApplication.php', $path);
		$this->assertFileExists($path);
	}

	/**
	 * getPathOfNamespace() resolves a directory namespace ('*') to a directory path.
	 */
	public function testGetPathOfNamespace_withDirectoryNamespace(): void
	{
		$path = Prado::getPathOfNamespace('Prado\Web\UI\*');
		$this->assertIsString($path);
		$this->assertDirectoryExists($path);
	}

	/**
	 * getPathOfNamespace() returns null when the leading alias is not registered.
	 */
	public function testGetPathOfNamespace_withUnknownAlias(): void
	{
		$path = Prado::getPathOfNamespace('NonExistentAliasXYZ99999\SomeClass', '.php');
		$this->assertNull($path);
	}

	// -------------------------------------------------------------------------
	// Prado::varDump()
	// -------------------------------------------------------------------------

	/**
	 * varDump() returns a non-empty string representation of any value.
	 */
	public function testVarDump_returnsStringRepresentation(): void
	{
		$result = Prado::varDump(['key' => 'value']);
		$this->assertIsString($result);
		$this->assertNotEmpty($result);
	}

	/**
	 * varDump() with null contains 'null' in the output.
	 */
	public function testVarDump_withNull(): void
	{
		$result = Prado::varDump(null);
		$this->assertIsString($result);
		$this->assertStringContainsStringIgnoringCase('null', $result);
	}

	// -------------------------------------------------------------------------
	// Prado::localize()
	// -------------------------------------------------------------------------

	/**
	 * localize() substitutes named parameters via strtr() when no globalization
	 * handler is configured (the no-translation fast path exercised by the test bootstrap).
	 */
	public function testLocalize_withNoGlobalization_substitutesParameters(): void
	{
		$result = Prado::localize('Hello {name}!', ['name' => 'World']);
		$this->assertSame('Hello World!', $result);
	}

	/**
	 * localize() returns the original text unchanged when no parameters are given.
	 */
	public function testLocalize_withNoParameters_returnsOriginalText(): void
	{
		$result = Prado::localize('Plain text message');
		$this->assertSame('Plain text message', $result);
	}

	// -------------------------------------------------------------------------
	// Prado::poweredByPrado()
	// -------------------------------------------------------------------------

	/**
	 * poweredByPrado() returns an HTML anchor string referencing the PRADO project.
	 *
	 * The test bootstrap's TAssetManager has no web-accessible base path, so this
	 * test nulls the application temporarily (safe because PRADO_TEST_RUN bypasses
	 * the singleton guard) to exercise the no-AssetManager fallback URL path.
	 */
	public function testPoweredByPrado_returnsHtmlAnchor(): void
	{
		$app = Prado::getApplication();
		Prado::setApplication(null);
		try {
			$result = Prado::poweredByPrado();
		} finally {
			Prado::setApplication($app);
		}
		$this->assertIsString($result);
		$this->assertStringContainsString('<a', $result);
		$this->assertStringContainsString('pradosoft', $result);
	}

	/**
	 * poweredByPrado() with logoType=1 includes 'powered2' in the image URL.
	 */
	public function testPoweredByPrado_withLogoType1_usesPowered2Image(): void
	{
		$app = Prado::getApplication();
		Prado::setApplication(null);
		try {
			$result = Prado::poweredByPrado(1);
		} finally {
			Prado::setApplication($app);
		}
		$this->assertIsString($result);
		$this->assertStringContainsString('<a', $result);
		$this->assertStringContainsString('powered2', $result);
	}
}
