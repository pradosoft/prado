<?php
/**
 * Core interfaces essential for TApplication class.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2005-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado
 */

namespace Prado;

/**
 * IService interface.
 *
 * This interface must be implemented by services.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado
 * @since 3.0
 */
interface IService
{
	/**
	 * Initializes the service.
	 * @param TXmlElement $config the configuration for the service
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
	 * @return boolean whether the service is enabled
	 */
	public function getEnabled();
	/**
	 * @param boolean $value whether the service is enabled
	 */
	public function setEnabled($value);
	/**
	 * Runs the service.
	 */
	public function run();
}
