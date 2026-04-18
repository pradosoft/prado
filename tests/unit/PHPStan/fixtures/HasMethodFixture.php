<?php

/**
 * Fixture for TComponentHasMethodTypeSpecifyingExtension tests.
 *
 * PHPStan should not report any errors on this file when the extension is active.
 * When the extension is disabled, PHPStan should report "Call to an undefined
 * method" for every guarded method call inside an if ($this->hasMethod(...)) block.
 */

declare(strict_types=1);

namespace PradoTests\PHPStan\Fixtures;

use Prado\TComponent;

/**
 * A concrete subclass that declares assertUninitialized() – this is the method
 * the tests try to call after the hasMethod guard.
 */
class HasMethodFixtureComponent extends TComponent
{
	public function assertUninitialized(string $connectionId): void
	{
	}

	public function validate(mixed $value): bool
	{
		return true;
	}
}

/**
 * Caller that uses hasMethod() guards before invoking methods.
 * Without the extension, PHPStan cannot prove the methods exist and emits errors.
 */
class HasMethodCaller extends TComponent
{
	/**
	 * The canonical motivating example from the bug report.
	 * hasMethod guard for a method declared on a sibling subclass.
	 */
	public function testHasMethodGuard(HasMethodFixtureComponent $component): void
	{
		if ($component->hasMethod('dynamicMethod')) {
			$component->dynamicMethod('someValue');
		}
	}

	/**
	 * $this-based guard: the real-world pattern where a framework class checks
	 * for an optional lifecycle method on itself.
	 */
	public function testSelfHasMethodGuard(): void
	{
		if ($this->hasMethod('dynamicMethod')) {
			$this->dynamicMethod('Property');
		}
	}

	/**
	 * A second method name to confirm the extension handles any constant string,
	 * not just 'assertUninitialized'.
	 */
	public function testAlternativeMethodGuard(HasMethodFixtureComponent $component): void
	{
		if ($component->hasMethod('validate')) {
			$component->validate('someValue');
		}
	}
}
