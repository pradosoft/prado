<?php
/**
 * Prado bootstrap file.
 *
 * This file is intended to be included in the entry script of Prado applications.
 * It defines Prado class by extending PradoBase, a static class providing globally
 * available functionalities that enable PRADO component model and error handling mechanism.
 *
 * By including this file, the PHP error and exception handlers are set as
 * PRADO handlers, and an __autoload function is provided that automatically
 * loads a class file if the class is not defined.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2005-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/COPYRIGHT
 * @package Prado
 */

namespace Prado;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Defines Prado class if not defined.
 */
if(!class_exists('Prado',false))
{
	/**
	 * Prado class.
	 *
	 * @author Qiang Xue <qiang.xue@gmail.com>
	* @package Prado
	 * @since 3.0
	 */
	class Prado extends PradoBase
	{
	}
}

/**
 * Initializes error and exception handlers
 */
Prado::initErrorHandlers();

class_alias('\Prado\TApplication', 'TApplication', true);
class_alias('\Prado\Web\Services\TPageService', 'TPageService', true);
class_alias('\Prado\Web\UI\TPage', 'TPage', true);
