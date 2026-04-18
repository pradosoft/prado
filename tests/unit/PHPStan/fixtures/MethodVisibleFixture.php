<?php

/**
 * Fixture for PradoMethodVisibleStaticMethodTypeSpecifyingExtension tests.
 *
 * PHPStan should not report any errors on this file when the extension is active.
 * When the extension is disabled, PHPStan should report "Call to an undefined
 * method" for every guarded call inside a Prado::method_visible() block.
 */

declare(strict_types=1);

namespace PradoTests\PHPStan\Fixtures;

use Prado\Prado;
use Prado\TComponent;

/**
 * Declares two optional methods that callers check via method_visible().
 */
class MethodVisibleFixtureComponent extends TComponent
{
	public function initialize(string $id): void
	{
	}

	public function configure(array $options): void
	{
	}
}

/**
 * Caller that uses Prado::method_visible() guards.
 * Mirrors the pattern used extensively inside TComponent itself.
 */
class MethodVisibleCaller extends TComponent
{
	/**
	 * Guard on an external object.
	 */
	public function testMethodVisibleOnObject(MethodVisibleFixtureComponent $component): void
	{
		if (Prado::method_visible($component, 'initialize')) {
			$component->initialize('myId');
		}
	}

	/**
	 * Guard on $this — used in TComponent::__get / __set and similar lifecycle code.
	 */
	public function testMethodVisibleOnSelf(): void
	{
		if (Prado::method_visible($this, 'initialize')) {
			$this->initialize('myId');
		}
	}

	/**
	 * A second method name to confirm generality.
	 */
	public function testMethodVisibleConfigure(MethodVisibleFixtureComponent $component): void
	{
		if (Prado::method_visible($component, 'configure')) {
			$component->configure([]);
		}
	}
}
