<?php

/**
 * Negative fixture for TComponent::isa() narrowing of intersection-typed subjects.
 *
 * This file is expected to produce exactly one PHPStan error.  The guarded call
 * names a method that exists on no class in the hierarchy, so PHPStan reports
 * "Call to an undefined method" against the class the isa() guard narrowed to.
 * The reported class name proves the narrowing landed on the asserted class
 * rather than on the original intersection or on a permissive type.
 */

declare(strict_types=1);

namespace Prado\Test\Unit\PHPStan\Fixtures;

use Prado\TComponent;

/**
 * An interface that does not extend TComponent, so `instanceof TComponent` on one
 * of its instances produces an intersection type.
 */
interface IsaNegativeFixtureService
{
	public function serviceSpecificMethod(): string;
}

/**
 * The narrowing target named by the isa() guard below.
 */
class IsaNegativeFixtureComponent extends TComponent implements IsaNegativeFixtureService
{
	public function serviceSpecificMethod(): string
	{
		return 'service';
	}
}

class IsaIntersectionNegativeCaller
{
	/**
	 * The isa() guard narrows the intersection to IsaNegativeFixtureComponent.
	 * The call below exists on no class, so PHPStan names that narrowed class.
	 */
	public function testIsaGuardReportsNarrowedClass(?IsaNegativeFixtureService $service): void
	{
		if ($service !== null && $service instanceof TComponent && $service->isa(IsaNegativeFixtureComponent::class)) {
			$service->thisMethodDoesNotExist();
		}
	}
}
