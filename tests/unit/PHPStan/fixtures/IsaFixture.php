<?php

/**
 * Fixture for TComponentIsaTypeSpecifyingExtension tests.
 *
 * PHPStan should not report errors on this file when the extension is active.
 * When the extension is disabled, PHPStan cannot narrow the type inside the
 * isa() guard and will report "Call to an undefined method" for the narrowed call.
 */

declare(strict_types=1);

namespace PradoTests\PHPStan\Fixtures;

use Prado\TComponent;

/**
 * A concrete subclass with its own method, used as the narrowing target.
 */
class IsaFixtureSubComponent extends TComponent
{
	public function subclassSpecificMethod(): string
	{
		return 'specific';
	}
}

/**
 * Another distinct subclass so we can verify narrowing applies to the right type.
 */
class IsaFixtureOtherComponent extends TComponent
{
	public function otherSpecificMethod(): int
	{
		return 42;
	}
}

class IsaCaller
{
	/**
	 * Canonical isa() guard: $component is TComponent but the guard narrows it
	 * to IsaFixtureSubComponent, enabling the subclass-specific call.
	 */
	public function testIsaGuard(TComponent $component): void
	{
		if ($component->isa(IsaFixtureSubComponent::class)) {
			// With extension: $component is narrowed to IsaFixtureSubComponent
			$component->subclassSpecificMethod();
		}
	}

	/**
	 * A second narrowing target to confirm the extension handles any subclass.
	 */
	public function testIsaGuardOther(TComponent $component): void
	{
		if ($component->isa(IsaFixtureOtherComponent::class)) {
			$component->otherSpecificMethod();
		}
	}
}
