<?php
/**
 * TLogRouter, TLogRoute, TFileLogRoute, TEmailLogRoute class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Brad Anderson <javalizard@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2010 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Util
 */

Prado::using('System.Data.TDbConnection');

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
 *   <route class="TFileLogRoute" Categories="System.Web.UI" Levels="Warning" Roles="developer,administrator,other" Active="false" />
 *   <route class="TEmailLogRoute" Categories="Application" Levels="Fatal" Emails="admin@pradosoft.com" />
 * </code>
 * PHP configuration style:
 * <code>
 * 
 * </code>
 * You can specify multiple routes with different filtering conditions and different
 * targets, even if the routes are of the same type.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Carl G. Mathisen <carlgmathisen@gmail.com>
 * @version $Id$
 * @package System.Util
 * @since 3.0
 */
class TLogRouter extends TModule
{
	/**
	 * @var array list of routes available
	 */
	private $_routes=array();
	/**
	 * @var array list of routes needed to be logged before the page flush
	 */
	private $_preroutes=array();
	/**
	 * @var string external configuration file
	 */
	private $_configFile=null;
	/**
	 * @var boolean whether to do any routes
	 */
	private $_active=true;
	
	
	
	/**
	 * Initializes this module.
	 * This method is required by the IModule interface.
	 * @param mixed configuration for this module, can be null
	 * @throws TConfigurationException if {@link getConfigFile ConfigFile} is invalid.
	 */
	public function init($config)
	{
		parent::init($config);
		
		if($this->_configFile!==null)
		{
 			if(is_file($this->_configFile))
 			{
				if($this->getApplication()->getConfigurationType()==TApplication::CONFIG_TYPE_PHP)
				{
					$phpConfig = include $this->_configFile;
					$this->loadConfig($phpConfig);
				}
				else
				{
					$dom=new TXmlDocument;
					$dom->loadFromFile($this->_configFile);
					$this->loadConfig($dom);
				}
			}
			else
				throw new TConfigurationException('logrouter_configfile_invalid',$this->_configFile);
		}
		$this->loadConfig($config);

		// This is needed for FirePhp because it outputs headers
		$this->getApplication()->attachEventHandler('onPreFlushOutput',array($this,'collectLogsPreFlush'));
		$this->getApplication()->attachEventHandler('OnEndRequest',array($this,'collectLogs'));
	}

	/**
	 * Loads configuration from an XML element or PHP array
	 * @param mixed configuration node
	 * @throws TConfigurationException if log route class or type is not specified
	 */
	private function loadConfig($config)
	{
		if(is_array($config))
		{
			if(isset($config['routes']) && is_array($config['routes']))
			{
				foreach($config['routes'] as $route)
				{
					$properties = isset($route['properties'])?$route['properties']:array();
					if(!isset($route['class']))
						throw new TConfigurationException('logrouter_routeclass_required');
					if(isset($properties['disabled']) && $properties['disabled'])
						continue;
					$route=Prado::createComponent($route['class']);
					if(!($route instanceof TLogRoute))
						throw new TConfigurationException('logrouter_routetype_invalid');
					
					$this->_routes[]=$route;
					if($route instanceof IHeaderRoute)
						$this->_preroutes[]=$route;
					
					try {
						foreach($properties as $name=>$value)
							$route->setSubproperty($name,$value);
						$route->init($route);
					} catch(Exception $e) {
						$route->InitError = $e;
					}
				}
			}
		}
		else
		{
			foreach($config->getElementsByTagName('route') as $routeConfig)
			{
				$properties=$routeConfig->getAttributes();
				if(($disabled=$properties->remove('disabled'))!==null)
					continue;
				if(($class=$properties->remove('class'))===null)
					throw new TConfigurationException('logrouter_routeclass_required');
				$route=Prado::createComponent($class);
				if(!($route instanceof TLogRoute))
					throw new TConfigurationException('logrouter_routetype_invalid');
					
				$this->_routes[]=$route;
				if($route instanceof IHeaderRoute)
					$this->_preroutes[]=$route;
				
				try {
					foreach($properties as $name=>$value)
						$route->setSubproperty($name,$value);
					$route->init($routeConfig);
				} catch(Exception $e) {
					$route->InitError = $e;
				}
			}
		}
	}
	
	/**
	 * This returns the installed routes
	 * @return array of TLogRoute
	 */
	public function getRoutes() { return $this->_routes; }

	/**
	 * Adds a TLogRoute instance to the log router.  If a log route implements {@link IHeaderRoute}
	 * then it will get its log route data just before the page is written (b/c it needs that for the headers)
	 * 
	 * @param TLogRoute $route 
	 * @throws TInvalidDataTypeException if the route object is invalid
	 */
	public function addRoute($route)
	{
		if(!($route instanceof TLogRoute))
			throw new TInvalidDataTypeException('logrouter_routetype_invalid');
		$this->_routes[]=$route;
		if($route instanceof IHeaderRoute)
			$this->_preroutes[]=$route;
		$route->init($this);
	}

	/**
	 * @return string external configuration file. Defaults to null.
	 */
	public function getConfigFile()
	{
		return $this->_configFile;
	}

	/**
	 * @return boolean whether the TLogRouter is active or not.
	 */
	public function getActive()
	{
		return $this->_active;
	}

	/**
	 * @param boolean tells the object whether it's active or not.
	 */
	public function setActive($v)
	{
		$this->_active = TPropertyValue::ensureBoolean($v);
	}

	/**
	 * @param string external configuration file in namespace format. The file
	 * must be suffixed with '.xml'.
	 * @throws TConfigurationException if the file is invalid.
	 */
	public function setConfigFile($value)
	{
		if(($this->_configFile=Prado::getPathOfNamespace($value,$this->getApplication()->getConfigurationFileExt()))===null)
			throw new TConfigurationException('logrouter_configfile_invalid',$value);
	}

	/**
	 * Collects log messages from a logger.
	 * This method is an event handler to the application's onPreFlush event.
	 * Only pre flush routes get this treatment.
	 * @param mixed event parameter
	 */
	public function collectLogsPreFlush($param) {
		if(!$this->_active) return;
		
		$logger=Prado::getLogger();
		foreach($this->_preroutes as $route)
			$route->collectLogs($logger);
	}

	/**
	 * Collects log messages from a logger.
	 * This method is an event handler to the application's EndRequest event.
	 * Only post flush routes get this treatment.
	 * @param mixed event parameter
	 */
	public function collectLogs($param)
	{
		if(!$this->_active) return;
		
		$logger=Prado::getLogger();
		foreach($this->_routes as $route)
			if(!in_array($route, $this->_preroutes))
				$route->collectLogs($logger);
	}
}


/**
 * IHeaderRoute interface.
 *
 * This is used for registering log routers that output to the header so it can be routed before the page flush.
 *
 * @author Brad Anderson <javalizard@gmail.com>
 * @version $Id$
 * @package System.Util
 * @since 3.0
 */ 

