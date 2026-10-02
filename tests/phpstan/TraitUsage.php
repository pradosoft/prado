<?php

declare(strict_types=1);

namespace Prado\Test\PHPStan;

use Prado\Util\Behaviors\TNoUnserializeBehaviorTrait;
use Prado\Util\Behaviors\TNoUnserializeClassBehaviorTrait;
use Prado\Util\TBehavior;
use Prado\Util\TClassBehavior;

final class NoUnserializeBehavior extends TBehavior
{
	use TNoUnserializeBehaviorTrait;
}

final class NoUnserializeClassBehavior extends TClassBehavior
{
	use TNoUnserializeClassBehaviorTrait;
}
