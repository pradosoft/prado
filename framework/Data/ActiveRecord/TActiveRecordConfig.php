<?php
/**
 * TActiveRecordConfig class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2008 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Data.ActiveRecord
 */

Prado::using('System.Data.TDataSourceConfig');

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
 * @version $Id$
 * @package System.Data.ActiveRecord
 * @since 3.1
 */
class TActiveRecordConfig extends TDataSourceConfig
{
	private $_enableCache=false;

	/**
	 * Initialize the active record manager.
	 * @param TXmlDocument xml configuration.
	 */
	public function init($xml)
	{
		parent::init($xml);
		Prado::using('System.Data.ActiveRecord.TActiveRecordManager');
		$manager = TActiveRecordManager::getInstance();
		if($this->getEnableCache())
			$manager->setCache($this->getApplication()->getCache());
		$manager->setDbConnection($this->getDbConnection());
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
}

?>
