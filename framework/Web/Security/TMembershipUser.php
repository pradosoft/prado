<?php
/**
 * TMembershipUser class.
 * Exposes and updates membership user information in the membership data store.
 *
 * @author Jason Ragsdale <jrags@jasrags.net>
 * @version $Id: TMembershipUser.php 1398 2006-09-08 19:31:03Z xue $
 * @package System.Web.Security
 * @since 3.1
 */
Prado::using('System.Web.Security.TProviderException');
Prado::using('System.Web.Security.TMembership');
class TMembershipUser
{
	private $_comment;
	private $_creationDate;
	private $_email;
	private $_isApproved=false;
	private $_isLockedOut=false;
	private $_isOnline=false;
	private $_lastActivityDate;
	private $_lastLockoutDate;
	private $_lastLoginDate;
	private $_lastPasswordChangedDate;
	private $_passwordQuestion;
	private $_providerName;
	private $_providerUserKey;
	private $_userName;

	public function __construct($providerName=null,$name=null,$providerUserKey=null,$email=null,$passwordQuestion=null,$comment=null,$isApproved=null,$isLockedOut=null,$creationDate=null,$lastLoginDate=null,$lastActivityDate=null,$lastPasswordChangedDate=null,$lastLockoutDate=null)
	{
		if (($providerName===null) || (TMembership::getProviders($providerName)===null))
		{
			throw new TProviderException('Membership_provider_name_invalid',$providerName);
		}
		if ($name!==null)
		{
			$name = trim($name);
		}
		if ($email!==null)
		{
			$email = trim($email);
		}
		if ($passwordQuestion!==null)
		{
			$passwordQuestion = trim($passwordQuestion);
		}
		$this->_providerName = $providerName;
		$this->_userName = $name;
		$this->_providerUserKey = $providerUserKey;
		$this->_email = $email;
		$this->_passwordQuestion = $passwordQuestion;
		$this->_comment = $comment;
		$this->_isApproved = $isApproved;
		$this->_isLockedOut = $isLockedOut;
		$this->_creationDate = $creationDate;
		$this->_lastLoginDate = $lastLoginDate;
		$this->_lastActivityDate = $lastActivityDate;
		$this->_lastPasswordChangedDate = $lastPasswordChangedDate;
		$this->_lastLockoutDate = $lastLockoutDate;
	}
	public function getComment()
	{
		return $this->_comment;
	}
	public function setApplicationName($value)
	{
		$this->_comment = TPropertyValue::ensureString($value);
	}
	public function getCreationDate()
	{
		return $this->_creationDate;
	}
	public function getEmail()
	{
		return $this->_email;
	}
	public function setEmail($value)
	{
		$this->_email = TPropertyValue::ensureString($value);
	}
	public function getIsApproved()
	{
		return $this->_isApproved;
	}
	public function setIsApproved($value)
	{
		$this->_isApproved = TPropertyValue::ensureBoolean($value);
	}
	public function getIsLockedOut()
	{
		return $this->_isLockedOut;
	}
	public function getIsOnline()
	{
		return $this->_isOnline;
	}
	public function getLastActivityDate()
	{
		return $this->_lastActivityDate;
	}
	public function setLastActivityDate($value)
	{
		$this->_lastActivityDate = TPropertyValue::ensureString($value);
	}
	public function getLastLockoutDate()
	{
		return $this->_lastLockoutDate;
	}
	public function getLastLoginDate()
	{
		return $this->_lastLoginDate;
	}
	public function setLastLoginDate($value)
	{
		$this->_lastLoginDate = TPropertyValue::ensureString($value);
	}
	public function getLastPasswordChangedDate()
	{
		return $this->_lastPasswordChangedDate;
	}
	public function getPasswordQuestion()
	{
		return $this->_passwordQuestion;
	}
	public function getProviderUserKey()
	{
		return $this->_providerUserKey;
	}
	public function getUserName()
	{
		return $this->_userName;
	}
	public function changePassword($oldPassword,$newPassword,$throwOnError=null)
	{

	}
	public function getPassword()
	{
		//		$throwOnError;
		//		$passwordAnswer;
		//		$answer;
		//		$answer,$useAnswer,$throwOnError;
	}
	public function resetPassword()
	{
		//		$throwOnError;
		//		$passwordAnswer;
		//		$answer;
		//		$answer,$useAnswer,$throwOnError;
	}
 	public function unlockUser()
 	{
 		
 	}
 	public function update()
 	{
 		
 	}
 	private function updateSelf()
 	{
 		
 	}
}
?>