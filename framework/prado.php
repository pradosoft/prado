<?php
/**
 * Prado bootstrap file.
 *
 * This file is intended to be included in the entry script of Prado applications.
 * It defines Prado class by extending PradoBase, a static class providing globally
 * available functionalities to Prado applications. It also sets PHP error and
 * exception handler functions, and provides a __autoload function which automatically
 * loads a class file if the class is not defined.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System
 */

/**
 * Includes the Prado core header file
 */
require_once(dirname(__FILE__).'/core.php');

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
		include_once($className.Prado::CLASS_FILE_EXT);
		if(!class_exists($className,false))
			Prado::fatalError("Class file for '$className' cannot be found.");
	}
}

//Error handlers are set during TApplication::run(),
//Exception stack is empty if set error handlers requires TApplication
//and TApplication then causes an exception during instantiation.
//see TApplication::initDefaultExceptionHandlers()

/**
 * Includes TApplication class file
 */
require_once(dirname(__FILE__).'/TApplication.php');

?>