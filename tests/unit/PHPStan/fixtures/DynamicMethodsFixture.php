<?php

/**
 * Fixture for DynamicMethodsClassReflectionExtension tests.
 *
 * PHPStan must never report "Call to an undefined method" for any method whose
 * name starts with 'dy' or 'fx' on a TComponent subclass.  This is true whether
 * or not the method is actually declared on the class.
 *
 * NOTE: Do not use fxAttachClassBehavior or fxDetachClassBehavior here — those
 * ARE real declared methods on TComponent (with specific signatures) so PHPStan
 * type-checks them normally and they do not exercise the dynamic extension.
 * Use invented names that do not exist on TComponent.
 */

declare(strict_types=1);

namespace PradoTests\PHPStan\Fixtures;

use Prado\TComponent;

class DynamicMethodsFixtureComponent extends TComponent
{
}

class DynamicMethodsCaller
{
	/**
	 * Dynamic behavior events (dy prefix) must be accepted unconditionally.
	 * PHPStan must not report "Call to an undefined method" for invented dy names.
	 */
	public function testDyPrefixMethods(DynamicMethodsFixtureComponent $component): void
	{
		// All of these are valid regardless of whether the method is declared.
		$component->dyCustomMethod();
		$component->dyInitialize('arg');
		$component->dyValidate('value', true);
	}

	/**
	 * Global events (fx prefix) must be accepted unconditionally.
	 * PHPStan must not report "Call to an undefined method" for invented fx names.
	 */
	public function testFxPrefixMethods(DynamicMethodsFixtureComponent $component): void
	{
		// Invented fx names — not declared on TComponent.
		$component->fxCustomBroadcast('payload');
		$component->fxNotifySubscribers(42);
		$component->fxBuildIndex();
	}

	/**
	 * Additional dy/fx calls on the same component instance.
	 */
	public function testMoreDynamicMethods(DynamicMethodsFixtureComponent $component): void
	{
		$component->dyBeforeSave();
		$component->dyAfterLoad('result');
		$component->fxDispatchEvent('name', []);
	}
}
