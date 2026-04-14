<?php

/**
 * TDbModule class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util;

use Prado\Data\TDbPropertiesTrait;
use Prado\TModule;

/**
 * TDbModule class.
 *
 * The base class for Database modules
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TDbModule extends TModule implements IDbModule
{
	use TDbPropertiesTrait;
}
