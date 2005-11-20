<?php
/**
 * TAuthManager class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Security
 */

/**
 * Using TUserManager class
 */
Prado::using('System.Security.TUserManager');

/**
 * TAuthManager class
 *
 * TAuthManager performs user authentication and authorization for a Prado application.
 * TAuthManager works together with a {@link TUserManager} module that can be
 * specified via the {@link setUserManager UserManager} property.
 * If an authorization fails, TAuthManager will try to redirect the client
 * browser to a login page that is specified via the {@link setLoginPage LoginPage}.
 * To login or logout a user, call {@link login} or {@link logout}, respectively.
 *
 * To load TAuthManager, configure it in application configuration as follows,
 * <module id="auth" type="System.Security.TAuthManager" UserManager="users" LoginPage="login" />
 * <module id="users" type="System.Security.TUserManager" />
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Security
 * @since 3.0
 */
class TAuthManager extends TComponent implements IModule
{
	/**
	 * GET variable name for return url
	 */
	const RETURN_URL_VAR='ReturnUrl';
	/**
	 * @var boolean if the module has been initialized
	 */
	private $_initialized=false;
	/**
	 * @var TApplication application instance
	 */
	private $_application;
	/**
	 * @var TUserManager user manager instance
	 */
	private $_userManager=null;
	/**
	 * @var string login page
	 */
	private $_loginPage=null;
	/**
	 * @var boolean whether authorization should be skipped
	 */
	private $_skipAuthorization=false;

	/**
	 * @return string id of this module
	 */
	public function getID()
	{
		return $this->_id;
	}

	/**
	 * @param string id of this module
	 */
	public function setID($value)
	{
		$this->_id=$value;
	}

	/**
	 * Initializes this module.
	 * This method is required by the IModule interface.
	 * @param TApplication Prado application, can be null
	 * @param TXmlElement configuration for this module, can be null
	 * @throws TConfigurationException if user manager does not exist or is not TUserManager
	 */
	public function init($application,$config)
	{
		if($this->_userManager===null)
			throw new TConfigurationException('authmanager_usermanager_required');
		if(is_string($this->_userManager))
		{
			if(($users=$application->getModule($this->_userManager))===null)
				throw new TConfigurationException('authmanager_usermanager_inexistent',$this->_userManager);
			if(!($users instanceof TUserManager))
				throw new TConfigurationException('authmanager_usermanager_invalid',$this->_userManager);
			$this->_userManager=$users;
		}
		$this->_application=$application;
		$application->attachEventHandler('Authentication',array($this,'doAuthentication'));
		$application->attachEventHandler('EndRequest',array($this,'leave'));
		$application->attachEventHandler('Authorization',array($this,'doAuthorization'));
		$this->_initialized=true;
	}

	/**
	 * @return TUserManager user manager instance
	 */
	public function getUserManager()
	{
		return $this->_userManager;
	}

	/**
	 * @param string|TUserManager the user manager module ID or the user mananger object
	 * @throws TInvalidOperationException if the module has been initialized or the user manager object is not TUserManager
	 */
	public function setUserManager($provider)
	{
		if($this->_initialized)
			throw new TInvalidOperationException('authmanager_usermanager_unchangeable');
		if(!is_string($provider) && !($provider instanceof TUserManager))
			throw new TConfigurationException('authmanager_usermanager_invalid',$this->_userManager);
		$this->_userManager=$provider;
	}

	/**
	 * @return string path of login page should login is required
	 */
	public function getLoginPage()
	{
		return $this->_loginPage;
	}

	/**
	 * Sets the login page that the client browser will be redirected to if login is needed.
	 * Login page should be specified in the format of page path.
	 * @param string path of login page should login is required
	 * @see TPageService
	 */
	public function setLoginPage($pagePath)
	{
		$this->_loginPage=$pagePath;
	}

	/**
	 * Performs authentication.
	 * This is the event handler attached to application's Authentication event.
	 * Do not call this method directly.
	 * @param mixed sender of the Authentication event
	 * @param mixed event parameter
	 */
	public function doAuthentication($sender,$param)
	{
		$this->onAuthenticate($param);

		$service=$this->_application->getService();
		if(($service instanceof TPageService) && $service->isRequestingPage($this->getLoginPage()))
			$this->_skipAuthorization=true;
	}

