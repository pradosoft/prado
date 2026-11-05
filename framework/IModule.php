<?php

/**
 * Core interfaces essential for TApplication class.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado;

/**
 * IModule interface.
 *
 * This interface must be implemented by application modules.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
interface IModule
{
	/**
	 * Initializes the module.
	 * @param null|array|\Prado\Xml\TXmlElement $config the configuration for the module,
	 *   `TXmlElement` for XML configuration, `array` for PHP configuration, and null
	 *   when invoked without configuration.
	 */
	public function init($config);
	/**
	 * @return string ID of the module
	 */
	public function getID();
	/**
	 * @param string $id ID of the module
	 */
	public function setID($id);
}