interface IHeaderRoute {
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
 * @version $Id$
 * @package System.Util
 * @since 3.0
 */
abstract class TLogRoute extends TApplicationComponent
{
	/**
	 * @var array lookup table for level names
	 */
	protected static $_levelNames=array(
		TLogger::DEBUG=>'Debug',
		TLogger::INFO=>'Info',
		TLogger::NOTICE=>'Notice',
		TLogger::WARNING=>'Warning',
		TLogger::ERROR=>'Error',
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
		'error'=>TLogger::ERROR,
		'alert'=>TLogger::ALERT,
		'fatal'=>TLogger::FATAL
	);
	/**
	 * @var string the id of the route
	 */
	private $_id=null;
	/**
	 * @var string the name of the route
	 */
	private $_name=null;
	/**
	 * @var integer log level filter (bits)
	 */
	private $_levels=null;
	/**
	 * @var array log category filter
	 */
	private $_categories=null;
	/**
	 * @var array log controls filter
	 */
	private $_controls=null;
	/**
	 * @var array role filter
	 */
	private $_roles=null;

	/**
	 * @var int|string the reference to the hit metadata.  This is a transient property per page hit.
	 */
	private $_metaid=null;
	/**
	 * @var int the user id of the hit.  This is a transient property per page hit.
	 */
	private $_userid=null;
	
	/**
	 * @var boolean whether this is an active route or not
	 */
	private $_active=true;
	/**
	 * $var Exception any problems on the loading of the module
	 */
	private $_error=null;
	/**
	 * Initializes the route.
	 * @param TXmlElement configurations specified in {@link TLogRouter}.
	 */
	public function init($config)
	{
		if(is_array($config)) {
			if(isset($config['id']))
				$this->_id = $config['id'];
			if(isset($config['name']))
				$this->Name = $config['name'];
			if(isset($config['active']))
				$this->Active = $config['active'];
			if(isset($config['roles']))
				$this->Roles = $config['roles'];
			if(isset($config['categories']))
				$this->Categories = $config['categories'];
			if(isset($config['levels']))
				$this->Levels = $config['levels'];
			if(isset($config['controls']))
				$this->Controls = $config['controls'];
		}
	}
	

	/**
	 * @return string the id of the route
	 */
	public function getId()
	{
		return $this->_id;
	}

	/**
	 * @param The id of the route.
	 */
	public function setId($id)
	{
		$this->_id = $id;
	}

	/**
	 * @return string the name of the route
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * @param The name of the route.
	 */
	public function setName($name)
	{
		$this->_name = $name;
	}

	/**
	 * @return boolean true if the route is active
	 */
	public function getActive()
	{
		return $this->_active;
	}

	/**
	 * @param boolean sets the object to active or not.
	 */
	public function setActive($v)
	{
		$this->_active = TPropertyValue::ensureBoolean($v);
	}

	/**
	 * @return Exception this returns any errors the log route has
	 */
	public function getInitError()
	{
		return $this->_error;
	}

	/**
	 * @param mixed this sets the errors that the log route may have
	 */
	public function setInitError($v)
	{
		$this->_error = $v;
	}

	/**
	 * @return string this returns the meta data id associated with the route
	 */
	public function getMetaId()
	{
		return $this->_metaid;
	}

	/**
	 * @param string this sets the meta data id associated with the route.
	 */
	public function setMetaId($v)
	{
		$this->_metaid = $v;
	}


	/**
	 * @return string this returns the user id associated with the route.
	 */
	public function getUserId()
	{
		return $this->_userid;
	}

	/**
	 * @return string this sets the user id associated with the route.
	 */
	public function setUserId($v)
	{
		$this->_userid = $v;
	}

	/**
	 * @return array log roles filter
	 */
	public function getRoles()
	{
		return $this->_roles;
	}

