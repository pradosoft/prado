<?php
/**
 * Core interfaces essential for TApplication class.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System
 */

/**
 * IService interface.
 *
 * This interface must be implemented by services.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System
 * @since 3.0
 */
interface IService
{
	/**
	 * Initializes the service.
	 * @param TXmlElement the configuration for the service
	 */
	public function init($config);
	/**
	 * @return string ID of the service
	 */
	public function getID();
	/**
	 * @param string ID of the service
	 */
	public function setID($id);
	/**
	 * @return boolean whether the service is enabled
	 */
	public function getEnabled();
	/**
	 * @param boolean whether the service is enabled
	 */
	public function setEnabled($value);
	/**
	 * Runs the service.
	 */
	public function run();
}