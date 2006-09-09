<?php

require_once(dirname(__FILE__).'/TSqlMapClient.php');

/**
 * A singleton class to access the default SqlMapper.
 *
 * Usage: Call configure() once, then use instance() to obtain a TSqlMapper
 * instance. 
 * <code>  
 * TMapper::configure($configFile); 
 * $object = TMapper::instance()->queryForObject('statementName');
 * </code>
 * 
 * If your configuration file is named 'sqlmap.config' you may skip the
 * configure() call.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Id$
 * @package System.DataAccess.SQLMap
 * @since 3.0
 */
class TMapper
{
	/**
	 * Data mapper singleton
	 * @var TSqlMapper
	 */
	private static $_mapper;

	/**
	 * Configure the data mapper singleton instance. 
	 * @param string configuration file
	 * @param boolean true to load configuration from cache.
	 * @return TSqlMapper data mapper instance.
	 */
	public static function configure($configFile,$loadCachedConfig=false)
	{
		if(is_null(self::$_mapper))
		{
			$sqlmap = new TSQLMapClient;
			self::$_mapper = $sqlmap->configure($configFile,$loadCachedConfig);
		}
		return self::$_mapper;
	}

	/**
	 * Gets the data mapper singleton instance. Default configuration file is
	 * 'sqlmap.config'.
	 * @return TSqlMapper singleton instance.
	 */
	public static function instance()
	{
		if(is_null(self::$_mapper))
			self::configure('sqlmap.xml');
		return self::$_mapper;
	}
}

?>