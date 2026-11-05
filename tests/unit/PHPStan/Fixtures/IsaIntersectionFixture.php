<?php

/**
 * Fixture for TComponent::isa() narrowing of intersection-typed subjects.
 *
 * PHPStan never routes a method call to a MethodTypeSpecifyingExtension when the
 * subject has more than one object class name, so an intersection subject such as
 * `IService&TComponent` is narrowed by the `@phpstan-assert-if-true` tag on
 * {@see \Prado\TComponent::isa()} instead of by TComponentIsaTypeSpecifyingExtension.
 * That tag travels with the framework, so this fixture reports zero errors with or
 * without the PRADO services block.
 */

declare(strict_types=1);

namespace Prado\Test\Unit\PHPStan\Fixtures;

use Prado\IService;
use Prado\TComponent;
use Prado\Web\Services\TPageService;

/**
 * An interface that does not extend TComponent, so `instanceof TComponent` on one
 * of its instances produces an intersection type.
 */
interface IsaIntersectionFixtureService
{
	public function serviceSpecificMethod(): string;
}

/**
 * A second interface used as an interface narrowing target.
 */
interface IsaIntersectionFixtureTarget
{
	public function targetInterfaceMethod(): string;
}

/**
 * The narrowing target: a TComponent that also implements the interfaces above.
 */
class IsaIntersectionFixtureComponent extends TComponent implements IsaIntersectionFixtureService, IsaIntersectionFixtureTarget
{
	public function serviceSpecificMethod(): string
	{
		return 'service';
	}

	public function targetInterfaceMethod(): string
	{
		return 'target';
	}

	public function targetSpecificMethod(): int
	{
		return 7;
	}
}

class IsaIntersectionCaller
{
	/**
	 * The subject is IsaIntersectionFixtureService&TComponent inside the guard.
	 * The isa() guard narrows it to IsaIntersectionFixtureComponent.
	 */
	public function testIsaGuardOnIntersection(?IsaIntersectionFixtureService $service): void
	{
		if ($service !== null && $service instanceof TComponent && $service->isa(IsaIntersectionFixtureComponent::class)) {
			$service->targetSpecificMethod();
		}
	}

	/**
	 * A nullable IService is checked for TComponent and then narrowed to
	 * TPageService by isa().  This is the shape a service lookup takes wherever the
	 * declared return type is the IService interface rather than a TComponent class.
	 */
	public function testIsaGuardOnServiceIntersection(?IService $service): void
	{
		if ($service !== null && $service instanceof TComponent && $service->isa(TPageService::class)) {
			$service->getRequestedPage();
		}
	}

	/**
	 * Narrowing an intersection subject to an interface works the same way.
	 */
	public function testIsaGuardOnIntersectionToInterface(TComponent $component): void
	{
		if ($component instanceof IsaIntersectionFixtureService && $component->isa(IsaIntersectionFixtureTarget::class)) {
			$component->targetInterfaceMethod();
		}
	}
}
