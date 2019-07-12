<?php
/**
 * TCache and cache dependency classes.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Caching
 */

namespace Prado\Caching;

use Prado\Prado;
use Prado\TApplicationMode;

/**
 * TApplicationStateCacheDependency class.
 *
 * TApplicationStateCacheDependency performs dependency checking based on
 * the mode of the currently running PRADO application.
 * The dependency is reportedly as unchanged if and only if the application
 * is running in performance mode.
 *
 * You may chain this dependency together with other dependencies
 * so that only when the application is not in performance mode the other dependencies
 * will be checked.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Caching
 * @since 3.1.0
 */
class TApplicationStateCacheDependency extends TCacheDependency
{
	/**
	 * Performs the actual dependency checking.
	 * This method returns true if the currently running application is not in performance mode.
	 * @return bool whether the dependency is changed or not.
	 */
	public function getHasChanged()
	{
		return Prado::getApplication()->getMode() !== TApplicationMode::Performance;
	}
}
