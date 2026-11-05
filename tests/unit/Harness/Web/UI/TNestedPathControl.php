<?php

/**
 * TNestedPathControl class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Harness\Web\UI;

use Prado\Test\Unit\Harness\Traits\TNestedPathTrait;
use Prado\Web\UI\TControl;

/**
 * TNestedPathControl exposes a 'Cfg' property whose setter replaces the nested
 * object outright, the way configuration-style setters do.
 *
 * A source that applies 'Cfg' after 'Cfg.Size' loses the nested write, so this
 * control reports whether a property source orders a parent path before the
 * paths nested beneath it. {@see \Prado\Test\Unit\Harness\TNestedPathComponent}
 * is the plain TComponent counterpart.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TNestedPathControl extends TControl
{
	use TNestedPathTrait;
}