	/**
	 * Performs authorization.
	 * This is the event handler attached to application's Authorization event.
	 * Do not call this method directly.
	 * @param mixed sender of the Authorization event
	 * @param mixed event parameter
	 */
	public function doAuthorization($sender,$param)
	{
		if(!$this->_skipAuthorization)
		{
			$this->onAuthorize($param);
		}
	}

	/**
	 * Performs login redirect if authorization fails.
	 * This is the event handler attached to application's EndRequest event.
	 * Do not call this method directly.
	 * @param mixed sender of the event
	 * @param mixed event parameter
	 */
	public function leave($sender,$param)
	{
		if($this->_application->getResponse()->getStatusCode()===401)
		{
			$service=$this->_application->getService();
			if($service instanceof TPageService)
			{
				$returnUrl=$this->_application->getRequest()->getRequestUri();
				$url=$service->constructUrl($this->getLoginPage(),array(self::RETURN_URL_VAR=>$returnUrl));
				$this->_application->getResponse()->redirect($url);
			}
		}
	}

	/**
	 * Performs the real authentication work.
	 * An Authenticate event will be raised if there is any handler attached to it.
	 * If the application already has a non-null user, it will return without further authentication.
	 * Otherwise, user information will be restored from session data.
	 * @param mixed parameter to be passed to Authenticate event
	 * @throws TConfigurationException if session module does not exist.
	 */
	public function onAuthenticate($param)
	{
		if($this->hasEventHandler('Authenticate'))
			$this->raiseEvent('Authenticate',$this,$this->_application);
		if($this->_application->getUser()!==null)
			return;

		if(($session=$this->_application->getSession())===null)
			throw new TConfigurationException('authmanager_session_required');
		$session->open();
		$sessionInfo=$session->getItems()->itemAt($this->generateUserSessionKey());
		$user=$this->_userManager->getUser(null)->loadFromString($sessionInfo);
		$this->_application->setUser($user);
	}

	/**
	 * Performs the real authorization work.
	 * Authorization rules obtained from the application will be used to check
	 * if a user is allowed. If authorization fails, the response status code
	 * will be set as 401 and the application terminates.
	 * @param mixed parameter to be passed to Authenticate event
	 */
	public function onAuthorize($param)
	{
		if($this->hasEventHandler('Authorize'))
			$this->raiseEvent('Authorize',$this,$this->_application);
		if(!$this->_application->getAuthorizationRules()->isUserAllowed($this->_application->getUser(),$this->_application->getRequest()->getRequestType()))
		{
			$this->_application->getResponse()->setStatusCode(401);
			$this->_application->completeRequest();
		}
	}

	/**
	 * @return string a key used to store user information in session
	 */
	protected function generateUserSessionKey()
	{
		return md5($this->_application->getUniqueID().'prado:user');
	}

	/**
	 * Updates the user data stored in session.
	 * @param IUser user object
	 * @throws new TConfigurationException if session module is not loaded.
	 */
	public function updateSessionUser($user)
	{
		if(!$user->getIsGuest())
		{
			if(($session=$this->_application->getSession())===null)
				throw new TConfigurationException('authmanager_session_required');
			else
				$session->getItems()->add($this->generateUserSessionKey(),$user->saveToString());
		}
	}

	/**
	 * Logs in a user with username and password.
	 * The username and password will be used to validate if login is successful.
	 * If yes, a user object will be created for the application.
	 * @param string username
	 * @param string password
	 * @return boolean if login is successful
	 */
	public function login($username,$password)
	{
		if($this->_userManager->validateUser($username,$password))
		{
			$user=$this->_userManager->getUser($username);
			$this->updateSessionUser($user);
			$this->_application->setUser($user);
			return true;
		}
		else
			return false;
	}

	/**
	 * Logs out a user.
	 * User session will be destroyed after this method is called.
	 * @throws TConfigurationException if session module is not loaded.
	 */
	public function logout()
	{
		if(($session=$this->_application->getSession())===null)
			throw new TConfigurationException('authmanager_session_required');
		else
		{
			$this->_userManager->switchToGuest($this->_application->getUser());
			$session->destroy();
		}
	}
}

?>