<?php

/**
 * TCache and cache dependency classes.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Caching;

use Prado\Prado;
use Prado\TApplicationMode;

/**
 * TApplicationStateCacheDependency class.
 *
 * TApplicationStateCacheDependency performs dependency checking based on
 * the mode of the currently running PRADO application.
 * The dependency is reported as unchanged if and only if the application
 * is running in performance mode.
 *
 * You may chain this dependency together with other dependencies
 * so that only when the application is not in performance mode the other dependencies
 * will be checked.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.1.0
 */
class TApplicationStateCacheDependency extends TCacheDependency
{
	/**
	 * @return bool true if the application is not running in performance mode.
	 */
	public function getHasChanged()
	{
		return Prado::getApplication()->getMode() !== TApplicationMode::Performance;
	}
}
