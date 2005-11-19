<?php
/**
 * THttpSession class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web
 */

/**
 * THttpSession class
 *
 * THttpSession provides session-level data management and the related configurations.
 * To start the session, call {@open}; to complete and send out session data, call {@close};
 * to destroy the session, call {@destroy}. If AutoStart is true, then the session
 * will be started once the session module is loaded and initialized.
 *
 * To access data stored in session, use the Items property. For example,
 * <code>
 *   $session=new THttpSession;
 *   $session->open();
 *   foreach($session->Items as $key=>$value)
 *       ; // read data in session
 *   $session->Items['key']=$data; // store new data into session
 * </code>
 *
 * The following configurations are available for session:
 * AutoStart, Cookie, CacheLimiter, SavePath, Storage, GCProbability, CookieUsage, Timeout.
 * See the corresponding setter and getter documentation for more information.
 * Note, these properties must be set before the session is started.
 *
 * THttpSession can be inherited with customized session storage method.
 * Override {@link _open}, {@link _close}, {@link _read}, {@link _write}, {@link _destroy} and {@link _gc}
 * and set Storage as 'Custom' to store session using methods other than files and shared memory.
 *
 * By default, THttpSession is registered with {@link TApplication} as the
 * request module. It can be accessed via {@link TApplication::getSession()}.
 *
 * THttpSession may be configured in application configuration file as follows,
 * <module id="session" type="THttpSession" SessionName="SSID" SavePath="/tmp"
 *         CookieMode="Allow" Storage="File" AutoStart="true" GCProbability="1"
 *         UseTransparentSessionID="true" TimeOut="3600" />
 * where {@link getSessionName SessionName}, {@link getSavePath SavePath},
 * {@link getCookieMode CookieMode}, {@link getStorage Storage},
 * {@link getAutoStart AutoStart}, {@link getGCProbability GCProbability},
 * {@link getUseTransparentSessionID UseTransparentSessionID} and
 * {@link getTimeOut TimeOut} are configurable properties of THttpSession.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web
 * @since 3.0
 */
class THttpSession extends TComponent implements IModule
{
	/**
	 * @var string ID of this module
	 */
	private $_id;
	/**
	 * @var THttpSessionCollection list of session variables
	 */
	private $_items;
	/**
	 * @var boolean whether this module has been initialized
	 */
	private $_initialized=false;
	/**
	 * @var boolean whether the session has started
	 */
	private $_started=false;
	/**
	 * @var boolean whether the session should be started when the module is initialized
	 */
	private $_autoStart=false;
	/**
	 * @var THttpCookie cookie to be used to store session ID and other data
	 */
	private $_cookie=null;

	/**
	 * Initializes the module.
	 * This method is required by IModule.
	 * If AutoStart is true, the session will be started.
	 * @param TApplication prado application instance
	 */
	public function init($application,$config)
	{
		if($this->_autoStart)
			session_start();
		$this->_initialized=true;
		$application->setSession($this);
	}

	/**
	 * Starts the session if it has not started yet.
	 */
	public function open()
	{
		if(!$this->_started)
		{
			if($this->_cookie!==null)
				session_set_cookie_params($this->_cookie->getExpire(),$this->_cookie->getPath(),$this->_cookie->getDomain(),$this->_cookie->getSecure());
			session_start();
			$this->_started=true;
		}
	}

	/**
	 * Ends the current session and store session data.
	 */
	public function close()
	{
		if($this->_started)
		{
			session_write_close();
			$this->_started=false;
		}
	}

	/**
	 * Destroys all data registered to a session.
	 */
	public function destroy()
	{
		if($this->_started)
		{
			session_destroy();
			$this->_started=false;
		}
	}

	/**
	 * @return string the ID of this session module (not session ID)
	 */
	public function getID()
	{
		return $this->_id;
	}

	/**
	 * @param string the ID of this session module (not session ID)
	 */
	public function setID($value)
	{
		$this->_id=$value;
	}

	/**
	 * @return THttpSessionCollection list of session variables
	 */
	public function getItems()
	{
		if($this->_items===null)
			$this->_items=new THttpSessionCollection($_SESSION);
		return $this->_items;
	}

	/**
	 * @return boolean whether the session has started
	 */
	public function getIsStarted()
	{
		return $this->_started;
	}

	/**
	 * @return string the current session ID
	 */
	public function getSessionID()
	{
		return session_id();
	}

	/**
	 * @param string the session ID for the current session
	 * @throws TInvalidOperationException if session is started already
	 */
	public function setSessionID($value)
	{
		if($this->_started)
			throw new TInvalidOperationException('httpsession_sessionid_unchangeable');
		else
			session_id($value);
	}

