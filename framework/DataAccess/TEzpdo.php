<?php

class TEzpdo extends TDatabaseProvider
{
	/**
	 * @var array list of default ezpdo options.
	 */
	private $_options = array(
		'source_dirs' => null,
		'recursive' => true,
		'compiled_dir' => null, // default to compiled under current dir
		'compiled_file' => 'compiled_file', // the default class map file
		'backup_compiled' => true, // whether to backup old compiled file
		'check_table_exists' => true, // whether always check if table exists before db operation
		'table_prefix' => '', // table prefix (default to none)
		'relation_table' => '_ez_relation_', // the table name for object relations
		'split_relation_table' => true, // whether to split relation table
		'auto_flush' => false, // enable or disable auto flush at the end of script 
		'flush_before_find' => true, // enable or disable auto flush before find() 
		'auto_compile' => true, // enable or disable auto compile 
		'autoload' => false, // enable or disable class autoloading 
		'log_queries' => false, // enable logging queries (for debug only) 
		'dispatch_events' => true, // whether to dispatch events (true by default)
		'default_oid_column' => 'eoid', // oid column name is default to 'eoid' now
		);
	
	/**
	 * @var array List of source directories.
	 */	
	private $_sources = array();
	
	/**
	 * @var epManager ezpdo manager instance.
	 */
	private $_manager;
	
	/**
	 * Initialize the ezpdo module, sets the default compile directory to use
	 * the Prado runtime directory.
	 */
	public function init($config)
	{
		parent::init($config);
		include($this->getEzpdoLibrary().'/ezpdo.php');
		$path = $this->getApplication()->getRuntimePath().'/ezpdo';
		$this->_options['compiled_dir'] = $path;
		if($this->getApplication()->getMode() != TApplication::STATE_PERFORMANCE)
		{
			if(!is_dir($path))
				throw new TConfigurationException('ezpdo_compile_dir_not_found', $path);
			$this->_options['auto_compile'] = false;
		}
	}
	
	/**
	 * @return string ezpdo library directory.
	 */
	protected function getEzpdoLibrary()
	{
		return Prado::getPathOfNamespace('System.3rdParty.ezpdo');
	}
	
	/**
	 * @return array merged database connection options with the other options.
	 */
	protected function getOptions()
	{
		if(strlen($dsn = $this->getConnectionString()) > 0)
			$options['default_dsn'] = $dsn;
		else
			$options['default_dsn'] = $this->buildDsn();
			
		return array_merge($this->_options, $options);
	}
	
	/**
	 * Initialize the ezManager once.
	 */
	protected function initialize()
	{
		Prado::using('System.3rdParty.ezpdo.src.base.epConfig');
		include($this->getEzpdoLibrary().'/src/runtime/epManager.php');
		
		if(is_null($this->_manager))
		{
			$this->_manager = new epManager;
			foreach($this->_sources as $source)
				Prado::using($source.'.*');
			$this->_manager->setConfig(new epConfig($this->getOptions()));
		}
	}
		
	/**
	 * @return epManager the ezpdo manager for this module.
	 */
	public function getConnection()
	{
		$this->initialize();
		return $this->_manager;
	}
	
	/**	
	 * @param string The intput directory, using dot path aliases, that contains
	 * class source files to be compiled. Use commma for multiple directories
	 */
	public function setSourceDirs($values)
	{
		$paths = array();
		foreach(explode(',', $values) as $value)
		{
			$dot = Prado::getPathOfNamespace($value);
			$this->_sources[] = $value;
			if(($path = realpath($dot)) !== false)
				$paths[] = $path; 
		}
		$this->_options['source_dirs'] = implode(',', $paths);
	}
	
	/**
	 * @return string comma delimited list of source directories.
	 */
	public function getSourceDirs()
	{
		return $this->_options['source_dir'];
	}
	
	/**
	 * @param boolean Whether to compile subdirs recursively, default is true.
	 */
	public function setRecursive($value)
	{
		$this->_options['recursive'] = TPropertyValue::ensureBoolean($value);
	}
	
	/**
	 * @return boolean true will compile subdirectories recursively.
	 */
	public function getRecursive()
	{
		return $this->_options['recursive'];
	}
	
	/**
	 * @param string database table prefix.
	 */
	public function setTablePrefix($value)
	{
		$this->_options['table_prefix'] = $value;
	}
	
	/**
	 * @param string the table name for object relations, default is
	 * '_ez_relation_'
	 */
	public function setRelationTableName($value)
	{
		$this->_options['relation_table'] = $value;
	}
	
	/**
	 * @return string the table name for object relations.
	 */
	public function getRelationTableName()
	{
		return $this->_options['relation_table'];
	}
	
	/**
	 * @param boolean whether to split relation table, default is true.
	 */
	public function setSplitRelationTable($value)
	{
		$this->_options['split_relation_table'] = TPropertyValue::ensureBoolean($value);
	}
	
	/**
	 * @string boolean true will split relation table.
	 */
	public function getSplitRelationTable()
	{
		return $this->_options['split_relation_table'];
	}
	
	/**
	 * @param boolean enable or disable auto flush at the end of script, default
	 * is false.
	 */
	public function setAutoFlush($value)
	{
		$this->_options['auto_flush'] = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return boolean whether to auto flush at the end of script.
	 */
	public function getAutoFlush()
	{
		return $this->_options['auto_flush'];
	}
	
	/**
	 * @param boolean enable or disable auto flush before find(), default is
	 * true.
	 */
	public function setFlushBeforeFind($value)
	{
		$this->_options['flush_before_find'] = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return boolean whether to auto flush before find()
	 */
	public function getFlushBeforeFind()
	{
		return $this->_options['flush_before_find'];
	}
	
	/**
	 * @param boolean enable or disable auto compile, default is true.
	 */
	public function setAutoCompile($value)
	{
		$this->_options['auto_compile'] = TPropertyValue::ensureBoolean($value);
	}
	
	/**
	 * @return boolean whether to auto compile class files.
	 */
	public function getAutoCompile()
	{
		return $this->_options['auto_compile'];
	}
	
	/**
	 * @param string default oid column name,  default is 'eoid'.
	 */
	public function setDefaultOidColumn($value)
	{
		$this->_options['default_oid_column'] = $value;
	}
	
	/**
	 * @return string default oid column name.
	 */
	public function getDefaultOidColumn($value)
	{
		return $this->_options['default_oid_column'];
	}
}

?>