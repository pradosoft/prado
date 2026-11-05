<?php

/**
 * TNestedPathComponent class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Harness;

use Prado\TComponent;
use Prado\Test\Unit\Harness\Traits\TNestedPathTrait;

/**
 * TNestedPathComponent exposes a 'Cfg' property whose setter replaces the nested
 * object outright, the way configuration-style setters do.
 *
 * A source that applies 'Cfg' after 'Cfg.Size' loses the nested write, so this
 * component reports whether a property source orders a parent path before the
 * paths nested beneath it. {@see \Prado\Test\Unit\Harness\Web\UI\TNestedPathControl}
 * is the TControl counterpart, for a source that requires a control.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TNestedPathComponent extends TComponent
{
	use TNestedPathTrait;
}
