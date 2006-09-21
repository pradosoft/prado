<?php
/**
 * TMembershipProvider class.
 * Defines the contract that PRADO implements to provide membership services using custom membership providers.
 *
 * @author Jason Ragsdale <jrags@jasrags.net>
 * @version $Id: TMembershipProvider.php 1398 2006-09-08 19:31:03Z xue $
 * @package System.Web.Security
 * @since 3.1
 */
Prado::using('System.Configuration.Provider.TProviderBase');
abstract class TMembershipProvider extends TProviderBase
{
	private $_applicationName;
	private $_enablePasswordReset=false;
	private $_enablePasswordRetrieval=false;
	private $_maxInvalidPasswordAttempts;
	private $_minRequiredNonAlphanumericCharacters;
	private $_minRequiredPasswordLength;
	private $_passwordAttemptWindow;
	private $_passwordStrengthRegularExpression;
	private $_requiresQuestionAndAnswer=false;
	private $_requiresUniqueEmail=false;
	/**
	 * @var string external configuration file
	 */
	private $_configFile=null;

	public function getEnablePasswordReset()
	{
		return $this->_enablePasswordReset;
	}
	public function setEnablePasswordReset($value)
	{
		$this->_enablePasswordReset = TPropertyValue::ensureBoolean($value);
	}
	public function getEnablePasswordRetrieval()
	{
		return $this->_enablePasswordRetrieval;
	}
	public function setEnablePasswordRetrieval($value)
	{
		$this->_enablePasswordRetrieval = TPropertyValue::ensureBoolean($value);
	}
	public function getMaxInvalidPasswordAttempts()
	{
		return $this->_maxInvalidPasswordAttempts;
	}
	public function setMaxInvalidPasswordAttempts($value)
	{
		$this->_maxInvalidPasswordAttempts = TPropertyValue::ensureInteger($value);
	}
	public function getMinRequiredNonAlphanumericCharacters()
	{
		return $this->_minRequiredNonAlphanumericCharacters;
	}
	public function setMinRequiredNonAlphanumericCharacters($value)
	{
		$this->_minRequiredNonAlphanumericCharacters = TPropertyValue::ensureInteger($value);
	}
	public function getMinRequiredPasswordLength()
	{
		return $this->_minRequiredPasswordLength;
	}
	public function setMinRequiredPasswordLength($value)
	{
		$this->_minRequiredPasswordLength = TPropertyValue::ensureInteger($value);
	}
	public function getPasswordAttemptWindow()
	{
		return $this->_passwordAttemptWindow;
	}
	public function setPasswordAttemptWindow($value)
	{
		$this->_passwordAttemptWindow = TPropertyValue::ensureInteger($value);
	}
	public function getPasswordStrengthRegularExpression()
	{
		return $this->_passwordStrengthRegularExpression;
	}
	public function setPasswordStrengthRegularExpression($value)
	{
		$this->_passwordStrengthRegularExpression = TPropertyValue::ensureString($value);
	}
	public function getRequiresQuestionAndAnswer()
	{
		return $this->_requiresQuestionAndAnswer;
	}
	public function setRequiresQuestionAndAnswer($value)
	{
		$this->_requiresQuestionAndAnswer = TPropertyValue::ensureString($value);
	}
	public function getRequiresUniqueEmail()
	{
		return $this->_requiresUniqueEmail;
	}
	public function setRequiresUniqueEmail($value)
	{
		$this->_requiresUniqueEmail = TPropertyValue::ensureBoolean($value);
	}
	
	public function __construct()
	{

	}
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
				throw new TConfigurationException('membershipprovider_configfile_invalid',$this->_configFile);
		}
		$this->loadConfig($config);
//		$this->getApplication()->attachEventHandler('OnEndRequest',array($this,'collectLogs'));
	}
	/**
	 * Loads configuration from an XML element
	 * @param TXmlElement configuration node
	 * @throws TConfigurationException if log route class or type is not specified
	 */
	private function loadConfig($xml)
	{
		foreach($xml->getElementsByTagName('provider') as $providerConfig)
		{
			$properties=$providerConfig->getAttributes();
			if(($class=$properties->remove('class'))===null)
				throw new TConfigurationException('membershipprovider_routeclass_required');
			$provider=Prado::createComponent($class);
			if(!($provider instanceof TMembershipProvider))
				throw new TConfigurationException('membershipprovider_routetype_invalid');
			foreach($properties as $name=>$value)
				$provider->setSubproperty($name,$value);
			$this->_providers[]=$provider;
			$provider->init($providerConfig);
		}
	}
	public abstract function changePassword($username,$oldPassword,$newPassword);
	public abstract function changePasswordQuestionAndAnswer($username,$password,$newPasswordQuestion,$newPasswordAnswer);
	public abstract function createUser($username,$password,$email,$passwordQuestion,$passwordAnswer,$isApproved,$providerUserKey);
	protected function decryptPassword($encodedPassword)
	{

	}
	public abstract function deleteUser($username,$deleteAllRelatedData);
	public function encodePassword($pass,$passwordFormat,$salt)
	{

	}
	protected function encryptPassword($password)
	{

	}
	public abstract function findUsersByEmail($emailToMatch,$pageIndex=null,$pageSize=null);
	public abstract function findUsersByName($usernameToMatch,$pageIndex=null,$pageSize=null);
	public function generateSalt()
	{
		
	}
	public abstract function getAllUsers($pageIndex=null,$pageSize=null);
	public abstract function getNumberOfUsersOnline();
	public abstract function getPassword($username,$answer);
	public abstract function getMembershipUser($username=null,$providerUserKey=null,$userIsOnline=false);
	public abstract function getUserNameByEmail($email);
	public abstract function resetPassword($username,$answer);
	public function unEncodePassword($pass,$passwordFormat)
	{

	}
	public abstract function unlockUser($userName);
	public abstract function updateUser(TMembershipUser $user);
	public abstract function validateUser($username,$password);
}
?>