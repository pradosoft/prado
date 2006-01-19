<?php
/**
 * TLogRouter, TLogRoute, TFileLogRoute, TEmailLogRoute class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Log
 */

/**
 * TLogRouter class.
 *
 * TLogRouter manages routes that record log messages in different media different ways.
 * For example, a file log route {@link TFileLogRoute} records log messages
 * in log files. An email log route {@link TEmailLogRoute} sends log messages
 * to email addresses.
 *
 * Log routes may be configured in application or page folder configuration files
 * or an external configuration file specified by {@link setConfigFile ConfigFile}.
 * The format is as follows,
 * <code>
 *   &lt;route class="TFileLogRoute" Categories="System.Web.UI" Levels="Warning" /&gt;
 *   &lt;route class="TEmailLogRoute" Categories="Application" Levels="Fatal" Emails="admin@pradosoft.com" /&gt;
 * </code>
 * You can specify multiple routes with different filtering conditions and different
 * targets, even if the routes are of the same type.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Log
 * @since 3.0
 */
class TLogRouter extends TModule
{
	/**
	 * File extension of external configuration file
	 */
	const CONFIG_FILE_EXT='.xml';
	/**
	 * @var array list of routes available
	 */
	private $_routes=array();
	/**
	 * @var string external configuration file
	 */
	private $_configFile=null;

	/**
	 * Initializes this module.
	 * This method is required by the IModule interface.
	 * @param TXmlElement configuration for this module, can be null
	 * @throws TConfigurationException if {@link getConfigFile ConfigFile} is invalid.
	 */
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
		//$this->getApplication()->attachEventHandler('Error',array($this,'collectLogs'));
		$this->getApplication()->attachEventHandler('EndRequest',array($this,'collectLogs'));
	}

	/**
	 * Loads configuration from an XML element
	 * @param TXmlElement configuration node
	 * @throws TConfigurationException if log route class or type is not specified
	 */
	private function loadConfig($xml)
	{
		foreach($xml->getElementsByTagName('route') as $routeConfig)
		{
			$properties=$routeConfig->getAttributes();
			if(($class=$properties->remove('class'))===null)
				throw new TConfigurationException('logrouter_routeclass_required');
			$route=Prado::createComponent($class);
			if(!($route instanceof TLogRoute))
				throw new TConfigurationException('logrouter_routetype_invalid');
			foreach($properties as $name=>$value)
				$route->setSubproperty($name,$value);
			$this->_routes[]=$route;
			$route->init($routeConfig);
		}
	}

	/**
	 * @return string external configuration file. Defaults to null.
	 */
	public function getConfigFile()
	{
		return $this->_configFile;
	}

	/**
	 * @param string external configuration file in namespace format. The file
	 * must be suffixed with '.xml'.
	 * @throws TInvalidDataValueException if the file is invalid.
	 */
	public function setConfigFile($value)
	{
		if(($this->_configFile=Prado::getPathOfNamespace($value,self::LOG_FILE_EXT))===null)
			throw new TConfigurationException('logrouter_configfile_invalid',$value);
	}

	/**
	 * Collects log messages from a logger.
	 * This method is an event handler to application's EndRequest event.
	 * @param mixed event parameter
	 */
	public function collectLogs($param)
	{
		$logger=Prado::getLogger();
		foreach($this->_routes as $route)
			$route->collectLogs($logger);
	}
}

/**
 * TLogRoute class.
 *
 * TLogRoute is the base class for all log route classes.
 * A log route object retrieves log messages from a logger and sends it
 * somewhere, such as files, emails.
 * The messages being retrieved may be filtered first before being sent
 * to the destination. The filters include log level filter and log category filter.
 *
 * To specify level filter, set {@link setLevels Levels} property,
 * which takes a string of comma-separated desired level names (e.g. 'Error, Debug').
 * To specify category filter, set {@link setCategories Categories} property,
 * which takes a string of comma-separated desired category names (e.g. 'System.Web, System.IO').
 *
 * Level filter and category filter are combinational, i.e., only messages
 * satisfying both filter conditions will they be returned.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Log
 * @since 3.0
 */