	/**
	 * @param array|string The roles that this log router is attached to.
	 */
	public function setRoles($roles)
	{
		if(is_array($roles))
			$this->_roles=$roles;
		else
		{
			$this->_roles=array();
			$roles=strtolower($roles);
			foreach(explode(',',$roles) as $role)
			{
				$role=trim($role);
				if(!in_array($role, $this->_roles))
					$this->_roles[] = $role;
			}
		}
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
	 * include 'Debug', 'Info', 'Notice', 'Warning', 'Error', 'Alert' and 'Fatal'.
	 */
	public function setLevels($levels)
	{
		if(is_integer($levels))
			$this->_levels=$levels;
		else
		{
			$this->_levels=null;
			if(is_string($levels))
				$levels = explode(',',strtolower($levels));
			
			foreach($levels as $level)
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
			$this->_categories=array();
			foreach(explode(',',$categories) as $category)
			{
				if(($category=trim($category))!=='')
					$this->_categories[]=$category;
			}
		}
	}

	/**
	 * @return array list of controls to be looked for
	 */
	public function getControls()
	{
		return $this->_controls;
	}

	/**
	 * @param array|string list of controls to be looked for. If the value is a string,
	 * it is assumed to be comma-separated control client ids.
	 */
	public function setControls($controls)
	{
		if(is_array($controls))
			$this->_controls=$controls;
		else
		{
			$this->_controls=array();
			foreach(explode(',',$controls) as $control)
			{
				if(($control=trim($control))!=='')
					$this->_controls[]=$control;
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
	protected function formatLogMessage($message,$level,$category,$time, $memory)
	{
		if(!$this->MetaId)
			$this->MetaId = $this->Request->UserHostAddress;
		return '[metaid: ' .$this->MetaId.'] ' . @date('M d H:i:s',$time).' [Memory: '.$memory.'] ['.$this->getLevelName($level).'] ['.$category.'] '.$message."\n";
	}

	/**
	 * Retrieves log messages from logger to log route specific destination.
	 * @param TLogger logger instance
	 */
	public function collectLogs(TLogger $logger)
	{
		// if not active or roles don't match, end function
		if(!$this->_active || ($this->_roles && !array_intersect($this->_roles, $this->User->Roles))) return;
		
		Prado::trace('Routing Logs: '.get_class($this) . '->id='.$this->id,'System.Util.TLogRouter');
		
		$logs=$logger->getLogs($this->getLevels(),$this->getCategories(),$this->getControls());
		if(!empty($logs))
			$this->processLogs($logs);
	}
	
	/**
	 *	@return string this is the xml representation of the route
	 */
	public function toXml() {
		$xml = '<route ' . $this->encodeId() . $this->encodeName() . $this->encodeClass() . $this->encodeLevels() . 
			$this->encodeCategories() . $this->encodeRoles() . $this->encodeControls() . '/>';
		return $xml;
	}
	
	/**
	 *	@return string this encodes the id of the route as an xml attribute
	 */
	protected function encodeId() {
		return 'id="'. $this->_id .'" ';
	}
	
	/**
	 *	@return string this encodes the name of the route as an xml attribute
	 */
	protected function encodeName() {
		$active = '';
		if(!$this->_active) $active = 'active="'. ($this->_active?'true':'false') .'" ';
		return 'name="'. $this->_name .'" ' . $active;
	}
	
	/**
	 *	@return string this encodes the class of the route as an xml attribute
	 */
	protected function encodeClass() {
		return 'class="'. get_class($this) .'" ';
	}
	
	/**
	 *	@return string this encodes the levels of the route as an xml attribute
	 */
	protected function encodeLevels() {
		if(!$this->_levels) return '';
		$levels = array();
		foreach(self::$_levelNames as $level => $name)
			if($level & $this->_levels)
				$levels[] = strtolower($name);
		return 'levels="'. implode(',', $levels) .'" ';
	}
	
	/**
	 *	@return string this encodes the categories of the route as an xml attribute
	 */
	protected function encodeCategories() {
		if(!$this->_categories) return '';
		return 'categories="'. implode(',', $this->_categories) .'" ';
	}
	
	/**
	 *	@return string this encodes the roles of the route as an xml attribute
	 */
	protected function encodeRoles() {
		if(!$this->_roles) return '';
		return 'roles="'. implode(',', $this->_roles) .'" ';
	}
	
	/**
	 *	@return string this encodes the controls of the route as an xml attribute
	 */
	protected function encodeControls() {
		if(!$this->_roles) return '';
		return 'controls="'. implode(',', $this->_controls) .'" ';
	}

	/**
	 * Processes log messages and sends them to specific destination.
	 * Derived child classes must implement this method.
	 * @param array list of messages.  Each array elements represents one message
	 * with the following structure:
	 * array(
	 *   [0] => message
	 *   [1] => level
	 *   [2] => category
	 *   [3] => timestamp
	 *   [4] => memory in bytes
	 *   [5] => control);
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
 * @version $Id$
 * @package System.Util
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
	 * @var string original directory set for the log files so it can be recreated
	 */
	private $_logPradoPath=null;
	/**
	 * @var string log file name
	 */
	private $_logFile='prado.log';

	/**
	 * Initializes the route.
	 * @param TXmlElement configurations specified in {@link TLogRouter}.
	 * @throws TConfigurationException if {@link getSentFrom SentFrom} is empty and
	 * 'sendmail_from' in php.ini is also empty.
	 */
	public function init($config)
	{
		parent::init($config);
		
		if(is_array($config)) {
			if(isset($config['logfile']))
				$this->LogFile = $config['logfile'];
			if(isset($config['logpath']))
				$this->LogPath = $config['logpath'];
			if(isset($config['maxfilesize']))
				$this->MaxFileSize = $config['maxfilesize'];
			if(isset($config['maxfilesize']))
				$this->MaxLogFiles = $config['maxlogfiles'];
		}
	}
	
	/**
	 *	@return string this encodes the TFileLogRoute as a string
	 */
	public function toXml() {
		$xml = '<route ' .$this->encodeId(). $this->encodeName().$this->encodeClass() . $this->encodeLevels() . 
			$this->encodeCategories() . $this->encodeControls() . $this->encodeRoles() . $this->encodeMaxFileSize(). 
			$this->encodeMaxLogFiles(). $this->encodeLogPath().$this->encodeLogFile().'/>';
		return $xml;
	}
	
	/**
	 *	@return string this encodes the maxfilesize of the route as an xml attribute
	 */
	protected function encodeMaxFileSize() {
		if(!$this->MaxFileSize) return '';
		return 'maxfilesize="'. addslashes($this->MaxFileSize) .'" ';
	}
	
	/**
	 *	@return string this encodes the maxlogfiles of the route as an xml attribute
	 */
	protected function encodeMaxLogFiles() {
		if(!$this->MaxFileSize) return '';
		return 'maxlogfiles="'. addslashes($this->MaxLogFiles) .'" ';
	}
	
	/**
	 *	@return string this encodes the logpath of the route as an xml attribute
	 */
	protected function encodeLogPath() {
		if(!$this->LogPath) return '';
		return 'logpath="'. addslashes($this->_logPradoPath) .'" ';
	}
	
	/**
	 *	@return string this encodes the logfile of the route as an xml attribute
	 */
	protected function encodeLogFile() {
		if(!$this->LogFile) return '';
		return 'logfile="'. addslashes($this->LogFile) .'" ';
	}
	
	
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
		$this->_logPradoPath = $value;
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
		$logFile=$this->getLogPath().DIRECTORY_SEPARATOR.$this->getLogFile();
		if(@filesize($logFile)>$this->_maxFileSize*1024)
			$this->rotateFiles();
		foreach($logs as $log)
			error_log($this->formatLogMessage($log[0],$log[1],$log[2],$log[3],$log[4]),3,$logFile);
	}

	/**
	 * Rotates log files.
	 */
	protected function rotateFiles()
	{
		$file=$this->getLogPath().DIRECTORY_SEPARATOR.$this->getLogFile();
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
 * @version $Id$
 * @package System.Util
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
		parent::init($config);
		
		if(is_array($config)) {
			if(isset($config['emails']))
				$this->Emails = $config['emails'];
			if(isset($config['subject']))
				$this->Subject = $config['subject'];
			if(isset($config['from']))
				$this->SentFrom = $config['from'];
		}
		
		if($this->_from==='')
			$this->_from=ini_get('sendmail_from');
		if($this->_from==='')
			throw new TConfigurationException('emaillogroute_sentfrom_required');
	}
	
	/**
	 *	@return string this encodes the TEmailLogRoute as an xml element
	 */
	public function toXml() {
		$xml = '<route ' .$this->encodeId(). $this->encodeName().$this->encodeClass() . $this->encodeLevels() . 
			$this->encodeCategories() . $this->encodeControls() . $this->encodeRoles() . $this->encodeEmails(). 
			$this->encodeSubject(). $this->encodeFrom().'/>';
		return $xml;
	}
	
	/**
	 *	@return string this encodes the emails of the route as an xml attribute
	 */
	protected function encodeEmails() {
		if(!$this->Emails) return '';
		return 'emails="'. addslashes(implode(',',$this->Emails)) .'" ';
	}
	
	/**
	 *	@return string this encodes the subject of the route as an xml attribute
	 */
	protected function encodeSubject() {
		if($this->Subject == self::DEFAULT_SUBJECT) return '';
		return 'subject="'. addslashes($this->Subject) .'" ';
	}
	
	/**
	 *	@return string this encodes the from email of the route as an xml attribute
	 */
	protected function encodeFrom() {
		return 'sentfrom="'. addslashes($this->SentFrom) .'" ';
	}
	
	
	/**
	 *	This sends a test email with a test log message
	 */
	public function sendTestEmail() {
		$this->processLogs(array(
				array('Test Message',TLogger::DEBUG,'System.Util.TEmailLogRoute',microtime(true),memory_get_usage())
			));
	}

	/**
	 * Sends log messages to specified email addresses.
	 * @param array list of log messages
	 */
	protected function processLogs($logs)
	{
		$message='';
		foreach($logs as $log)
			$message.=$this->formatLogMessage($log[0],$log[1],$log[2],$log[3],$log[4]);
		$message=wordwrap($message,70);
		$returnPath = ini_get('sendmail_path') ? "Return-Path:{$this->_from}\r\n" : '';
		foreach($this->_emails as $email)
			mail($email,$this->getSubject(),$message,"From:{$this->_from}\r\n{$returnPath}");

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
		$this->_subject=$value ? $value : null;
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
 * @version $Id$
 * @package System.Util
 * @since 3.0
 */
class TBrowserLogRoute extends TLogRoute
{
	/**
	 * @var string css class for indentifying the table structure in the dom tree
	 */
	private $_cssClass='log-route-browser';
	

	/**
	 * Sends log messages to the browser.
	 * This does quartile analysis on the logs to highlight the memory and time offenders
	 * @param array list of log messages
	 */
	public function processLogs($logs)
	{
		if(empty($logs) || $this->getApplication()->getMode()==='Performance') return;
		$first = $logs[0][3];
		$mem_first = $logs[0][4];
		$even = true;
		$use_interquartile_top_bottom = false;
		$response = $this->getApplication()->getResponse();
		
		$c = count($logs);
		for($i=0,$n=count($logs); $i<$n; $i++) {
			$logs[$i]['i'] = $i;
			if($i > 1 && $i < $n-1) {
				$logs[$i]['time_delta'] = $logs[$i+1][3] - $logs[$i][3];
				$logs[$i]['time_total'] = $logs[$i+1][3] - $first;
				$logs[$i]['mem_delta'] = $logs[$i+1][4] - $logs[$i][4];
				$logs[$i]['mem_total'] = $logs[$i+1][4] - $mem_first;
			} else {
				$logs[$i]['time_delta'] = '?';
				$logs[$i]['time_total'] = $logs[$i][3] - $first;
				$logs[$i]['mem_delta'] = '?';
				$logs[$i]['mem_total'] = $logs[$i][4] - $mem_first;
			}
			$logs[$i]['even'] = !($even = !$even);
		}
		
		{
			vrsort($logs, 'mem_delta');
			$median = $logs[round($c/2)];
			$q1 = $logs[round($c/4)];
			$q3 = $logs[round($c*3/4)];
			
			$mem_delta_median = $median['mem_delta'];
			$mem_delta_q1 = $q1['mem_delta'];
			$mem_delta_q3 = $q3['mem_delta'];
			$irq = $mem_delta_q1 - $mem_delta_q3;
			
			if($use_interquartile_top_bottom) {
				$top = $mem_delta_q1 + $irq * 1.5;
				$bottom = $mem_delta_q3 - $irq * 1.5;
			} else {
				$top = $logs[round($c*2/100)]['mem_delta'];
				$bottom = $logs[round($c*98/100)]['mem_delta'];
			}
			
			$sum_top = 0;
			$sum_bottom = 0;
			$top_value = $mem_delta_q1;
			$bottom_value = $mem_delta_q3;
			
			$top_outliers = 0;
			$bottom_outliers = 0;
			for($i=0,$n=count($logs); $i<$n; $i++) {
				$logs[$i]['mem_delta_order'] = $i;
				$logs[$i]['top_outlier'] = false;
				$logs[$i]['bottom_outlier'] = false;
				if($logs[$i]['mem_delta'] > $top) {
					$logs[$i]['top_outlier'] = true;
					$top_outliers++;
					$sum_top += $logs[$i]['mem_delta'];
				}
				if($logs[$i]['mem_delta'] < $bottom) {
					$logs[$i]['bottom_outlier'] = true;
					$bottom_outliers++;
					$sum_bottom += $logs[$i]['mem_delta'];
				}
					
				if($logs[$i]['mem_delta'] > $mem_delta_q1) {
					$logs[$i]['mem_delta_quartile'] = 0;
					if($logs[$i]['mem_delta'] > $top_value)
						$top_value = $logs[$i]['mem_delta'];
				} else if($logs[$i]['mem_delta'] > $mem_delta_median) {
					$logs[$i]['mem_delta_quartile'] = 1;
				} else if($logs[$i]['mem_delta'] > $mem_delta_q3) {
					$logs[$i]['mem_delta_quartile'] = 2;
				} else {
					$logs[$i]['mem_delta_quartile'] = 3;
					if($logs[$i]['mem_delta'] < $bottom_value)
						$bottom_value = $logs[$i]['mem_delta'];
				}
			}
			if($top_outliers)
				$sum_top /= $top_outliers;
			if($bottom_outliers)
				$sum_bottom /= $bottom_outliers;
		}
		
		$metrics = array(
				'mem_outliers' => $top_outliers + $bottom_outliers, 
				'mem_top_outliers' => $top_outliers,
				'mem_bottom_outliers' => $bottom_outliers,
				'mem_avg_top_outliers' => round($sum_top),
				'mem_avg_bottom_outliers' => round($sum_bottom),
				'mem_median' => $mem_delta_median,
				'mem_q1' => $mem_delta_q1,
				'mem_q3' => $mem_delta_q3,
				'mem_top_irq' => $top,
				'mem_bottom_irq' => $bottom,
				'mem_top' => $top_value,
				'mem_bottom' => $bottom_value
			);
			
		{
			vrsort($logs, 'time_delta');
			$median = $logs[round($c/2)];
			$q1 = $logs[round($c/4)];
			$q3 = $logs[round($c*3/4)];
			
			$time_delta_median = $median['time_delta'];
			$time_delta_q1 = $q1['time_delta'];
			$time_delta_q3 = $q3['time_delta'];
			$irq = $time_delta_q1 - $time_delta_q3;
			
			if($use_interquartile_top_bottom) {
				$top = $time_delta_q1 + $irq * 1.5;
				$bottom = $time_delta_q3 - $irq * 1.5;
			} else {
				$top = $logs[round($c*2/100)]['time_delta'];
				$bottom = $logs[round($c*98/100)]['time_delta'];
			}
			
			$sum_top = 0;
			$sum_bottom = 0;
			$top_value = $time_delta_q1;
			$bottom_value = $time_delta_q3;
			
			$top_outliers = 0;
			$bottom_outliers = 0;
			for($i=0,$n=count($logs); $i<$n; $i++) {
				$logs[$i]['time_delta_order'] = $i;
				$logs[$i]['time_top_outlier'] = false;
				$logs[$i]['time_bottom_outlier'] = false;
				if($logs[$i]['time_delta'] > $top) {
					$logs[$i]['time_top_outlier'] = true;
					$top_outliers++;
					$sum_top += $logs[$i]['time_delta'];
				}
				if($logs[$i]['time_delta'] < $bottom) {
					$logs[$i]['time_bottom_outlier'] = true;
					$bottom_outliers++;
					$sum_bottom += $logs[$i]['time_delta'];
				}
					
				if($logs[$i]['time_delta'] > $time_delta_q1) {
					$logs[$i]['time_delta_quartile'] = 0;
					if($logs[$i]['time_delta'] > $top_value)
						$top_value = $logs[$i]['time_delta'];
				} else if($logs[$i]['time_delta'] > $time_delta_median) {
					$logs[$i]['time_delta_quartile'] = 1;
				} else if($logs[$i]['time_delta'] > $time_delta_q3) {
					$logs[$i]['time_delta_quartile'] = 2;
				} else {
					$logs[$i]['time_delta_quartile'] = 3;
					if($logs[$i]['time_delta'] < $bottom_value)
						$bottom_value = $logs[$i]['time_delta'];
				}
			}
			if($top_outliers)
				$sum_top /= $top_outliers;
			if($bottom_outliers)
				$sum_bottom /= $bottom_outliers;
		}
		$metrics += array(
				'time_outliers' => round(($top_outliers + $bottom_outliers) * 1000, 3), 
				'time_top_outliers' => $top_outliers,
				'time_bottom_outliers' => $bottom_outliers,
				'time_avg_top_outliers' => round($sum_top * 1000, 3),
				'time_avg_bottom_outliers' => round($sum_bottom * 1000, 3),
				'time_median' => round($time_delta_median * 1000, 3),
				'time_q1' => round($time_delta_q1 * 1000, 3),
				'time_q3' => round($time_delta_q3 * 1000, 3),
				'time_top_irq' => round($top * 1000, 3),
				'time_bottom_irq' => round($bottom * 1000, 3),
				'time_top' => round($top_value * 1000, 3),
				'time_bottom' => round($bottom_value * 1000, 3)
			);
		
		vsort($logs, 'i');
		//ksort($logs);
		
		$response->write($this->renderHeader($metrics));
		for($i=0,$n=count($logs);$i<$n;++$i)
		{
			$response->write($this->renderMessage($logs[$i]));
		}
		$response->write($this->renderFooter());
	}
	
	/**
	 * @param string the css class of the control
	 */
	public function setCssClass($value)
	{
		$this->_cssClass = TPropertyValue::ensureString($value);
	}

	/**
	 * @return string the css class of the control
	 */
	public function getCssClass()
	{
		return TPropertyValue::ensureString($this->_cssClass);
	}

	protected function renderHeader($metrics)
	{
		$string = '';
		if($className=$this->getCssClass())
		{
			$string = <<<EOD
<table class="$className">
	<tr class="header">
		<th colspan="7">
			Application Log
		</th>
	</tr><tr class="description">
	    <th>&nbsp;</th>
		<th>Category</th><th>Message</th>
		<th>Time Spent (s)</th>
		<th>Cumulated Time Spent (s)</th>
		<th>&Delta; Memory</th>
		<th>Memory</th>
	</tr>
EOD;
		}
		else
		{
			$top_outliers = 'unset';
			if($metrics['mem_top_outliers'])
				$top_outliers = 'Avg Upper Outlier: '. $metrics['mem_avg_top_outliers'] . ' &nbsp; ';
			if($metrics['time_top_outliers'])
				$time_top_outliers = 'Avg Upper Outlier: '. $metrics['time_avg_top_outliers'] . ' ms &nbsp; ';
			$string = <<<EOD
<table cellspacing="0" cellpadding="2" border="0" width="100%" style="table-layout:auto">
	<tr>
		<th style="background-color: black; color:white;" colspan="7">
			Application Log
		</th>
	</tr>
	<tr>
		<th style="background-color: black; color:white;" colspan="7">
			Memory Stats-   &nbsp;  &nbsp; 
				Top Value: {$metrics['mem_top']} &nbsp; 
				{$top_outliers} &nbsp;
				Q1: {$metrics['mem_q1']} &nbsp; 
				Median: {$metrics['mem_median']} &nbsp; 
				Q3: {$metrics['mem_q3']}  &nbsp; 
				Bottom Value: {$metrics['mem_bottom']} &nbsp; 
		</th>
	</tr>
	<tr>
		<th style="background-color: black; color:white;" colspan="7">
			Time Stats-   &nbsp;  &nbsp; 
				Top Value: {$metrics['time_top']} ms &nbsp; 
				{$time_top_outliers} &nbsp;
				Q1: {$metrics['time_q1']} ms &nbsp; 
				Median: {$metrics['time_median']} ms &nbsp; 
				Q3: {$metrics['time_q3']} ms  &nbsp; 
				Bottom Value: {$metrics['time_bottom']} ms &nbsp; 
		</th>
	</tr>
	<tr style="background-color: #ccc; color:black">
	    <th style="width: 15px">&nbsp;</th>
		<th style="width: auto">Category</th>
		<th style="width: auto">Message</th>
		<th style="width: 120px">Time Spent (s)</th>
		<th style="width: 150px">Cumulated Time Spent (s)</th>
		<th style="width: 100px">&Delta; Memory</th>
		<th style="width: 120px">Memory</th>
	</tr>
EOD;
		}
		return $string;
	}

	protected function renderMessage($log)
	{
		$string = '';
		$total = sprintf('%0.6f', $log['time_total']);
		$delta = sprintf('%0.6f', $log['time_delta']);
		$mem_total = $log['mem_total'];
		$mem_delta = $log['mem_delta'];
		$msg = preg_replace('/\(line[^\)]+\)$/','',$log[0]); //remove line number info
		$msg = THttpUtility::htmlEncode($msg);
		if($this->getCssClass())
		{
			//$log[1] = 0xF;
			
			$colorCssClass = $log[1];
			$memcolor = $log['top_outlier'] ? 'high-memory': ($mem_delta < 0 ? 'negative-memory': '');
			$timecolor = $log['time_top_outlier'] ? 'high-time': ($delta > 0.001 ? 'medium-time': '');
			$string = <<<EOD
	<tr class="message">
		<td class="code level-$colorCssClass">&nbsp;</td>
		<td class="category">{$log[2]}</td>
		<td class="message">{$msg}</td>
		<td class="time $timecolor">{$delta}</td>
		<td class="cumulatedtime">{$total}</td>
		<td class="mem_change $memcolor">{$mem_delta}</td>
		<td class="mem_total">{$mem_total}</td>
	</tr>
EOD;
		}
		else
		{
			$bgcolor = $log['even'] ? "#fff" : "#eee";
			$color = $this->getColorLevel($log[1]);
			$memcolor = $log['top_outlier'] ? '#e00': ($mem_delta < 0 ? '#080': '');
			$timecolor = $log['time_top_outlier'] ? '#e00': ($delta > 0.001 ? '#00c': '');
			$string = <<<EOD
	<tr style="background-color: {$bgcolor}; color:#000">
		<td style="border:1px solid silver;background-color: $color;">&nbsp;</td>
		<td>{$log[2]}</td>
		<td>{$msg}</td>
		<td style="text-align:center; color: $timecolor">{$delta}</td>
		<td style="text-align:center">{$total}</td>
		<td style="text-align:center; color: $memcolor">{$mem_delta}</td>
		<td style="text-align:center">{$mem_total}</td>
	</tr>
EOD;
		}
		return $string;
	}

	protected function getColorLevel($level)
	{
		switch($level)
		{
			case TLogger::DEBUG: return 'green';
			case TLogger::INFO: return 'black';
			case TLogger::NOTICE: return '#3333FF';
			case TLogger::WARNING: return '#33FFFF';
			case TLogger::ERROR: return '#ff9933';
			case TLogger::ALERT: return '#ff00ff';
			case TLogger::FATAL: return 'red';
		}
		return '';
	}

	protected function renderFooter()
	{
		$string = '';
		if($this->getCssClass())
		{
			$string .= '<tr class="footer"><td colspan="7" align="center">';
			foreach(self::$_levelValues as $name => $level)
			{
				$string .= '<span class="level-'.$level.'">'.strtoupper($name)."</span>";
			}
		}
		else
		{
			$string .= "<tr><td colspan=\"7\" style=\"text-align:center; background-color:black; border-top: 1px solid #ccc; padding:0.2em;\">";
			foreach(self::$_levelValues as $name => $level)
			{
				$string .= "<span style=\"color:white; border:1px solid white; background-color:".$this->getColorLevel($level);
				$string .= ";margin: 0.5em; padding:0.01em;\">".strtoupper($name)."</span>";
			}
		}
		$string .= '</td></tr></table>';
		return $string;
	}
}




/**
 * TArraySorter class.
 * TArraySorter allows one to easily sort an array based on the value of a specific key
 *
 * @author Brad Anderson <javalizard@gmail.com>
 * @version $Id$
 * @package System
 * @since 3.2a
 */
class TArraySorter {
	private $_v;
	public function __construct($v) {
		$this->_v = $v;
	}
	public function sort_func($a, $b) {
		return $a[$this->_v] > $b[$this->_v];
	}
	public function sort_func_rev($a, $b) {
		return $a[$this->_v] < $b[$this->_v];
	}
	public function avsort(&$array) {
		uasort($array, array($this, 'sort_func'));
	}
	public function vsort(&$array) {
		usort($array, array($this, 'sort_func'));
	}
	public function avrsort(&$array) {
		uasort($array, array($this, 'sort_func_rev'));
	}
	public function vrsort(&$array) {
		usort($array, array($this, 'sort_func_rev'));
	}
}


/**
 * This sorts an array of arrays based on a the value of a key in the child array 
 * This sort drops all associations and reindexes the keys numerically in order
 * @param array &$array of arrays to be sorted
 * @param string $key the $key in the child arrays to use to sort by
 */
function vsort(&$array, $key) {
	$vsort = new TArraySorter($key);
	$vsort->vsort($array);
	unset($vsort);
}
/**
 * This sorts an array of arrays based on a the value of a key in the child array
 * This sort keeps all associations but reorders the array
 * @param array &$array of arrays to be sorted
 * @param string $key the $key in the child arrays to use to sort by
 */
function avsort(&$array, $key) {
	$uvsort = new TArraySorter($key);
	$uvsort->avsort($array);
	unset($uvsort);
}
/**
 * This sorts an array of arrays based on a the value of a key in the child array in reverse order
 * This sort drops all associations and reindexes the keys numerically in order
 * @param array &$array of arrays to be sorted
 * @param string $key the $key in the child arrays to use to sort by
 */
function vrsort(&$array, $key) {
	$vsort = new TArraySorter($key);
	$vsort->vrsort($array);
	unset($vsort);
}
/**
 * This sorts an array of arrays based on a the value of a key in the child array in reverse order
 * This sort keeps all associations but reorders the array
 * @param array &$array of arrays to be sorted
 * @param string $key the $key in the child arrays to use to sort by
 */
function avrsort(&$array, $key) {
	$vsort = new TArraySorter($key);
	$vsort->avrsort($array);
	unset($vsort);
}


/**
 * TDbLogRoute class
 *
 * TDbLogRoute stores log messages in a database table.
 * To specify the database table, set {@link setConnectionID ConnectionID} to be
 * the ID of a {@link TDataSourceConfig} module and {@link setLogTableName LogTableName}.
 * If they are not setting, an SQLite3 database named 'sqlite3.log' will be created and used
 * under the runtime directory.
 *
 * By default, the database table name is 'pradolog'. It has the following structure:
 * <code>
 *	CREATE TABLE pradolog
 *  (
 *		log_id INTEGER NOT NULL PRIMARY KEY,
 *		level INTEGER,
 *		category VARCHAR(128),
 *		logtime VARCHAR(20),
 *		message VARCHAR(255)
 *   );
 * </code>
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id$
 * @package System.Util
 * @since 3.1.2
 */
class TDbLogRoute extends TLogRoute
{
	/**
	 * @var string the ID of TDataSourceConfig module
	 */
	private $_connID='';
	/**
	 * @var TDbConnection the DB connection instance
	 */
	private $_db;
	/**
	 * @var string name of the DB log table
	 */
	private $_logTable='pradolog';
	/**
	 * @var boolean whether the log DB table should be created automatically
	 */
	private $_autoCreate=true;

	/**
	 * Destructor.
	 * Disconnect the db connection.
	 */
	public function __destruct()
	{
		if($this->_db!==null)
			$this->_db->setActive(false);
	}

	/**
	 * Initializes this module.
	 * This method is required by the IModule interface.
	 * It initializes the database for logging purpose.
	 * @param TXmlElement configuration for this module, can be null
	 * @throws TConfigurationException if the DB table does not exist.
	 */
	public function init($config)
	{
		if(is_array($config)) {
			if(isset($config['connectionid']))
				$this->ConnectionID = $config['connectionid'];
			if(isset($config['logtablename']))
				$this->LogTableName = $config['logtablename'];
			if(isset($config['autocreatelogtable']))
				$this->AutoCreateLogTable = $config['autocreatelogtable'];
		}
		
		if(!$this->checkForTable()) {
			// DB table not exists
			if($this->_autoCreate)
				$this->createDbTable();
			else
				throw new TConfigurationException('db_logtable_inexistent',$this->_logTable);
		}

		parent::init($config);
	}
	
	/**
	 *	This checks for the existance of the log table
	 * @return boolean true if the table exists, false if not
	 */
	protected function checkForTable() {
		
		$db=$this->getDbConnection();
		$db->setActive(true);

		$sql='SELECT * FROM '.$this->_logTable.' WHERE 0';
		try
		{
			$db->createCommand($sql)->query()->close();
			return true;
		} catch( Exception $e ) {
			return false;
		}
	}
	
	
	/**
	 *	@return string this encodes the TDbLogRoute as xml
	 */
	public function toXml() {
		$xml = '<route ' .$this->encodeId(). $this->encodeName().$this->encodeClass() . $this->encodeLevels() . 
			$this->encodeCategories() . $this->encodeControls() . $this->encodeRoles() . $this->encodeConnectionID(). 
			$this->encodeLogTableName(). $this->encodeAutoCreateLogTable().'/>';
		return $xml;
	}
	
	/**
	 *	@return string this encodes the id of the database module of the route as an xml attribute
	 */
	protected function encodeConnectionID() {
		return 'connectionid="'. addslashes($this->ConnectionID) .'" ';
	}
	
	/**
	 *	@return string this encodes the table name of the route as an xml attribute
	 */
	protected function encodeLogTableName() {
		return 'logtablename="'. addslashes($this->LogTableName) .'" ';
	}
	
	/**
	 *	@return string this encodes the auto create log table of the route as an xml attribute
	 */
	protected function encodeAutoCreateLogTable() {
		return 'autocreatelogtable="'. $this->AutoCreateLogTable .'" ';
	}

	/**
	 * Stores log messages into database.
	 * @param array list of log messages
	 */
	protected function processLogs($logs)
	{
		try {
			$sql='INSERT INTO '.$this->_logTable.'(metakey, userid, level, category, memory, logtime, message) VALUES (:metakey, :userid, :level, :category, :memory, :logtime, :message)';
			$command=$this->getDbConnection()->createCommand($sql);
			foreach($logs as $log)
			{
				$command->bindValue(':metakey',$this->MetaId);
				$command->bindValue(':userid',$this->UserId);
				$command->bindValue(':level',$log[1]);
				$command->bindValue(':category',$log[2]);
				$command->bindValue(':memory',$log[4]);
				$command->bindValue(':logtime',$log[3]);
				$command->bindValue(':message',$log[0]);
				$command->execute();
			}
		} catch(Exception $e) {
			// table may be deleted from when this was instantiated
			//probable case: deleted table (aka. dumped database), and don't fail in this case
			
			//If the table is there, something else is up and rethrow error
			if($this->checkForTable())
				throw $e;
		}
	}

	/**
	 * Creates the DB table for storing log messages.
	 * @todo create sequence for PostgreSQL
	 */
	protected function createDbTable()
	{
		$db = $this->getDbConnection();
		$driver=$db->getDriverName();
		$autoidAttributes = '';
		if($driver==='mysql')
			$autoidAttributes = 'AUTO_INCREMENT';
		
		// metakey = varchar 39 because that's the size of an IPv6 address
		$sql='CREATE TABLE '.$this->_logTable.' (
			log_id INTEGER NOT NULL PRIMARY KEY ' . $autoidAttributes . ',
			metakey VARCHAR(39),
			userid BIGINT,
			level INTEGER NOT NULL,
			category VARCHAR(128),
			memory INTEGER NOT NULL,
			logtime DECIMAL(20,8) NOT NULL,
			message VARCHAR(255), INDEX(metakey), INDEX(userid), INDEX(level), INDEX(category), INDEX(logtime))';
		$db->createCommand($sql)->execute();
	}

	/**
	 * Creates the DB connection.
	 * @param string the module ID for TDataSourceConfig
	 * @return TDbConnection the created DB connection
	 * @throws TConfigurationException if module ID is invalid or empty
	 */
	protected function createDbConnection()
	{
		if($this->_connID!=='')
		{
			$config=$this->getApplication()->getModule($this->_connID);
			if($config instanceof TDataSourceConfig)
				return $config->getDbConnection();
			else
				throw new TConfigurationException('dblogroute_connectionid_invalid',$this->_connID);
		}
		else
		{
			$db=new TDbConnection;
			// default to SQLite3 database
			$dbFile=$this->getApplication()->getRuntimePath().'/sqlite3.log';
			$db->setConnectionString('sqlite:'.$dbFile);
			return $db;
		}
	}

	/**
	 * @return TDbConnection the DB connection instance
	 */
	public function getDbConnection()
	{
		if($this->_db===null)
			$this->_db=$this->createDbConnection();
		return $this->_db;
	}

	/**
	 * @return string the ID of a {@link TDataSourceConfig} module. Defaults to empty string, meaning not set.
	 */
	public function getConnectionID()
	{
		return $this->_connID;
	}

	/**
	 * Sets the ID of a TDataSourceConfig module.
	 * The datasource module will be used to establish the DB connection for this log route.
	 * @param string ID of the {@link TDataSourceConfig} module
	 */
	public function setConnectionID($value)
	{
		$this->_connID=$value;
	}

	/**
	 * @return string the name of the DB table to store log content. Defaults to 'pradolog'.
	 * @see setAutoCreateLogTable
	 */
	public function getLogTableName()
	{
		return $this->_logTable;
	}

	/**
	 * Sets the name of the DB table to store log content.
	 * Note, if {@link setAutoCreateLogTable AutoCreateLogTable} is false
	 * and you want to create the DB table manually by yourself,
	 * you need to make sure the DB table is of the following structure:
	 * (key CHAR(128) PRIMARY KEY, value BLOB, expire INT)
	 * @param string the name of the DB table to store log content
	 * @see setAutoCreateLogTable
	 */
	public function setLogTableName($value)
	{
		$this->_logTable=$value;
	}

	/**
	 * @return boolean whether the log DB table should be automatically created if not exists. Defaults to true.
	 * @see setAutoCreateLogTable
	 */
	public function getAutoCreateLogTable()
	{
		return $this->_autoCreate;
	}

	/**
	 * @param boolean whether the log DB table should be automatically created if not exists.
	 * @see setLogTableName
	 */
	public function setAutoCreateLogTable($value)
	{
		$this->_autoCreate=TPropertyValue::ensureBoolean($value);
	}

}

/**
 * TFirebugLogRoute class.
 *
 * TFirebugLogRoute prints selected log messages in the firebug log console.
 *
 * {@link http://www.getfirebug.com/ FireBug Website}
 *
 * @author Enrico Stahn <mail@enricostahn.com>, Christophe Boulain <Christophe.Boulain@gmail.com>
 * @version $Id$
 * @package System.Util
 * @since 3.1.2
 */
class TFirebugLogRoute extends TBrowserLogRoute
{
	
	protected function renderHeader ()
	{
		$string = <<<EOD

<script type="text/javascript">
/*<![CDATA[*/
if (typeof(console) == 'object')
{
	console.log ("[Cumulated Time] [Time] [Memory] [Level] [Category] [Message]");

EOD;

		return $string;
	}

	protected function renderMessage ($log)
	{
		$logfunc = $this->getFirebugLoggingFunction($log[1]);
		$total = sprintf('%0.6f', $log['time_total']);
		$delta = sprintf('%0.6f', $log['time_delta']);
		$msg = trim($this->formatLogMessage($log[0],$log[1],$log[2],'',$log[4]));
		$msg = preg_replace('/\(line[^\)]+\)$/','',$msg); //remove line number info
		$msg = "[{$total}] [{$delta}] ".$msg; // Add time spent and cumulated time spent
		$string = $logfunc . '(\'' . addslashes($msg) . '\');' . "\n";

		return $string;
	}


	protected function renderFooter ()
	{
		$string = <<<EOD

}
</script>

EOD;

		return $string;
	}

	protected function getFirebugLoggingFunction($level)
	{
		switch ($level)
		{
			case TLogger::DEBUG:
			case TLogger::INFO:
			case TLogger::NOTICE:
				return 'console.log';
			case TLogger::WARNING:
				return 'console.warn';
			case TLogger::ERROR:
			case TLogger::ALERT:
			case TLogger::FATAL:
				return 'console.error';
		}
		return 'console.log';
	}

}

/**
 * TFirePhpLogRoute class.
 *
 * TFirePhpLogRoute prints log messages in the firebug log console via firephp.
 *
 * {@link http://www.getfirebug.com/ FireBug Website}
 * {@link http://www.firephp.org/ FirePHP Website}
 *
 * @author Yves Berkholz <godzilla80[at]gmx[dot]net>
 * @version $Id$
 * @package System.Util
 * @since 3.1.5
 */
class TFirePhpLogRoute extends TLogRoute implements IHeaderRoute
{
	/**
	 * Default group label
	 */
	const DEFAULT_LABEL = 'System.Util.TLogRouter(TFirePhpLogRoute)';

	private $_groupLabel = null;

	static public function available() {
		require_once Prado::getPathOfNamespace('System.3rdParty.FirePHPCore') . '/FirePHP.class.php';
		$instance = FirePHP::getInstance(true);
		$available = $instance->detectClientExtension();
		return $available;
	}

	/**
	 * Initializes the route.
	 * @param TXmlElement configurations specified in {@link TLogRouter}.
	 */
	public function init($config)
	{
		parent::init($config);
		
		if(is_array($config)) {
			if(isset($config['grouplabel']))
				$this->GroupLabel = $config['grouplabel'];
		}
		if($this->Application->Service instanceof IPageEvents) {
			$this->Application->Service->OnPreRenderComplete[] = array($this, 'checkHeadFlush');
		}
	}
	
	/**
	 * Not having the head tag flush when it's done is a small price to pay to enable firephp
	 * @param TXmlElement configurations specified in {@link TLogRouter}.
	 */
	public function checkHeadFlush($sender, $page) {
		if(!$this->Active) return;
		$heads = $page->findControlsByType('THead');
		
		// there should only be one but it's an array, so why not?
		foreach($heads as $head) {
			if($head->RenderFlush) {
				Prado::log('Turning off head flush option for firephp', TLogger::INFO, 'System.Util.TFirePhpLogRoute');
				$head->RenderFlush = false;
			}
		}
	}
	
	
	
	
	/**
	 *	@return string this encodes the TFirePhpLogRoute of the route as an xml attribute
	 */
	public function toXml() {
		$xml = '<route ' .$this->encodeId(). $this->encodeName().$this->encodeClass() . $this->encodeLevels() . 
			$this->encodeCategories() . $this->encodeControls() . $this->encodeRoles() . $this->encodeGroupLabel(). '/>';
		return $xml;
	}
	
	/**
	 *	@return string this encodes the grouplabel of the route as an xml attribute
	 */
	protected function encodeGroupLabel() {
		if($this->GroupLabel == self::DEFAULT_LABEL) return '';
		return 'grouplabel="'. addslashes($this->GroupLabel) .'" ';
	}
	

	/**
	 * Stores log messages into database.
	 * @param array list of log messages
	 */
	public function processLogs($logs)
	{
		if(empty($logs) || $this->getApplication()->getMode()==='Performance') return;

		if( headers_sent() ) {
			echo '
				<div style="width:100%; background-color:darkred; color:#FFF; padding:2px">
					TFirePhpLogRoute.GroupLabel "<i>' . $this -> getGroupLabel() . '</i>" -
					Routing to FirePHP impossible, because headers already sent!
				</div>
			';
			$fallback = new TFirebugLogRoute();
			$fallback->processLogs($logs);
			return;
		}

		require_once Prado::getPathOfNamespace('System.3rdParty.FirePHPCore') . '/FirePHP.class.php';
		$firephp = FirePHP::getInstance(true);
		$firephp -> setOptions(array('useNativeJsonEncode' => false));

		$firephp -> group($this->getGroupLabel(), array('Collapsed' => true));

		$firephp ->log('Time,  Message');

		$first = $logs[0][3];
		$c = count($logs);
		for($i=0,$n=$c;$i<$n;++$i)
		{
			$message	= $logs[$i][0];
			$level		= $logs[$i][1];
			$category	= $logs[$i][2];

			if ($i<$n-1)
			{
				$delta = $logs[$i+1][3] - $logs[$i][3];
				$total = $logs[$i+1][3] - $first;
			}
			else
			{
				$delta = '?';
				$total = $logs[$i][3] - $first;
			}

			$message = sPrintF('+%0.6f: %s', $delta, preg_replace('/\(line[^\)]+\)$/','',$message));
			$firephp ->fb($message, $category, self::translateLogLevel($level));
		}
		$firephp ->log( sPrintF('%0.6f', $total), 'Cumulated Time');
		$firephp -> groupEnd();
	}

	protected static function translateLogLevel($level)
	{
		switch($level)
		{
			case TLogger::INFO:
				return FirePHP::INFO;
			case TLogger::DEBUG:
			case TLogger::NOTICE:
				return FirePHP::LOG;
			case TLogger::WARNING:
				return FirePHP::WARN;
			case TLogger::ERROR:
			case TLogger::ALERT:
			case TLogger::FATAL:
				return FirePHP::ERROR;
		}
		return FirePHP::LOG;
	}

	/**
	 * @return string group label. Defaults to TFirePhpLogRoute::DEFAULT_LABEL
	 */
	public function getGroupLabel()
	{
		if($this->_groupLabel===null)
			$this->_groupLabel=self::DEFAULT_LABEL;
		return $this->_groupLabel;
	}

	/**
	 * @param string group label.
	 */
	public function setGroupLabel($value)
	{
		$this->_groupLabel=$value ? $value : null;
	}
}
