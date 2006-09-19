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
Prado::using('System.Web.Security.');
final class TMembership
{
	public static $ApplicationName;
	public static $EnablePasswordReset=false;
	public static $EnablePasswordRetrieval=false;
	public static $HashAlgorithmType;
	public static $IsHashAlgorithmFromMembershipConfig=false;
	public static $MaxInvalidPasswordAttempts;
	public static $MinRequiredNonAlphanumericCharacters;
	public static $MinRequiredPasswordLength;
	public static $PasswordAttemptWindow;
	public static $PasswordStrengthReqularExpression;
	public static $Provider;
	public static $Providers;
	public static $RequiresQuestionAndAnswer=false;
	public static $UserIsOnlineTimeWindow;
	private static $_punctuations;
	private static $_s_HashAlgorithmFromConfig=false;
	private static $_s_HashAlgorithmType;
	private static $_s_Initialized=false;
	private static $_s_InitializeException;
	private static $_s_lock;
	private static $_s_Provider;
	private static $_s_Providers;
	private static $_s_UserIsOnlineTimeWindow;

	public static function __construct()
	{
		self::$_punctuations="!@#$%^&*()_-+=[{]};:>./?";
		self::$_s_UserIsOnlineTimeWindow=15;
		self::$_s_lock = new stdClass();
		self::$_s_Initialized=false;
		self::$_s_InitializeException=null;
	}
	public static function getApplicationName()
	{
		return self::$ApplicationName;
	}
	public static function setApplicationName($value)
	{
		self::$ApplicationName = TPropertyValue::ensureString($value);
	}
	public static function getEnablePasswordReset()
	{
		return self::$EnablePasswordReset;
	}
	public static function getEnablePasswordRetrieval()
	{
		return self::$EnablePasswordRetrieval;
	}
	public static function getHashAlgorithmType()
	{
		return self::$HashAlgorithmType;
	}
	public static function getHashAlgorithmFromMembershipConfig()
	{
		return self::$IsHashAlgorithmFromMembershipConfig;
	}
	public static function getMaxInvalidPasswordAttempts()
	{
		return self::$MaxInvalidPasswordAttempts;
	}
	public static function getMinRequiredNonAlphanumericCharacters()
	{
		return self::$MinRequiredNonAlphanumericCharacters;
	}
	public static function getMinRequiredPasswordLength()
	{
		return self::$MinRequiredPasswordLength;
	}
	public static function getPasswordAttemptWindow()
	{
		return self::$PasswordAttemptWindow;
	}
	public static function getPasswordStrengthReqularExpression()
	{
		return self::$PasswordStrengthReqularExpression;
	}
	public static function getProvider()
	{
		return self::$Provider;
	}
	public static function getProviders()
	{
		return self::$Providers;
	}
	public static function getUserIsOnlineTimeWindow()
	{
		return self::$UserIsOnlineTimeWindow;
	}
	public static function CreateUser($username,$password,$email=null,$passwordQuestion=null,$passwordAnswer=null,$isApproved=null,$providerUserKey=null)
	{
		return self::$Provider->CreateUser($username,$password,$email,$passwordQuestion,$passwordAnswer,$isApproved,$providerUserKey);
	}
	public static function DeleteUser($username,$deleteAllRelatedData=true)
	{
		return self::$Provider->DeleteUser($username,$deleteAllRelatedData);
	}
	public static function FindUsersByEmail($emailToMatch,$pageIndex=null,$pageSize=null)
	{
		if ($pageIndex < 0 && $pageIndex!==null)
		{
			throw new TException('PageIndex_bad',$pageIndex);
		}
		if ($pageSize > 1 && $pageSize!==null)
		{
			throw new TException('PageSize_bad',$pageSize);
		}
		return self::$Provider->FindUsersByEmail($emailToMatch,$pageIndex,$pageSize);
	}
	public static function FindUsersByName($usernameToMatch,$pageIndex=null,$pageSize=null)
	{
		if ($pageIndex < 0 && $pageIndex!==null)
		{
			throw new TException('PageIndex_bad',$pageIndex);
		}
		if ($pageSize > 1 && $pageSize!==null)
		{
			throw new TException('PageSize_bad',$pageSize);
		}
		return self::$Provider->FindUsersByName($usernameToMatch,$pageIndex,$pageSize);
	}
	public static function GeneratePassword($length,$numberOfNonAlphanumericCharacters)
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
	public static function GetAllUsers($pageIndex=null,$pageSize=null)
	{
		if ($pageIndex < 0 && $pageIndex!==null)
		{
			throw new TException('PageIndex_bad',$pageIndex);
		}
		if ($pageSize > 1 && $pageSize!==null)
		{
			throw new TException('PageSize_bad',$pageSize);
		}
		return self::$Provider->GetAllUsers($pageIndex,$pageSize);
	}
	private static function GetCurrentUserName()
	{
		//how to get the current username?
	}
	public static function GetNumberOfUsersOnline()
	{
		return self::$Provider->GetNumberOfUsersOnline();
	}
	public static function GetUser($username=null,$providerUserKey=null,$userIsOnline=false)
	{
		if ($username===null && $providerUserKey===null)
		{
			return self::$Provider->GetUser(self::GetCurrentUserName(),null,true);
		}
		if ($username===null && $providerUserKey!==null)
		{
			return self::$Provider->GetUser(null,$providerUserKey,$userIsOnline);
		}
		if ($username!==null && $providerUserKey===null)
		{
			return self::$Provider->GetUser($username,null,$userIsOnline);
		}
	}
	public static function GetUserNameByEmail($emailToMatch)
	{
		return self::$Provider->GetUserNameByEmail($emailToMatch);
	}
	private static function Initialize()
	{
		if (self::$_s_Initialized)
		{
			if (self::$_s_InitializeException!==null)
			{
				throw new self::$_s_InitializeException;
			}
		}
		else
		{

		}
	}
	public static function UpdateUser(TMembershipUser $user)
	{
		if ($user===null)
		{
			throw new TException('Membership_user_can_not_be_null');
		}
		$user->Update();
	}
	public static function ValidateUser($username,$password)
	{
		return self::$Provider->ValidateUser($username,$password);
	}
}
?>