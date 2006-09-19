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
	private static $_ApplicationName;
	private static $_EnablePasswordReset=false;
	private static $_EnablePasswordRetrieval=false;
	private static $_HashAlgorithmType;
	private static $_IsHashAlgorithmFromMembershipConfig=false;
	private static $_MaxInvalidPasswordAttempts;
	private static $_MinRequiredNonAlphanumericCharacters;
	private static $_MinRequiredPasswordLength;
	private static $_PasswordAttemptWindow;
	private static $_PasswordStrengthReqularExpression;
	private static $_Provider;
	private static $_Providers;
	private static $_RequiresQuestionAndAnswer=false;
	private static $_UserIsOnlineTimeWindow=15;
	private static $_punctuations='!@#$%^&*()_-+=[{]};:>./?';
	private static $_HashAlgorithmFromConfig=false;
	private static $_Initialized=false;
	private static $_InitializeException;

	public static function getApplicationName()
	{
		return self::$_ApplicationName;
	}
	public static function setApplicationName($value)
	{
		self::$_ApplicationName = TPropertyValue::ensureString($value);
	}
	public static function getEnablePasswordReset()
	{
		return self::$_EnablePasswordReset;
	}
	public static function getEnablePasswordRetrieval()
	{
		return self::$_EnablePasswordRetrieval;
	}
	public static function getHashAlgorithmType()
	{
		return self::$_HashAlgorithmType;
	}
	public static function getHashAlgorithmFromMembershipConfig()
	{
		return self::$_IsHashAlgorithmFromMembershipConfig;
	}
	public static function getMaxInvalidPasswordAttempts()
	{
		return self::$_MaxInvalidPasswordAttempts;
	}
	public static function getMinRequiredNonAlphanumericCharacters()
	{
		return self::$_MinRequiredNonAlphanumericCharacters;
	}
	public static function getMinRequiredPasswordLength()
	{
		return self::$_MinRequiredPasswordLength;
	}
	public static function getPasswordAttemptWindow()
	{
		return self::$_PasswordAttemptWindow;
	}
	public static function getPasswordStrengthReqularExpression()
	{
		return self::$_PasswordStrengthReqularExpression;
	}
	public static function getProvider()
	{
		return self::$_Provider;
	}
	public static function getProviders()
	{
		return self::$_Providers;
	}
	public static function getUserIsOnlineTimeWindow()
	{
		return self::$_UserIsOnlineTimeWindow;
	}
	public static function CreateUser($username,$password,$email=null,$passwordQuestion=null,$passwordAnswer=null,$isApproved=null,$providerUserKey=null)
	{
		return self::$_Provider->CreateUser($username,$password,$email,$passwordQuestion,$passwordAnswer,$isApproved,$providerUserKey);
	}
	public static function DeleteUser($username,$deleteAllRelatedData=true)
	{
		return self::$_Provider->DeleteUser($username,$deleteAllRelatedData);
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
		return self::$_Provider->FindUsersByEmail($emailToMatch,$pageIndex,$pageSize);
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
		return self::$_Provider->FindUsersByName($usernameToMatch,$pageIndex,$pageSize);
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
		return self::$_Provider->GetAllUsers($pageIndex,$pageSize);
	}
	private static function GetCurrentUserName()
	{
		//how to get the current username?
	}
	public static function GetNumberOfUsersOnline()
	{
		return self::$_Provider->GetNumberOfUsersOnline();
	}
	public static function GetUser($username=null,$providerUserKey=null,$userIsOnline=false)
	{
		if ($username===null && $providerUserKey===null)
		{
			return self::$_Provider->GetUser(self::GetCurrentUserName(),null,true);
		}
		if ($username===null && $providerUserKey!==null)
		{
			return self::$_Provider->GetUser(null,$providerUserKey,$userIsOnline);
		}
		if ($username!==null && $providerUserKey===null)
		{
			return self::$_Provider->GetUser($username,null,$userIsOnline);
		}
	}
	public static function GetUserNameByEmail($emailToMatch)
	{
		return self::$_Provider->GetUserNameByEmail($emailToMatch);
	}
	private static function Initialize()
	{
		if (self::$__s_Initialized)
		{
			if (self::$__s_InitializeException!==null)
			{
				throw new self::$__s_InitializeException;
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
		return self::$_Provider->ValidateUser($username,$password);
	}
}
?>