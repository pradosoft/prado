<?php
/**
 * Prado bootstrap file.
 *
 * This file must be included first in order to run prado applications.
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
		require_once($className.Prado::CLASS_FILE_EXT);
	}
}

/**
 * Sets up error handler to convert PHP errors into exceptions that can be caught.
 */
set_error_handler(array('Prado','phpErrorHandler'),error_reporting());

/**
 * Sets up handler to handle uncaught exceptions.
 */
set_exception_handler(array('Prado','exceptionHandler'));

/**
 * Includes TApplication class file
 */
require_once(dirname(__FILE__).'/TApplication.php');

?>