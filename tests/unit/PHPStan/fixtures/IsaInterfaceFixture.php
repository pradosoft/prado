<?php

/**
 * Fixture for TComponentIsaTypeSpecifyingExtension — interface narrowing.
 *
 * This file isolates the interface-narrowing case of isa(): PHPStan should not
 * report errors on this file when the extension is active.  When the extension
 * is disabled (or only narrows to TComponent subclasses), PHPStan cannot resolve
 * the call on the interface type and reports "Call to an undefined method".
 *
 * This fixture is intentionally separate from IsaFixture.php so that the
 * interface case is independently verifiable.
 */

declare(strict_types=1);

namespace PradoTests\PHPStan\Fixtures;

use Prado\TComponent;

/**
 * An interface with a method that only makes sense on implementors.
 * PHPStan must see this method as available after an isa() guard.
 */
interface IsaFixtureInterface
{
	public function interfaceSpecificMethod(): string;
}

/**
 * A TComponent that also implements the interface above.
 */
class IsaFixtureInterfaceComponent extends TComponent implements IsaFixtureInterface
{
	public function interfaceSpecificMethod(): string
	{
		return 'interface';
	}
}

/**
 * A second interface with a different unique method, to confirm the extension
 * handles any interface class string, not just one specific one.
 */
interface IsaFixtureOtherInterface
{
	public function otherInterfaceMethod(): int;
}

/**
 * A TComponent implementing the second interface.
 */
class IsaFixtureOtherInterfaceComponent extends TComponent implements IsaFixtureOtherInterface
{
	public function otherInterfaceMethod(): int
	{
		return 99;
	}
}

class IsaInterfaceCaller
{
	/**
	 * Canonical interface guard: $component is TComponent but the isa() guard
	 * narrows it to IsaFixtureInterface, enabling the interface-specific call.
	 */
	public function testIsaGuardInterface(TComponent $component): void
	{
		if ($component->isa(IsaFixtureInterface::class)) {
			// With extension: $component is narrowed to IsaFixtureInterface
			$component->interfaceSpecificMethod();
		}
	}

	/**
	 * A second interface narrowing target, confirming the extension is generic.
	 */
	public function testIsaGuardOtherInterface(TComponent $component): void
	{
		if ($component->isa(IsaFixtureOtherInterface::class)) {
			$component->otherInterfaceMethod();
		}
	}
}
