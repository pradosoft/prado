<?php
/*
<module id="log" class="System.Log.LogRouter">
	<route class="TFileLogRoute" LogPath="xxx.yyy.zzz" MaxLogFiles="5" MaxFileSize="1024" Categories="System.Web" Levels="Error" />
</module>
*/
class TLogRouter extends TModule
{
	const CONFIG_FILE_EXT='.xml';
	private $_routes=array();
	private $_configFile=null;

	public function init($config)
	{
		if($this->_configFile!==null)
		{
 			if(is_file($this->_configFile))
 			{
				$dom=new TXmlDocument;
				$dom->loadFromFile($this->_configFile);
				$this->loadConfig($dom);
			}
			else
				throw new TConfigurationException('logrouter_configfile_invalid',$this->_configFile);
		}
		$this->loadConfig($xml);
		$this->getApplication()->attachEventHandler('EndRequest',array($this,'collectLogs'));
	}

	private function loadConfig($xml)
	{
		foreach($xml->getElementsByTagName('route') as $routeConfig)
		{
			$properties=$routeConfig->getAttributes();
			if(($class=$properties->remove('class'))===null)
				throw new TConfigurationException('logrouter_routeclass_required');
			$route=Prado::createComponent($class);
			if(!($route instanceof TLogRoute))
				throw new TConfigurationException('logrouter_routetype_required');
			foreach($properties as $name=>$value)
				$route->setSubproperty($name,$value);
			$this->_routes[]=$route;
			$route->init($routeConfig);
		}
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

	public function init($config)
	{
	}

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
	private $_maxFileSize=1024; // in KB
	private $_maxLogFiles=5;
	private $_logPath=null;
	private $_fileName='prado.log';
	private $_levelMap=array(
		TLogger::ERROR=>'Error',
		TLogger::DEBUG=>'Debug',
		TLogger::INFO=>'Info',
		TLogger::NOTICE=>'Notice',
		TLogger::WARNING=>'Warning',
		TLogger::ERROR=>'Error',
		TLogger::ALERT=>'Alert',
		TLogger::FATAL=>'Fatal'
	);

	public function init()
	{
		if($this->_logPath===null)
			throw new TConfigurationException('filelogroute_logfile_required');
	}

	public function getLogPath()
	{
		return $this->_logPath;
	}

	public function setLogPath($value)
	{
		if(($this->_logPath=Prado::getPathOfNamespace($value))===null)
			throw new TConfigurationException('filelogroute_logpath_invalid',$value);
	}

	public function getFileName()
	{
		return $this->_fileName;
	}

	public function setFileName($value)
	{
		$this->_fileName=$value;
	}

	public function getMaxFileSize()
	{
		return $this->_maxFileSize;
	}

	public function setMaxFileSize($value)
	{
		$this->_maxFileSize=TPropertyValue::ensureInteger($value);
		if($this->_maxFileSize<0)
			throw new TInvalidDataValueException('filelogroute_maxfilesize_invalid');
	}

	public function getMaxLogFiles()
	{
		return $this->_maxLogFiles;
	}

	public function setMaxLogFiles($value)
	{
		$this->_maxLogFiles=TPropertyValue::ensureInteger($value);
		if($this->_maxLogFiles<1)
			throw new TInvalidDataValueException('filelogroute_maxlogfiles_invalid');
	}

	protected function processLogs($logs)
	{
		$str='';
		foreach($logs as $log)
			$str.=$this->formatLogMessage($log[0],$log[1],$log[2],$log[3]);
		$logFile=$this->_logPath.'/'.$this->_fileName;
		if(@filesize($logFile)>$this->_maxFileSize*1024)
			$this->rotateFiles();
		$fw=fopen($logFile,'a');
		fwrite($fw,$str);
		fclose($fw);
	}

	protected function formatLogMessage($message,$level,$category,$time)
	{
		return @date('M d H:i:s',$time).' ['.$this->getLevelName($level).'] ['.$category.'] '.$message."\n";
	}

	protected function getLevelName($level)
	{
		return isset(self::$_levelMap[$level])?self::$_levelMap[$level]:'Unknown';
	}

	private function rotateFiles()
	{
		$file=$this->_logPath.'/'.$this->_fileName;
		for($i=$this->_maxLogFiles;$i>0;--$i)
		{
			$rotateFile=$file.'.'.$i;
			if(is_file($rotateFile))
			{
				if($i===$this->_maxLogFiles)
					unlink($rotateFile);
				else
					rename($rotateFile,$file.'.'.($i+1));
			}
		}
		if(is_file($file))
			rename($file,$file.'.1');
	}
}


?>