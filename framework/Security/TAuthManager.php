<?php

class TAuthManager extends TComponent implements IModule
{
	const RETURN_URL_VAR='ReturnUrl';
	private $_guest='Guest';
	private $_initialized=false;
	private $_application;
	private $_users=null;
	private $_loginPage=null;
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
	 * @param IApplication Prado application, can be null
	 * @param TXmlElement configuration for this module, can be null
	 */
	public function init($application,$config)
	{
		$this->_application=$application;
		$application->attachEventHandler('Authentication',array($this,'doAuthentication'));
		$application->attachEventHandler('EndRequest',array($this,'leave'));
		$application->attachEventHandler('Authorization',array($this,'doAuthorization'));
		$this->_initialized=true;
	}

	public function getGuestName()
	{
		return $this->_guest;
	}

	public function setGuestName($value)
	{
		$this->_guest=$value;
	}

	public function getUserManager()
	{
		if($this->_users instanceof TUserManager)
			return $this->_users;
		else
		{
			if(($users=$this->_application->getModule($this->_users))===null)
				throw new TConfigurationException('authenticator_usermanager_inexistent',$this->_users);
			if(!($users instanceof TUserManager))
				throw new TConfigurationException('authenticator_usermanager_invalid',$this->_users);
			$this->_users=$users;
			return $users;
		}
	}

	public function setUserManager($provider)
	{
		$this->_users=$provider;
	}

	public function getLoginPage()
	{
		return $this->_loginPage;
	}

	public function setLoginPage($pagePath)
	{
		$this->_loginPage=$pagePath;
	}

	public function doAuthentication($sender,$param)
	{
		$this->onAuthenticate($param);

		$service=$this->_application->getService();
		if(($service instanceof TPageService) && $service->isRequestingPage($this->getLoginPage()))
			$this->_skipAuthorization=true;
	}

	public function doAuthorization($sender,$param)
	{
		if(!$this->_skipAuthorization)
		{
			$this->onAuthorize($param);
		}
	}

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

	public function onAuthenticate($param)
	{
		if($this->hasEventHandler('Authenticate'))
			$this->raiseEvent('Authenticate',$this,$this->_application);
		if($this->_application->getUser()!==null)
			return;

		if(($session=$this->_application->getSession())===null)
			throw new TConfigurationException('authenticator_session_required');
		$session->open();
		if(($userManager=$this->getUserManager())===null)
			throw new TConfigurationException('authenticator_usermanager_required');
		$sessionInfo=$session->getItems()->itemAt($this->generateUserSessionKey());
		$user=$userManager->getUser(null)->loadFromString($sessionInfo);
		$this->_application->setUser($user);
	}

	public function onAuthorize($param)
	{
		if($this->hasEventHandler('Authenticate'))
			$this->raiseEvent('Authorize',$this,$this->_application);
		if(!$this->_application->getAuthorizationRules()->isUserAllowed($this->_application->getUser(),$this->_application->getRequest()->getRequestType()))
		{
			$this->_application->getResponse()->setStatusCode(401);
			$this->_application->completeRequest();
		}
	}

	protected function generateUserSessionKey()
	{
		return md5($this->_application->getUniqueID().'prado:user');
	}

	public function updateSessionUser($user)
	{
		if(!$user->getIsGuest())
		{
			if(($session=$this->_application->getSession())===null)
				throw new TConfigurationException('authenticator_session_required');
			else
				$session->getItems()->add($this->generateUserSessionKey(),$user->saveToString());
		}
	}

	public function login($username,$password)
	{
		if(($userManager=$this->getUserManager())===null)
			throw new TConfigurationException('authenticator_usermanager_required');
		else
		{
			if($userManager->validateUser($username,$password))
			{
				$user=$userManager->getUser($username);
				$this->updateSessionUser($user);
				$this->_application->setUser($user);
				return true;
			}
			else
				return false;
		}
	}

	public function logout()
	{
		if(($userManager=$this->getUserManager())===null)
			throw new TConfigurationException('authenticator_usermanager_required');
		else if(($session=$this->_application->getSession())===null)
			throw new TConfigurationException('authenticator_session_required');
		else
		{
			$userManager->logout($this->_application->getUser());
			$session->destroy();
		}
	}
}

?>