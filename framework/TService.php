<?php
/**
 * TService class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2013 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id: TService.php 3245 2013-01-07 20:23:32Z ctrlaltca $
 * @package System
 */

/**
 * TService class.
 *
 * TService implements the basic methods required by IService and may be
 * used as the basic class for application services.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: TService.php 3245 2013-01-07 20:23:32Z ctrlaltca $
 * @package System
 * @since 3.0
 */
abstract class TService extends TApplicationComponent implements IService
{
	/**
	 * @var string service id
	 */
	private $_id;
	/**
	 * @var boolean whether the service is enabled
	 */
	private $_enabled=true;

	/**
	 * Initializes the service and attaches {@link run} to the RunService event of application.
	 * This method is required by IService and is invoked by application.
	 * @param TXmlElement module configuration
	 */
	public function init($config)
	{
	}

	/**
	 * @return string id of this service
	 */
	public function getID()
	{
		return $this->_id;
	}

	/**
	 * @param string id of this service
	 */
	public function setID($value)
	{
		$this->_id=$value;
	}

	/**
	 * @return boolean whether the service is enabled
	 */
	public function getEnabled()
	{
		return $this->_enabled;
	}

	/**
	 * @param boolean whether the service is enabled
	 */
	public function setEnabled($value)
	{
		$this->_enabled=TPropertyValue::ensureBoolean($value);
	}

	/**
	 * Runs the service.
	 */
	public function run()
	{
	}
}