abstract class TLogRoute extends TComponent
{
	/**
	 * @var array lookup table for level names
	 */
	protected static $_levelNames=array(
		TLogger::DEBUG=>'Debug',
		TLogger::INFO=>'Info',
		TLogger::NOTICE=>'Notice',
		TLogger::WARNING=>'Warning',
		TLogger::ALERT=>'Alert',
		TLogger::FATAL=>'Fatal'
	);
	/**
	 * @var array lookup table for level values
	 */
	protected static $_levelValues=array(
		'debug'=>TLogger::DEBUG,
		'info'=>TLogger::INFO,
		'notice'=>TLogger::NOTICE,
		'warning'=>TLogger::WARNING,
		'alert'=>TLogger::ALERT,
		'fatal'=>TLogger::FATAL
	);
	/**
	 * @var integer log level filter (bits)
	 */
	private $_levels=null;
	/**
	 * @var array log category filter
	 */
	private $_categories=null;

	/**
	 * Initializes the route.
	 * @param TXmlElement configurations specified in {@link TLogRouter}.
	 */
	public function init($config)
	{
	}

	/**
	 * @return integer log level filter
	 */
	public function getLevels()
	{
		return $this->_levels;
	}

	/**
	 * @param integer|string integer log level filter (in bits). If the value is
	 * a string, it is assumed to be comma-separated level names. Valid level names
	 * include 'Error', 'Debug', 'Info', 'Notice', 'Warning', 'Error', 'Alert' and 'Fatal'.
	 */
	public function setLevels($levels)
	{
		if(is_integer($levels))
			$this->_levels=$levels;
		else
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
	}

	/**
	 * @return array list of categories to be looked for
	 */
	public function getCategories()
	{
		return $this->_categories;
	}

	/**
	 * @param array|string list of categories to be looked for. If the value is a string,
	 * it is assumed to be comma-separated category names.
	 */
	public function setCategories($categories)
	{
		if(is_array($categories))
			$this->_categories=$categories;
		else
		{
			$this->_categories=null;
			foreach(explode(',',$categories) as $category)
			{
				if(($category=trim($category))!=='')
					$this->_categories[]=$category;
			}
		}
	}

	/**
	 * @param integer level value
	 * @return string level name
	 */
	protected function getLevelName($level)
	{
		return isset(self::$_levelNames[$level])?self::$_levelNames[$level]:'Unknown';
	}

	/**
	 * @param string level name
	 * @return integer level value
	 */
	protected function getLevelValue($level)
	{
		return isset(self::$_levelValues[$level])?self::$_levelValues[$level]:0;
	}

	/**
	 * Formats a log message given different fields.
	 * @param string message content
	 * @param integer message level
	 * @param string message category
	 * @param integer timestamp
	 * @return string formatted message
	 */
	protected function formatLogMessage($message,$level,$category,$time)
	{
		return @date('M d H:i:s',$time).' ['.$this->getLevelName($level).'] ['.$category.'] '.$message."\n";
	}

	/**
	 * Retrieves log messages from logger to log route specific destination.
	 * @param TLogger logger instance
	 */
	public function collectLogs(TLogger $logger)
	{
		$logs=$logger->getLogs($this->getLevels(),$this->getCategories());
		if(!empty($logs))
			$this->processLogs($logs);
	}

	/**
	 * Processes log messages and sends them to specific destination.
	 * Derived child classes must implement this method.
	 * @param array list of messages. Each array element is a (formatted) message string.
	 */
	abstract protected function processLogs($logs);
}

/**
 * TFileLogRoute class.
 *
 * TFileLogRoute records log messages in files.
 * The log files are stored under {@link setLogPath LogPath} and the file name
 * is specified by {@link setLogFile LogFile}. If the size of the log file is
 * greater than {@link setMaxFileSize MaxFileSize} (in kilo-bytes), a rotation
 * is performed, which renames the current log file by suffixing the file name
 * with '.1'. All existing log files are moved backwards one place, i.e., '.2'
 * to '.3', '.1' to '.2'. The property {@link setMaxLogFiles MaxLogFiles}
 * specifies how many files to be kept.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Log
 * @since 3.0
 */
class TFileLogRoute extends TLogRoute
{
	/**
	 * @var integer maximum log file size
	 */
	private $_maxFileSize=512; // in KB
	/**
	 * @var integer number of log files used for rotation
	 */
	private $_maxLogFiles=2;
	/**
	 * @var string directory storing log files
	 */
	private $_logPath=null;
	/**
	 * @var string log file name
	 */
	private $_logFile='prado.log';

