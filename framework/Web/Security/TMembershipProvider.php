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
	public abstract $ApplicationName;
	public abstract $EnablePasswordReset=false;
	public abstract $EnablePasswordRetrieval=false;
	public abstract $MaxInvalidPasswordAttempts;
	public abstract $MinRequiredNonAlphanumericCharacters;
	public abstract $MinRequiredPasswordLength;
	public abstract $PasswordAttemptWindow;
	public abstract $PasswordStrengthReqularExpression;
	public abstract $RequiresQuestionAndAnswer=false;
	public abstract $RequiresUniqueEmail=false;
	//	private const SALT_SIZE_IN_BYTES = 0x10;
	
	protected function __construct()
	{
		
	}
	public abstract function ChangePassword($username,$oldPassword,$newPassword);
	public abstract function ChangePasswordQuestionAndAnswer($username,$password,$newPasswordQuestion,$newPasswordAnswer);
	public abstract function CreateUser($username,$password,$email,$passwordQuestion,$passwordAnswer,$isApproved,$providerUserKey);
	protected function DecryptPassword($encodedPassword)
	{
		
	}
	public abstract function DeleteUser($username,$deleteAllRelatedData);
	public function EncodePassword($pass,$passwordFormat,$salt)
	{
		
	}
	protected function EncryptPassword($password)
	{
		
	}
	public abstract function FindUsersByEmail($emailToMatch,$pageIndex=null,$pageSize=null);
	public abstract function FindUsersByName($usernameToMatch,$pageIndex=null,$pageSize=null);
	public function GenerateSalt()
	{
		
	}
	public abstract function GetAllUsers($pageIndex=null,$pageSize=null);
	public abstract function GetNumberOfUsersOnline();
	public abstract function GetPassword($username,$answer);
	public abstract function GetUser($username=null,$providerUserKey=null,$userIsOnline);
	public abstract function GetUserNameByEmail($email);
	public abstract function ResetPassword($username,$answer);
	public function UnEncodePassword($pass,$passwordFormat)
	{
		
	}
	public abstract function UnlockUser($userName);
	public abstract function UpdateUser(TMembershipUser $user);
	public abstract function ValidateUser($username,$password);
}
?>