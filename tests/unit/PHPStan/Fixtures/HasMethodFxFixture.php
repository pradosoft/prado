<?php

/**
 * Fixture for the `fx` exemption in TComponentHasMethodTypeSpecifyingExtension.
 *
 * TComponent::hasMethod() reports an `fx` global event only when the object
 * implements it or an enabled behavior provides it, so a hasMethod('fx…') guard
 * is a real runtime check. DynamicMethodsClassReflectionExtension makes every
 * `fx` method callable; without the exemption PHPStan reports each guard below
 * as always true (`method.alreadyNarrowedType`).
 *
 * No guarded type declares the method, so this file has no fixture-scaffolding
 * diagnostics at level 4 and its test counts every message unfiltered.
 */

declare(strict_types=1);

namespace Prado\Test\Unit\PHPStan\Fixtures;

use Prado\TComponent;
use Prado\Util\Helpers\TProcessHelper;

class HasMethodFxCaller extends TComponent
{
	/**
	 * Literal `fx` name on a TComponent-typed value.
	 */
	public function testLiteralFxGuard(TComponent $component): void
	{
		if ($component->hasMethod('fxOptionalEvent')) {
			$component->fxOptionalEvent('value');
		}
	}

	/**
	 * Class-constant `fx` name, as in TForkable.
	 */
	public function testConstantFxGuard(TComponent $component): void
	{
		if ($component->hasMethod(TProcessHelper::FX_PREPARE_FOR_FORK)) {
			$component->attachEventHandler(TProcessHelper::FX_PREPARE_FOR_FORK, [$component, TProcessHelper::FX_PREPARE_FOR_FORK]);
		}
	}

	/**
	 * `$this` guard on an `fx` name.
	 */
	public function testSelfFxGuard(): void
	{
		if ($this->hasMethod('fxOptionalEvent')) {
			$this->fxOptionalEvent('value');
		}
	}
}