	/**
	 * @return string directory storing log files. Defaults to application runtime path.
	 */
	public function getLogPath()
	{
		if($this->_logPath===null)
			$this->_logPath=$this->getApplication()->getRuntimePath();
		return $this->_logPath;
	}

	/**
	 * @param string directory (in namespace format) storing log files.
	 * @throws TConfigurationException if log path is invalid
	 */
	public function setLogPath($value)
	{
		if(($this->_logPath=Prado::getPathOfNamespace($value))===null || !is_dir($this->_logPath) || !is_writable($this->_logPath))
			throw new TConfigurationException('filelogroute_logpath_invalid',$value);
	}

	/**
	 * @return string log file name. Defaults to 'prado.log'.
	 */
	public function getLogFile()
	{
		return $this->_logFile;
	}

	/**
	 * @param string log file name
	 */
	public function setLogFile($value)
	{
		$this->_logFile=$value;
	}

	/**
	 * @return integer maximum log file size in kilo-bytes (KB). Defaults to 1024 (1MB).
	 */
	public function getMaxFileSize()
	{
		return $this->_maxFileSize;
	}

	/**
	 * @param integer maximum log file size in kilo-bytes (KB).
	 * @throws TInvalidDataValueException if the value is smaller than 1.
	 */
	public function setMaxFileSize($value)
	{
		$this->_maxFileSize=TPropertyValue::ensureInteger($value);
		if($this->_maxFileSize<=0)
			throw new TInvalidDataValueException('filelogroute_maxfilesize_invalid');
	}

	/**
	 * @return integer number of files used for rotation. Defaults to 2.
	 */
	public function getMaxLogFiles()
	{
		return $this->_maxLogFiles;
	}

	/**
	 * @param integer number of files used for rotation.
	 */
	public function setMaxLogFiles($value)
	{
		$this->_maxLogFiles=TPropertyValue::ensureInteger($value);
		if($this->_maxLogFiles<1)
			throw new TInvalidDataValueException('filelogroute_maxlogfiles_invalid');
	}

	/**
	 * Saves log messages in files.
	 * @param array list of log messages
	 */
	protected function processLogs($logs)
	{
		$logFile=$this->getLogPath().'/'.$this->getLogFile();
		if(@filesize($logFile)>$this->_maxFileSize*1024)
			$this->rotateFiles();
		foreach($logs as $log)
			error_log($this->formatLogMessage($log[0],$log[1],$log[2],$log[3]),3,$logFile);
	}

