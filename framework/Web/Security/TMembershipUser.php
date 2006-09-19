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
class TMembershipUser
{
	public $Comment;
	public $CreationDate;
	public $Email;
	public $IsApproved=false;
	public $IsLockedOut=false;
	public $IsOnline=false;
	public $LastActivityDate;
	public $LastLockoutDate;
	public $LastLoginDate;
	public $LastPasswordChangedDate;
	public $PasswordQuestion;
	public $ProviderName;
	public $ProviderUserKey;
	public $UserName;
	private $_Comment;
	private $_CreationDate;
	private $_Email;
	private $_IsApproved=false;
	private $_IsLockedOut=false;
	private $_LastActivityDate;
	private $_LastLockoutDate;
	private $_LastLoginDate;
	private $_LastPasswordChangedDate;
	private $_PasswordQuestion;
	private $_ProviderName;
	private $_ProviderUserKey;
	private $_UserName;

	public function __construct($providerName=null,$name=null,$providerUserKey=null,$email=null,$passwordQuestion=null,$comment=null,$isApproved=null,$isLockedOut=null,$creationDate=null,$lastLoginDate=null,$lastActivityDate=null,$lastPasswordChangedDate=null,$lastLockoutDate=null)
	{
		if (($providerName===null) || (TMembership===null))
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
		$this->_ProviderName = $providerName;
		$this->_UserName = $name;
		$this->_ProviderUserKey = $providerUserKey;
		$this->_Email = $email;
		$this->_PasswordQuestion = $passwordQuestion;
		$this->_Comment = $comment;
		$this->_IsApproved = $isApproved;
		$this->_IsLockedOut = $isLockedOut;
		$this->_CreationDate = $creationDate;
		$this->_LastLoginDate = $lastLoginDate;
		$this->_LastActivityDate = $lastActivityDate;
		$this->_LastPasswordChangedDate = $lastPasswordChangedDate;
		$this->_LastLockoutDate = $lastLockoutDate;
	}
	public function getComment()
	{
		return $this->Comment;
	}
	public function setApplicationName($value)
	{
		$this->Comment = TPropertyValue::ensureString($value);
	}
	public function getCreationDate()
	{
		return $this->CreationDate;
	}
	public function getEmail()
	{
		return $this->Email;
	}
	public function setEmail($value)
	{
		$this->Email = TPropertyValue::ensureString($value);
	}
	public function getIsApproved()
	{
		return $this->IsApproved;
	}
	public function setIsApproved($value)
	{
		$this->IsApproved = TPropertyValue::ensureBoolean($value);
	}
	public function getIsLockedOut()
	{
		return $this->IsLockedOut;
	}
	public function getIsOnline()
	{
		return $this->IsOnline;
	}
	public function getLastActivityDate()
	{
		return $this->LastActivityDate;
	}
	public function setLastActivityDate($value)
	{
		$this->LastActivityDate = TPropertyValue::ensureString($value);
	}
	public function getLastLockoutDate()
	{
		return $this->LastLockoutDate;
	}
	public function getLastLoginDate()
	{
		return $this->LastLoginDate;
	}
	public function setLastLoginDate($value)
	{
		$this->LastLoginDate = TPropertyValue::ensureString($value);
	}
	public function getLastPasswordChangedDate()
	{
		return $this->LastPasswordChangedDate;
	}
	public function getLastPasswordChangedDate()
	{
		return $this->LastPasswordChangedDate;
	}
	public function getPasswordQuestion()
	{
		return $this->PasswordQuestion;
	}
	public function getProviderUserKey()
	{
		return $this->ProviderUserKey;
	}
	public function getUserName()
	{
		return $this->UserName;
	}
	public function ChangePassword($oldPassword,$newPassword,$throwOnError=null)
	{

	}
	public function GetPassword()
	{
		//		$throwOnError;
		//		$passwordAnswer;
		//		$answer;
		//		$answer,$useAnswer,$throwOnError;
	}
	public function ResetPassword()
	{
		//		$throwOnError;
		//		$passwordAnswer;
		//		$answer;
		//		$answer,$useAnswer,$throwOnError;
	}
 	public function UnlockUser()
 	{
 		
 	}
 	public function Update()
 	{
 		
 	}
 	private function UpdateSelf()
 	{
 		
 	}
}
?>