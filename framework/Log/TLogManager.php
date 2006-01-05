<?php

abstract class TLogManager extends TModule
{
	protected $defaultSeverity = 'INFO | DEBUG | NOTICE | WARNING | ERROR | FATAL';
	
	protected $config;

	public function init($xml)
	{
		if(!is_null($this->config))
			$this->initLogger($this->loadConfigFromFile($this->config));
		else
			$this->initLogger($xml);
	}

	protected function loadConfigFromFile($file)
	{
		$xml = new TXmlDocument();
		$xml->loadFromFile($file);
		return $xml->getElementByTagName('loggers');
	}

	protected function initLogger($xml)
	{
		TEzcLoggerLoader::using('ezcLog');
		TEzcLoggerLoader::using('ezcLogMap');
		TEzcLoggerLoader::using('ezcLogContext');
		TEzcLoggerLoader::using('ezcLogFilter');

		$log = ezcLog::getInstance();
		foreach($xml->getElementsByTagName('logger') as $logger)
		{
			$logWriter = $this->getLogWriter($logger);
			$filters = $logger->getElementsByTagName('filter');
			foreach($filters as $filter)
			{
				$logFilter = new ezcLogFilter();
				$Severity = $filter->getAttribute('severity');
				$logFilter->severity = $this->getFilterSeverity($Severity);
				$map = $filter->getAttribute('disabled') ? 'unmap' : 'map';
				$log->$map($logFilter, $logWriter);
			}

			if($filters->Length < 1)
			{
				$logFilter = new ezcLogFilter();
				$logFilter->severity = $this->getFilterSeverity();
				$log->map($logFilter, $logWriter);
			}
		}
	}

	protected function getLogWriter($xml)
	{
		switch($xml->getAttribute('destination'))
		{
			case 'file' : 
				return TEzcLoggerUnixFileWriterFactory::create($xml);
			default : 
				throw new TException('invalid_log_destination');
		}
	}

	protected function getFilterSeverity($string='')
	{
		if(empty($string))
			$string = $this->defaultSeverity;
		$serverities = explode("|", $string);
		$mask = 0;
		foreach($serverities as $Severity)
			$mask = $mask | $this->getSeverity($Severity);			
		return $mask;
	}

	private function getSeverity($string)
	{
		switch(strtolower(trim($string)))
		{
            case 'debug': return ezcLog::DEBUG;
			case 'success audit' : return ezcLog::SUCCESS_AUDIT;
            case 'failed audit' : return ezcLog::FAILED_AUDIT;
			case 'info' : return ezcLog::INFO;
			case 'notice' : return ezcLog::NOTICE;
			case 'warn' : return ezcLog::WARNING;
			case 'error' : return ezcLog::ERROR;
			case 'fatal' : return ezcLog::FATAL;
		}
		return 0;
	}
}


/**
 * ${classname}
 *
 * ${description}
 *
 * @author Wei Zhuo<weizhuo[at]gmail[dot]com>
 * @version $Revision: 1.66 $  $Date: ${DATE} ${TIME} $
 * @package ${package}
 */
class TEzcLoggerUnixFileWriterFactory
{
	public static function create($xml)
	{
		TEzcLoggerLoader::using('ezcLogWriter');
		TEzcLoggerLoader::using('ezcLogWriterFile');
		TEzcLoggerLoader::using('ezcLogWriterUnixFile');
		
		$path = $xml->getAttribute('directory');
		$dir = Prado::getPathOfNamespace($path);
		$file = $xml->getAttribute('filename');
		if(empty($file)) $file = 'prado.log';
		return new ezcLogWriterUnixFile($dir, $file);
	}
}

/**
 * ${classname}
 *
 * ${description}
 *
 * @author Wei Zhuo<weizhuo[at]gmail[dot]com>
 * @version $Revision: 1.66 $  $Date: ${DATE} ${TIME} $
 * @package ${package}
 */
class TEzcLoggerLoader
{
	public static function using($class)
	{
		if(class_exists($class, false)) return;
		static $classes;
		$base = dirname(__FILE__);
		if(is_null($classes))
			$classes = include($base.'/EventLog/log_autoload.php');
		require_once($base.'/'.$classes[$class]);
	}
}

?>