	/**
	 * Rotates log files.
	 */
	protected function rotateFiles()
	{
		$file=$this->getLogPath().'/'.$this->getLogFile();
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

/**
 * TEmailLogRoute class.
 *
 * TEmailLogRoute sends selected log messages to email addresses.
 * The target email addresses may be specified via {@link setEmails Emails} property.
 * Optionally, you may set the email {@link setSubject Subject} and the
 * {@link setSentFrom SentFrom} address.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Log
 * @since 3.0
 */
class TEmailLogRoute extends TLogRoute
{
	/**
	 * Regex pattern for email address.
	 */
	const EMAIL_PATTERN='/^([0-9a-zA-Z]+[-._+&])*[0-9a-zA-Z]+@([-0-9a-zA-Z]+[.])+[a-zA-Z]{2,6}$/';
	/**
	 * Default email subject.
	 */
	const DEFAULT_SUBJECT='Prado Application Log';
	/**
	 * @var array list of destination email addresses.
	 */
	private $_emails=array();
	/**
	 * @var string email subject
	 */
	private $_subject='';
	/**
	 * @var string email sent from address
	 */
	private $_from='';

	/**
	 * Initializes the route.
	 * @param TXmlElement configurations specified in {@link TLogRouter}.
	 * @throws TConfigurationException if {@link getSentFrom SentFrom} is empty and
	 * 'sendmail_from' in php.ini is also empty.
	 */
	public function init($config)
	{
		if($this->_from==='')
			$this->_from=ini_get('sendmail_from');
		if($this->_from==='')
			throw new TConfigurationException('emaillogroute_sentfrom_required');
	}

	/**
	 * Sends log messages to specified email addresses.
	 * @param array list of log messages
	 */
	protected function processLogs($logs)
	{
		$message='';
		foreach($logs as $log)
			$message.=$this->formatLogMessage($log[0],$log[1],$log[2],$log[3]);
		$message=wordwrap($message,70);
		foreach($this->_emails as $email)
			mail($email,$this->getSubject(),$message,"From:{$this->_from}\r\n");

	}

	/**
	 * @return array list of destination email addresses
	 */
	public function getEmails()
	{
		return $this->_emails;
	}

	/**
	 * @return array|string list of destination email addresses. If the value is
	 * a string, it is assumed to be comma-separated email addresses.
	 */
	public function setEmails($emails)
	{
		if(is_array($emails))
			$this->_emails=$emails;
		else
		{
			$this->_emails=array();
			foreach(explode(',',$emails) as $email)
			{
				$email=trim($email);
				if(preg_match(self::EMAIL_PATTERN,$email))
					$this->_emails[]=$email;
			}
		}
	}

	/**
	 * @return string email subject. Defaults to TEmailLogRoute::DEFAULT_SUBJECT
	 */
	public function getSubject()
	{
		if($this->_subject===null)
			$this->_subject=self::DEFAULT_SUBJECT;
		return $this->_subject;
	}

	/**
	 * @param string email subject.
	 */
	public function setSubject($value)
	{
		$this->_subject=$value;
	}

	/**
	 * @return string send from address of the email
	 */
	public function getSentFrom()
	{
		return $this->_from;
	}

	/**
	 * @param string send from address of the email
	 */
	public function setSentFrom($value)
	{
		$this->_from=$value;
	}
}

/**
 * TBrowserLogRoute class.
 *
 * TBrowserLogRoute prints selected log messages in the response.
 *
 * @author Xiang Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Revision: $  $Date: $
 * @package System.Log
 * @since 3.0
 */
class TBrowserLogRoute extends TLogRoute
{
	public function processLogs($logs)
	{
		if(empty($logs) || $this->getApplication()->getMode()==='Performance') return;
		$first = $logs[0][3]; $prev = $first; $total = 0; $delta = 0; $even = true;
		$response = $this->getApplication()->getResponse();
		$response->write($this->renderHeader());
		foreach($logs as $log)
		{
			$timing['total'] = $log[3] - $first;
			$timing['delta'] = $log[3]-$prev;
			$timing['even'] = !($even = !$even);
			$prev=$log[3];
			$response->write($this->renderMessage($log,$timing));
		}
		$response->write($this->renderFooter());
	}

	protected function renderHeader()
	{
		$category = is_array($this->getCategories()) ?
						implode(', ',$this->getCategories()) : '';
		$string = <<<EOD
<table cellspacing="0" cellpadding="0" border="0" width="100%">
	<tr>
		<th style="background-color: black; color:white;" colspan="10">
			<h3>Trace Information: $category</h3>
		</th>
	</tr><tr style="background-color: #ccc;">
		<th>Category</th><th>Message</th><th>From First(s)</th><th>From Last(s)</th>
	</tr>
EOD;
		return $string;
	}

	protected function renderMessage($log, $info)
	{
		$bgcolor = $info['even'] ? "#fff" : "#eee";
		$total = sprintf('%0.6f', $info['total']);
		$delta = sprintf('%0.6f', $info['delta']);
		$color = $this->getColorLevel($log[1]);
		$msg = preg_replace('/\(line[^\)]+\)$/','',$log[0]); //remove line number info
		$string = <<<EOD
	<tr style="background-color: {$bgcolor}; color: {$color}">
		<td>{$log[2]}</td><td>{$msg}</td><td>{$total}</td><td>{$delta}</td>
	</tr>
EOD;
		return $string;
	}

	protected function getColorLevel($level)
	{
		switch($level)
		{
			case TLogger::DEBUG: return 'green';
			case TLogger::INFO: return 'black';
			case TLogger::NOTICE: return 'blue';
			case TLogger::WARNING: return '#f63';
			case TLogger::ALERT: return '#c00';
			case TLogger::FATAL: return 'red';
		}
	}

	protected function renderFooter()
	{
		$string = "<tr><td colspan=\"10\" style=\"text-align:center; border-top: 1px solid #ccc; padding:0.2em;\">";
		foreach(self::$_levelValues as $name => $level)
		{
			$string .= "<span style=\"color:".$this->getColorLevel($level);
			$string .= ";margin: 0.5em;\">".strtoupper($name)."</span>";
		}
		$string .= "</td></tr></table>";
		return $string;
	}
}
?>