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
 * IService interface.
 *
 * This interface must be implemented by services.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
interface IService
{
	/**
	 * Initializes the service.
	 * @param \Prado\Xml\TXmlElement $config the configuration for the service
	 */
	public function init($config);
	/**
	 * @return string ID of the service
	 */
	public function getID();
	/**
	 * @param string $id ID of the service
	 */
	public function setID($id);
	/**
	 * @return bool whether the service is enabled
	 */
	public function getEnabled();
	/**
	 * @param bool $value whether the service is enabled
	 */
	public function setEnabled($value);
	/**
	 * Runs the service.
	 */
	public function run();
}
