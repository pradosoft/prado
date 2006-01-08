<?php
/*
<module id="log" class="System.Log.LogRouter" ConfigFile="xxx">
	<route class="TFileLogRoute" SizeLimit="111" Levels="xxx" Categories="xxx" />
</module>
*/
class TLogRouter extends TModule
{
	const CONFIG_FILE_EXT='.xml';
	private $_routes=array();

	public function init($config)
	{
		foreach($config->getElementsByName('route') as $routeConfig)
		{
			$class=$routeConfig->removeAttribute('class');
			$route=Prado::createComponent($class);
			foreach($routeConfig->getAttributes() as $name=>$value)
				$route->setSubproperty($name,$value);
		}

		$this->getApplication()->attachEventHandler('EndRequest',array($this,'collectLogs'));
	}

	public function getConfigFile()
	{
		return $this->_configFile;
	}

	public function setConfigFile($value)
	{
		if(($this->_configFile=Prado::getPathOfNamespace($value,self::LOG_FILE_EXT))===null)
			throw new TConfigurationException('logrouter_configfile_invalid',$value);
	}

	public function collectLogs($param)
	{
		foreach($this->_routes as $route)
			$route->collectLogs();
	}
}


abstract class TLogRoute extends TComponent
{
	private static $_levelMap=array(
		'error'=>TLogger::ERROR,
		'debug'=>TLogger::DEBUG,
		'info'=>TLogger::INFO,
		'notice'=>TLogger::NOTICE,
		'warning'=>TLogger::WARNING,
		'error'=>TLogger::ERROR,
		'alert'=>TLogger::ALERT,
		'fatal'=>TLogger::FATAL
	);
	private $_levels=null;
	private $_categories=null;

	public function getLevels()
	{
		return $this->_levels;
	}

	public function setLevels($levels)
	{
		$this->_levels=null;
		$levels=strtolower($levels);
		foreach(explode(',',$levels) as $level)
		{
			$level=trim($level);
			if(isset(self::$_levelMap[$level]))
				$this->_levels|=self::$_levelMap[$level];
		}
		$this->_levels=$levels;
	}

	public function getCategories()
	{
		return $this->_categories;
	}

	public function setCategories($categories)
	{
		$this->_categories=null;
		foreach(explode(',',$categories) as $category)
		{
			if(($category=trim($category))!=='')
				$this->_categories[]=$category;
		}
	}

	public function collectLogs()
	{
		$logs=Prado::getLogger()->getLogs($this->getLevels(),$this->getCategories());
		$this->processLogs($logs);
	}

	protected function processLogs($logs);
}

class TFileLogRoute extends TLogRoute
{
	const LOG_FILE_EXT='.log';
	private $_file;

	public function getLogFile()
	{
		return $this->_file;
	}

	public function setLogFile($value)
	{
		if(($this->_file=Prado::getPathOfNamespace($value,self::LOG_FILE_EXT))===null)
			throw new TConfigurationException('filelogroute_logfile_invalid',$value);
	}

	protected function processLogs($logs)
	{
	}
}


?>