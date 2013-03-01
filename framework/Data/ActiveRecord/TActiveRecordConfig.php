<?php
/**
 * TActiveRecordConfig class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2013 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id: TActiveRecordConfig.php 3245 2013-01-07 20:23:32Z ctrlaltca $
 * @package System.Data.ActiveRecord
 */

Prado::using('System.Data.TDataSourceConfig');
Prado::using('System.Data.ActiveRecord.TActiveRecordManager');

/**
 * TActiveRecordConfig module configuration class.
 *
 * Database configuration for the default ActiveRecord manager instance.
 *
 * Example: application.xml configuration
 * <code>
 * <modules>
 * 	<module class="System.Data.ActiveRecord.TActiveRecordConfig" EnableCache="true">
 * 		<database ConnectionString="mysql:host=localhost;dbname=test"
 * 			Username="dbuser" Password="dbpass" />
 * 	</module>
 * </modules>
 * </code>
 *
 * MySQL database definition:
 * <code>
 * CREATE TABLE `blogs` (
 *  `blog_id` int(10) unsigned NOT NULL auto_increment,
 *  `blog_name` varchar(255) NOT NULL,
 *  `blog_author` varchar(255) NOT NULL,
 *  PRIMARY KEY  (`blog_id`)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
 * </code>
 *
 * Record php class:
 * <code>
 * class Blogs extends TActiveRecord
 * {
 * 	public $blog_id;
 *	public $blog_name;
 *	public $blog_author;
 *
 *	public static function finder($className=__CLASS__)
 *	{
 *		return parent::finder($className);
 *	}
 * }
 * </code>
 *
 * Usage example:
 * <code>
 * class Home extends TPage
 * {
 * 	function onLoad($param)
 * 	{
 * 		$blogs = Blogs::finder()->findAll();
 *      print_r($blogs);
 * 	}
 * }
 * </code>
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @version $Id: TActiveRecordConfig.php 3245 2013-01-07 20:23:32Z ctrlaltca $
 * @package System.Data.ActiveRecord
 * @since 3.1
 */
class TActiveRecordConfig extends TDataSourceConfig
{
	const DEFAULT_MANAGER_CLASS = 'System.Data.ActiveRecord.TActiveRecordManager';
	const DEFAULT_GATEWAY_CLASS = 'System.Data.ActiveRecord.TActiveRecordGateway';

	/**
	 * Defaults to {@link TActiveRecordConfig::DEFAULT_GATEWAY_CLASS DEFAULT_MANAGER_CLASS}
	 * @var string
	 */
	private $_managerClass = self::DEFAULT_MANAGER_CLASS;

	/**
	 * Defaults to {@link TActiveRecordConfig::DEFAULT_GATEWAY_CLASS DEFAULT_GATEWAY_CLASS}
	 * @var string
	 */
	private $_gatewayClass = self::DEFAULT_GATEWAY_CLASS;

	/**
	 * @var TActiveRecordManager
	 */
	private $_manager = null;

	private $_enableCache=false;

	/**
	 * Defaults to '{@link TActiveRecordInvalidFinderResult::Null Null}'
	 *
	 * @var TActiveRecordInvalidFinderResult
	 * @since 3.1.5
	 */
	private $_invalidFinderResult = TActiveRecordInvalidFinderResult::Null;

	/**
	 * Initialize the active record manager.
	 * @param TXmlDocument xml configuration.
	 */
	public function init($xml)
	{
		parent::init($xml);
		$manager = $this -> getManager();
		if($this->getEnableCache())
			$manager->setCache($this->getApplication()->getCache());
		$manager->setDbConnection($this->getDbConnection());
		$manager->setInvalidFinderResult($this->getInvalidFinderResult());
		$manager->setGatewayClass($this->getGatewayClass());
	}

	/**
	 * @return TActiveRecordManager
	 */
	public function getManager() {
		if($this->_manager === null)
			$this->_manager = Prado::createComponent($this -> getManagerClass());
		return TActiveRecordManager::getInstance($this->_manager);
	}

	/**
	 * Set implementation class of ActiveRecordManager
	 * @param string $value
	 */
	public function setManagerClass($value)
	{
		$this->_managerClass = TPropertyValue::ensureString($value);
	}

	/**
	 * @return string the implementation class of ActiveRecordManager. Defaults to {@link TActiveRecordConfig::DEFAULT_GATEWAY_CLASS DEFAULT_MANAGER_CLASS}
	 */
	public function getManagerClass()
	{
		return $this->_managerClass;
	}

	/**
	 * Set implementation class of ActiveRecordGateway
	 * @param string $value
	 */
	public function setGatewayClass($value)
	{
		$this->_gatewayClass = TPropertyValue::ensureString($value);
	}

	/**
	 * @return string the implementation class of ActiveRecordGateway. Defaults to {@link TActiveRecordConfig::DEFAULT_GATEWAY_CLASS DEFAULT_GATEWAY_CLASS}
	 */
	public function getGatewayClass()
	{
		return $this->_gatewayClass;
	}

	/**
	 * Set true to cache the table meta data.
	 * @param boolean true to cache sqlmap instance.
	 */
	public function setEnableCache($value)
	{
		$this->_enableCache = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return boolean true if table meta data should be cached, false otherwise.
	 */
	public function getEnableCache()
	{
		return $this->_enableCache;
	}

	/**
	 * @return TActiveRecordInvalidFinderResult Defaults to '{@link TActiveRecordInvalidFinderResult::Null Null}'.
	 * @see setInvalidFinderResult
	 * @since 3.1.5
	 */
	public function getInvalidFinderResult()
	{
		return $this->_invalidFinderResult;
	}

	/**
	 * Define the way an active record finder react if an invalid magic-finder invoked
	 *
	 * @param TActiveRecordInvalidFinderResult
	 * @see getInvalidFinderResult
	 * @since 3.1.5
	 */
	public function setInvalidFinderResult($value)
	{
		$this->_invalidFinderResult = TPropertyValue::ensureEnum($value, 'TActiveRecordInvalidFinderResult');
	}
}