	/**
	 * @return string the current session name
	 */
	public function getSessionName()
	{
		return session_name();
	}

	/**
	 * @param string the session name for the current session, must be an alphanumeric string, defaults to PHPSESSID
	 * @throws TInvalidOperationException if session is started already
	 */
	public function setSessionName($value)
	{
		if($this->_started)
			throw new TInvalidOperationException('httpsession_sessionname_unchangeable');
		else if(ctype_alnum($value))
			session_name($value);
		else
			throw new TInvalidDataValueException('httpsession_sessionname_invalid',$name);
	}

	/**
	 * @return string the current session save path, defaults to '/tmp'.
	 */
	public function getSavePath()
	{
		return session_save_path();
	}

	/**
	 * @param string the current session save path
	 * @throws TInvalidOperationException if session is started already
	 */
	public function setSavePath($value)
	{
		if($this->_started)
			throw new TInvalidOperationException('httpsession_savepath_unchangeable');
		else if(is_dir($value))
			session_save_path($value);
		else
			throw new TInvalidDataValueException('httpsession_savepath_invalid',$value);
	}

	/**
	 * @return string (File|SharedMemory|Custom) storage medium of session, defaults to 'File'.
	 * @see setStorage
	 */
	public function getStorage()
	{
		switch(session_module_name())
		{
			case 'files': return 'File';
			case 'mm': return 'SharedMemory';
			case 'user': return 'Custom';
			default: return 'Unknown';
		}
	}

	/**
	 * Sets the storage medium of session data.
	 * By default, the session data is stored in files (File).
	 * You may change to use shared memory (SharedMemory) for better performance
	 * if shared memory is available on the server.
	 * Or you may choose to use your own storage (Custom). If you do so,
	 * make sure you override {@link _open}, {@link _close}, {@link _read},
	 * {@link _write}, {@link _destroy}, and {@link _gc}.
	 * @param string (File|SharedMemory|Custom) storage medium of session.
	 * @throws TInvalidOperationException if session is started already
	 */
	public function setStorage($value)
	{
		if($this->_started)
			throw new TInvalidOperationException('httpsession_storage_unchangeable');
		else
		{
			$value=TPropertyValue::ensureEnum($value,array('File','SharedMemory','Custom'));
			if($value==='Custom')
				session_set_save_handler(array($this,'_open'),array($this,'_close'),array($this,'_read'),array($this,'_write'),array($this,'_destroy'),array($this,'_gc'));
			switch($value)
			{
				case 'Custom':
					session_module_name('user');
					break;
				case 'SharedMemory':
					session_module_name('mm');
					break;
				default:
					session_module_name('files');
					break;
			}
		}
	}

	/**
	 * @return THttpCookie cookie that will be used to store session ID
	 */
	public function getCookie()
	{
		if($this->_cookie===null)
			$this->_cookie=new THttpCookie($this->getSessionName(),$this->getSessionID());
		return $this->_cookie;
	}

	/**
	 * @return string (None|Allow|Only) how to use cookie to store session ID
	 *               'None' means not using cookie, 'Allow' means using cookie, and 'Only' means using cookie only, defaults to 'Allow'.
	 */
	public function getCookieMode()
	{
		if(ini_get('session.use_cookies')==='0')
			return 'None';
		else if(ini_get('session.use_only_cookies')==='0')
			return 'Allow';
		else
			return 'Only';
	}

	/**
	 * @param string (None|Allow|Only) how to use cookie to store session ID
	 *               'None' means not using cookie, 'Allow' means using cookie, and 'Only' means using cookie only.
	 * @throws TInvalidOperationException if session is started already
	 */
	public function setCookieMode($value)
	{
		if($this->_started)
			throw new TInvalidOperationException('httpsession_cookiemode_unchangeable');
		else
		{
			$value=TPropertyValue::ensureEnum($value,array('None','Allow','Only'));
			if($value==='None')
				ini_set('session.use_cookies','0');
			else if($value==='Allow')
			{
				ini_set('session.use_cookies','1');
				ini_set('session.use_only_cookies','0');
			}
			else
			{
				ini_set('session.use_cookies','1');
				ini_set('session.use_only_cookies','1');
			}
		}
	}

	/**
	 * @return boolean whether the session should be automatically started when the session module is initialized, defaults to false.
	 */
	public function getAutoStart()
	{
		return $this->_autoStart;
	}

