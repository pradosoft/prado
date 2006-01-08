<?php

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
		$this->loadConfig($config);
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
	private static $_levelNames=array(
		TLogger::ERROR=>'Error',
		TLogger::DEBUG=>'Debug',
		TLogger::INFO=>'Info',
		TLogger::NOTICE=>'Notice',
		TLogger::WARNING=>'Warning',
		TLogger::ERROR=>'Error',
		TLogger::ALERT=>'Alert',
		TLogger::FATAL=>'Fatal'
	);
	private static $_levelValues=array(
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
			if(isset(self::$_levelValues[$level]))
				$this->_levels|=self::$_levelValues[$level];
		}
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

	protected function getLevelName($level)
	{
		return isset(self::$_levelNames[$level])?self::$_levelNames[$level]:'Unknown';
	}

	protected function formatLogMessage($message,$level,$category,$time)
	{
		return @date('M d H:i:s',$time).' ['.$this->getLevelName($level).'] ['.$category.'] '.$message."\n";
	}

	public function collectLogs()
	{
		$logs=Prado::getLogger()->getLogs($this->getLevels(),$this->getCategories());
		$this->processLogs($logs);
	}

	abstract protected function processLogs($logs);
}

class TFileLogRoute extends TLogRoute
{
	private $_maxFileSize=1024; // in KB
	private $_maxLogFiles=2;
	private $_logPath=null;
	private $_logFile='prado.log';

	public function init($config)
	{
		if($this->_logPath===null)
			$this->_logPath=$this->getApplication()->getRuntimePath();
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

	public function getLogFile()
	{
		return $this->_logFile;
	}

	public function setLogFile($value)
	{
		$this->_logFile=$value;
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
		$logFile=$this->_logPath.'/'.$this->_logFile;
		if(@filesize($logFile)>$this->_maxFileSize*1024)
			$this->rotateFiles();
		foreach($logs as $log)
			error_log($this->formatLogMessage($log[0],$log[1],$log[2],$log[3]),3,$logFile);
	}

	protected function rotateFiles()
	{
		$file=$this->_logPath.'/'.$this->_logFile;
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

class TEmailLogRoute extends TLogRoute
{
	const EMAIL_PATTERN='/^([0-9a-zA-Z]+[-._+&])*[0-9a-zA-Z]+@([-0-9a-zA-Z]+[.])+[a-zA-Z]{2,6}$/';
	const DEFAULT_SUBJECT='Prado Application Log';
	private $_emails=array();
	private $_subject='';
	private $_from='';

	protected function processLogs($logs)
	{
		$message='';
		$headers=($this->_from==='') ? '' : "From:{$this->_from}\r\n";
		$subject=$this->_subject===''?self::DEFAULT_SUBJECT:$this->_subject;
		foreach($logs as $log)
			$message.=$this->formatLogMessage($log[0],$log[1],$log[2],$log[3]);
		$message=wordwrap($message,70);
		foreach($this->_emails as $email)
			mail($email,$subject,$message,$headers);

	}

	public function getEmails()
	{
		return $this->_emails;
	}

	public function setEmails($emails)
	{
		$this->_emails=array();
		foreach(explode(',',$emails) as $email)
		{
			$email=trim($email);
			if(preg_match(self::EMAIL_PATTERN,$email))
				$this->_emails[]=$email;
		}
	}

	public function getSubject()
	{
		return $this->_subject;
	}

	public function setSubject($value)
	{
		$this->_subject=$value;
	}

	public function getFrom()
	{
		return $this->_from;
	}

	public function setFrom($value)
	{
		$this->_from=$value;
	}
}

?>