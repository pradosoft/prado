<?php
/**
 * Prado bootstrap file.
 *
 * This file is intended to be included in the entry script of Prado applications.
 * It defines Prado class by extending PradoBase, a static class providing globally
 * available functionalities that enable PRADO component model and error handling mechanism.
 *
 * By including this file, the PHP error and exception handlers are set as
 * PRADO handlers, and an __autoload function is provided that automatiically
 * loads a class file if the class is not defined.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System
 */

/**
 * Includes the PradoBase class file
 */
require_once(dirname(__FILE__).'/PradoBase.php');

/**
 * Defines Prado class if not defined.
 */
if(!class_exists('Prado',false))
{
	class Prado extends PradoBase
	{
	}
}

/**
 * Defines __autoload function if not defined.
 */
if(!function_exists('__autoload'))
{
	function __autoload($className)
	{
		Prado::autoload($className);
	}
}

/**
 * Initializes error and exception handlers
 */
Prado::initErrorHandlers();

/**
 * Includes TApplication class file
 */
require_once(dirname(__FILE__).'/TApplication.php');

?>