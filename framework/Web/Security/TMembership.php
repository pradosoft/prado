<?php
/**
 * TMembership class.
 * Validates user credentials and manages user settings. This class cannot be inherited.
 *
 * @author Jason Ragsdale <jrags@jasrags.net>
 * @version $Id: TMembership.php 1398 2006-09-08 19:31:03Z xue $
 * @package System.Web.Security
 * @since 3.1
 */
final class TMembership
{
	private static $_applicationName;
	private static $_enablePasswordReset=false;
	private static $_enablePasswordRetrieval=false;
	private static $_hashAlgorithmType;
	private static $_isHashAlgorithmFromMembershipConfig=false;
	private static $_maxInvalidPasswordAttempts;
	private static $_minRequiredNonAlphanumericCharacters;
	private static $_minRequiredPasswordLength;
	private static $_passwordAttemptWindow;
	private static $_passwordStrengthReqularExpression;
	private static $_provider;
	private static $_providers;
	private static $_requiresQuestionAndAnswer=false;
	private static $_userIsOnlineTimeWindow=15;
	private static $_punctuations='!@#$%^&*()_-+=[{]};:>./?';
	private static $_hashAlgorithmFromConfig=false;
	private static $_initialized=false;
	private static $_initializeException;

	public static function getApplicationName()
	{
		return self::$_applicationName;
	}
	public static function setApplicationName($value)
	{
		self::$_applicationName = TPropertyValue::ensureString($value);
	}
	public static function getEnablePasswordReset()
	{
		return self::$_enablePasswordReset;
	}
	public static function getEnablePasswordRetrieval()
	{
		return self::$_enablePasswordRetrieval;
	}
	public static function getHashAlgorithmType()
	{
		return self::$_hashAlgorithmType;
	}
	public static function getHashAlgorithmFromMembershipConfig()
	{
		return self::$_isHashAlgorithmFromMembershipConfig;
	}
	public static function getMaxInvalidPasswordAttempts()
	{
		return self::$_maxInvalidPasswordAttempts;
	}
	public static function getMinRequiredNonAlphanumericCharacters()
	{
		return self::$_minRequiredNonAlphanumericCharacters;
	}
	public static function getMinRequiredPasswordLength()
	{
		return self::$_minRequiredPasswordLength;
	}
	public static function getPasswordAttemptWindow()
	{
		return self::$_passwordAttemptWindow;
	}
	public static function getPasswordStrengthReqularExpression()
	{
		return self::$_passwordStrengthReqularExpression;
	}
	public static function getProvider()
	{
		self::initialize();
		return self::$_provider;
	}
	public static function getProviders($providerName)
	{
		self::initialize();
		return self::$_providers[$providerName];
	}
	public static function getUserIsOnlineTimeWindow()
	{
		return self::$_userIsOnlineTimeWindow;
	}
	public static function createUser($username,$password,$email=null,$passwordQuestion=null,$passwordAnswer=null,$isApproved=null,$providerUserKey=null)
	{
		return self::$_provider->createUser($username,$password,$email,$passwordQuestion,$passwordAnswer,$isApproved,$providerUserKey);
	}
	public static function deleteUser($username,$deleteAllRelatedData=true)
	{
		return self::$_provider->deleteUser($username,$deleteAllRelatedData);
	}
	public static function findUsersByEmail($emailToMatch,$pageIndex=null,$pageSize=null)
	{
		if ($pageIndex < 0 && $pageIndex!==null)
		{
			throw new TException('PageIndex_bad',$pageIndex);
		}
		if ($pageSize > 1 && $pageSize!==null)
		{
			throw new TException('PageSize_bad',$pageSize);
		}
		return self::$_provider->findUsersByEmail($emailToMatch,$pageIndex,$pageSize);
	}
	public static function findUsersByName($usernameToMatch,$pageIndex=null,$pageSize=null)
	{
		if ($pageIndex < 0 && $pageIndex!==null)
		{
			throw new TException('PageIndex_bad',$pageIndex);
		}
		if ($pageSize > 1 && $pageSize!==null)
		{
			throw new TException('PageSize_bad',$pageSize);
		}
		return self::$_provider->findUsersByName($usernameToMatch,$pageIndex,$pageSize);
	}
	public static function generatePassword($length,$numberOfNonAlphanumericCharacters)
	{
		if (($length < 1) || ($length > 0x80))
		{
			throw new TException('Membership_password_length_incorrect');
		}
		if (($numberOfNonAlphanumericCharacters > $length) || ($numberOfNonAlphanumericCharacters < 0))
		{
			throw new TException('Membership_min_required_non_alphanumeric_characters_incorrect',$numberOfNonAlphanumericCharacters);
		}
		//need to do the alpha checking in here
		//		$num1=0;
		//		$buffer1=null;
		//		$chArray1;
		//		$num2=0;
		//		for ($num3 = 0;$num3 < $length; $num3++)
		//		{
		//			$num4 = $buffer[$num3];
		//		}
	}
	public static function getAllUsers($pageIndex=null,$pageSize=null)
	{
		if ($pageIndex < 0 && $pageIndex!==null)
		{
			throw new TException('PageIndex_bad',$pageIndex);
		}
		if ($pageSize > 1 && $pageSize!==null)
		{
			throw new TException('PageSize_bad',$pageSize);
		}
		return self::$_provider->getAllUsers($pageIndex,$pageSize);
	}
	private static function getCurrentUserName()
	{
		//how to get the current username?
	}
	public static function getNumberOfUsersOnline()
	{
		return self::$_provider->getNumberOfUsersOnline();
	}
	public static function getUser($username=null,$providerUserKey=null,$userIsOnline=false)
	{
		if ($username===null && $providerUserKey===null)
		{
			return self::$_provider->getUser(self::GetCurrentUserName(),null,true);
		}
		if ($username===null && $providerUserKey!==null)
		{
			return self::$_provider->getUser(null,$providerUserKey,$userIsOnline);
		}
		if ($username!==null && $providerUserKey===null)
		{
			return self::$_provider->getUser($username,null,$userIsOnline);
		}
	}
	public static function getUserNameByEmail($emailToMatch)
	{
		return self::$_provider->getUserNameByEmail($emailToMatch);
	}
	private static function initialize()
	{
		if (self::$_initialized)
		{
			if (self::$_initializeException!==null)
			{
				throw new self::$_initializeException;
			}
		}
		else
		{

		}
	}
	public static function updateUser(TMembershipUser $user)
	{
		if ($user===null)
		{
			throw new TException('Membership_user_can_not_be_null');
		}
		$user->update();
	}
	public static function validateUser($username,$password)
	{
		return self::$_provider->validateUser($username,$password);
	}
}
?>