	/**
	 * @param boolean whether the session should be automatically started when the session module is initialized, defaults to false.
	 * @throws TInvalidOperationException if session is started already
	 */
	public function setAutoStart($value)
	{
		if($this->_initialized)
			throw new TInvalidOperationException('httpsession_autostart_unchangeable');
		else
			$this->_autoStart=TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return integer the probability (percentage) that the gc (garbage collection) process is started on every session initialization, defaults to 1 meaning 1% chance.
	 */
	public function getGCProbability()
	{
		return TPropertyValue::ensureInteger(ini_get('session.gc_probability'));
	}

	/**
	 * @param integer the probability (percentage) that the gc (garbage collection) process is started on every session initialization.
	 * @throws TInvalidOperationException if session is started already
	 * @throws TInvalidDataValueException if the value is beyond [0,100].
	 */
	public function setGCProbability($value)
	{
		if($this->_started)
			throw new TInvalidOperationException('httpsession_gcprobability_unchangeable');
		else
		{
			$value=TPropertyValue::ensureInteger($value);
			if($value>=0 && $value<=100)
			{
				ini_set('session.gc_probability',$value);
				ini_set('session.gc_divisor','100');
			}
			else
				throw new TInvalidDataValueException('httpsession_gcprobability_invalid',$value);
		}
	}

	/**
	 * @return boolean whether transparent sid support is enabled or not, defaults to false.
	 */
	public function getUseTransparentSessionID()
	{
		return ini_get('session.use_trans_sid')==='1';
	}

	/**
	 * @param boolean whether transparent sid support is enabled or not.
	 */
	public function setUseTransparentSessionID($value)
	{
		if($this->_started)
			throw new TInvalidOperationException('httpsession_transid_unchangeable');
		else
			ini_set('session.use_only_cookies',TPropertyValue::ensureBoolean($value)?'1':'0');
	}

	/**
	 * @return integer the number of seconds after which data will be seen as 'garbage' and cleaned up, defaults to 1440 seconds.
	 */
	public function getTimeout()
	{
		return TPropertyValue::ensureInteger(ini_get('session.gc_maxlifetime'));
	}

	/**
	 * @param integer the number of seconds after which data will be seen as 'garbage' and cleaned up
	 * @throws TInvalidOperationException if session is started already
	 */
	public function setTimeout($value)
	{
		if($this->_started)
			throw new TInvalidOperationException('httpsession_maxlifetime_unchangeable');
		else
			ini_set('session.gc_maxlifetime',$value);
	}

	/**
	 * Session open handler.
	 * This method should be overriden if session Storage is set as 'user'.
	 * @param string session save path
	 * @param string session name
	 * @return boolean whether session is opened successfully
	 */
	public function _open($savePath,$sessionName)
	{
		return true;
	}

	/**
	 * Session close handler.
	 * This method should be overriden if session Storage is set as 'user'.
	 * @return boolean whether session is closed successfully
	 */
	public function _close()
	{
		return true;
	}

	/**
	 * Session read handler.
	 * This method should be overriden if session Storage is set as 'user'.
	 * @param string session ID
	 * @return string the session data
	 */
	public function _read($id)
	{
		return '';
	}

	/**
	 * Session write handler.
	 * This method should be overriden if session Storage is set as 'user'.
	 * @param string session ID
	 * @param string session data
	 * @return boolean whether session write is successful
	 */
	public function _write($id,$data)
	{
		return true;
	}

	/**
	 * Session destroy handler.
	 * This method should be overriden if session Storage is set as 'user'.
	 * @param string session ID
	 * @return boolean whether session is destroyed successfully
	 */
	public function _destroy($id)
	{
		return true;
	}

	/**
	 * Session GC (garbage collection) handler.
	 * This method should be overriden if session Storage is set as 'user'.
	 * @param integer the number of seconds after which data will be seen as 'garbage' and cleaned up.
	 * @return boolean whether session is GCed successfully
	 */
	public function _gc($maxLifetime)
	{
		return true;
	}
}

/**
 * THttpSessionCollection class.
 *
 * THttpSessionCollection implements a collection class to store session data items.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web
 * @since 3.0
 */
class THttpSessionCollection extends TMap
{
	/**
	 * @var boolean whether the initial session data has been loaded into the collection
	 */
	private $_initialized=false;

	/**
	 * Constructor.
	 * Initializes the list with an array or an iterable object.
	 * @param array|Iterator the intial data.
	 */
	public function __construct($data=null)
	{
		parent::__construct($data);
		$this->_initialized=true;
	}

	/**
	 * Adds the item into session.
	 * This method will be invoked whenever an item is added to the collection.
	 */
	protected function addedItem($key,$value)
	{
		if($this->_initialized)
			$_SESSION[$key]=$value;
	}

	/**
	 * Removes the item from session.
	 * This method will be invoked whenever an item is removed from the collection.
	 */
	protected function removedItem($key,$value)
	{
		unset($_SESSION[$key]);
	}
}
?>