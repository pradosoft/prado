<?php
/**
 * TAuthManager class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Security
 */

/**
 * Using IUserManager interface
 */
Prado::using('System.Security.IUserManager');

/**
 * TAuthManager class
 *
 * TAuthManager performs user authentication and authorization for a Prado application.
 * TAuthManager works together with a {@link IUserManager} module that can be
 * specified via the {@link setUserManager UserManager} property.
 * If an authorization fails, TAuthManager will try to redirect the client
 * browser to a login page that is specified via the {@link setLoginPage LoginPage}.
 * To login or logout a user, call {@link login} or {@link logout}, respectively.
 *
 * To load TAuthManager, configure it in application configuration as follows,
 * <module id="auth" class="System.Security.TAuthManager" UserManager="users" LoginPage="login" />
 * <module id="users" class="System.Security.TUserManager" />
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id$
 * @package System.Security
 * @since 3.0
 */
class TAuthManager extends TModule
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
	 * @var IUserManager user manager instance
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
	 * Initializes this module.
	 * This method is required by the IModule interface.
	 * @param TXmlElement configuration for this module, can be null
	 * @throws TConfigurationException if user manager does not exist or is not IUserManager
	 */
	public function init($config)
	{
		if($this->_userManager===null)
			throw new TConfigurationException('authmanager_usermanager_required');
		$application=$this->getApplication();
		if(is_string($this->_userManager))
		{
			if(($users=$application->getModule($this->_userManager))===null)
				throw new TConfigurationException('authmanager_usermanager_inexistent',$this->_userManager);
			if(!($users instanceof IUserManager))
				throw new TConfigurationException('authmanager_usermanager_invalid',$this->_userManager);
			$this->_userManager=$users;
		}
		$application->attachEventHandler('OnAuthentication',array($this,'doAuthentication'));
		$application->attachEventHandler('OnEndRequest',array($this,'leave'));
		$application->attachEventHandler('OnAuthorization',array($this,'doAuthorization'));
		$this->_initialized=true;
	}

	/**
	 * @return IUserManager user manager instance
	 */
	public function getUserManager()
	{
		return $this->_userManager;
	}

	/**
	 * @param string|IUserManager the user manager module ID or the user mananger object
	 * @throws TInvalidOperationException if the module has been initialized or the user manager object is not IUserManager
	 */
	public function setUserManager($provider)
	{
		if($this->_initialized)
			throw new TInvalidOperationException('authmanager_usermanager_unchangeable');
		if(!is_string($provider) && !($provider instanceof IUserManager))
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

		$service=$this->getService();
		if(($service instanceof TPageService) && $service->getRequestedPagePath()===$this->getLoginPage())
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
		$application=$this->getApplication();
		if($application->getResponse()->getStatusCode()===401)
		{
			$service=$application->getService();
			if($service instanceof TPageService)
			{
				$returnUrl=$application->getRequest()->getRequestUri();
				$this->setReturnUrl($returnUrl);
				$url=$service->constructUrl($this->getLoginPage());
				$application->getResponse()->redirect($url);
			}
		}
	}

	/**
	 * @return string URL that the browser should be redirected to when login succeeds.
	 */
	public function getReturnUrl()
	{
		return $this->getSession()->itemAt(self::RETURN_URL_VAR);
	}

	/**
	 * Sets the URL that the browser should be redirected to when login succeeds.
	 * @param string the URL to be redirected to.
	 */
	public function setReturnUrl($value)
	{
		$this->getSession()->add(self::RETURN_URL_VAR,$value);
	}

	/**
	 * Performs the real authentication work.
	 * An OnAuthenticate event will be raised if there is any handler attached to it.
	 * If the application already has a non-null user, it will return without further authentication.
	 * Otherwise, user information will be restored from session data.
	 * @param mixed parameter to be passed to OnAuthenticate event
	 * @throws TConfigurationException if session module does not exist.
	 */
	public function onAuthenticate($param)
	{
		$application=$this->getApplication();

		if(($session=$application->getSession())===null)
			throw new TConfigurationException('authmanager_session_required');
		$session->open();
		$sessionInfo=$session->itemAt($this->generateUserSessionKey());
		$user=$this->_userManager->getUser(null)->loadFromString($sessionInfo);
		$application->setUser($user);

		// event handler gets a chance to do further auth work
		if($this->hasEventHandler('OnAuthenticate'))
			$this->raiseEvent('OnAuthenticate',$this,$application);
	}

	/**
	 * Performs the real authorization work.
	 * Authorization rules obtained from the application will be used to check
	 * if a user is allowed. If authorization fails, the response status code
	 * will be set as 401 and the application terminates.
	 * @param mixed parameter to be passed to OnAuthorize event
	 */
	public function onAuthorize($param)
	{
		$application=$this->getApplication();
		if($this->hasEventHandler('OnAuthorize'))
			$this->raiseEvent('OnAuthorize',$this,$application);
		if(!$application->getAuthorizationRules()->isUserAllowed($application->getUser(),$application->getRequest()->getRequestType()))
		{
			$application->getResponse()->setStatusCode(401);
			$application->completeRequest();
		}
	}

	/**
	 * @return string a key used to store user information in session
	 */
	protected function generateUserSessionKey()
	{
		return md5($this->getApplication()->getUniqueID().'prado:user');
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
			if(($session=$this->getSession())===null)
				throw new TConfigurationException('authmanager_session_required');
			else
				$session->add($this->generateUserSessionKey(),$user->saveToString());
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
			$this->getApplication()->setUser($user);
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
		if(($session=$this->getSession())===null)
			throw new TConfigurationException('authmanager_session_required');
		else
		{
			$this->getUser()->setIsGuest(true);
			$session->destroy();
		}
	}
}

?>