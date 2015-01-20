<?php
/**
 * TControl, TControlCollection, TEventParameter and INamingContainer class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI
 */

/**
 * ITemplate interface
 *
 * ITemplate specifies the interface for classes encapsulating
 * parsed template structures.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI
 * @since 3.0
 */
interface ITemplate
{
	/**
	 * Instantiates the template.
	 * Content in the template will be instantiated as components and text strings
	 * and passed to the specified parent control.
	 * @param TControl the parent control
	 */
	public function instantiateIn($parent);
}