<?php
final class TRoles
{
	private static $_ApplicationName;
	private static $_CacheRolesInCookie=false;
	private static $_CookieName;
	private static $_CookiePath;
	private static $_CookieProtectionValue;
	private static $_CookieRequireSSL=false;
	private static $_CookieSlidingExpiration=false;
	private static $_CookieTimeout;
	private static $_CreatePersistentCookie=false;
	private static $_Domain;
	private static $_Enabled=false;
	private static $_MaxCachedResults;
	private static $_Provider;
	private static $_Providers;
	private static $_s_EnabledSet=false;
	private static $_s_Initialized=false;
	private static $_s_InitializeException;
	private static $_s_lock;

	public static function getApplicationName()
	{
		return self::$_ApplicationName;
	}
	public static function setApplicationName($value)
	{
		self::$_ApplicationName = TPropertyValue::ensureString($value);
	}
	public static function getCacheRolesInCookie()
	{
		return self::$_CacheRolesInCookie;
	}
	public static function getCookieName()
	{
		return self::$_CookieName;
	}
	public static function getCookiePath()
	{
		return self::$_CookiePath;
	}
	public static function getCookieProtectionValue()
	{
		return self::$_CookieProtectionValue;
	}
	public static function getCookieRequireSSL()
	{
		return self::$_CookieRequireSSL;
	}
	public static function getCookieSlidingExpiration()
	{
		return self::$_CookieSlidingExpiration;
	}
	public static function getCookieTimeout()
	{
		return self::$_CookieTimeout;
	}
	public static function getCreatePersistentCookie()
	{
		return self::$_CreatePersistentCookie;
	}
	public static function getDomain()
	{
		return self::$_Domain;
	}
	public static function getEnabled()
	{
		return self::$_Enabled;
	}
	public static function getMaxCachedResults()
	{
		return self::$_MaxCachedResults;
	}
	public static function getProvider()
	{
		return self::$_Provider;
	}
	public static function getProviders()
	{
		return self::$_Providers;
	}

	public static function AddUsersToRole($usernames,$roleName)
	{

	}
	public static function AddUsersToRoles($usernames,$roleNames)
	{

	}
	public static function AddUserToRole($username,$roleName)
	{

	}
	public static function AddUserToRoles($username,$roleNames)
	{

	}
	public static function CreateRole($roleName)
	{
		self::EnsureEnabled();
		self::$_Provider->CreateRole($roleName);
	}
	public static function DeleteCookie()
	{

	}
	public static function DeleteRole($roleName,$throwOnPopulatedRole=true)
	{
		self::EnsureEnabled();

		//		$flag1 = self::$_Provider->DeleteRole($roleName,$throwOnPopulatedRole);
		//		try
		//		{
		//			$principal1 = self::GetCurrentUser();
		//		}
		//		catch ()
		//		{
		//
		//		}

	}
	private static function EnsureEnabled()
	{
		self::Initialize();
		if (!self::$_s_Initialized)
		{
			throw new TException('Roles_feature_not_enabled');
		}
	}
	public static function FindUsersInRole($roleName,$usernameToMatch)
	{

	}
	public static function GetAllRoles()
	{

	}
	private static function GetCurrentUser()
	{

	}
	private static function GetCurrentUserName()
	{

	}
	public static function GetRolesForUser($username=null)
	{

	}
	public static function GetUsersInRole($roleName)
	{

	}
	private static function Initialize()
	{
		if (self::$_s_Initialized)
		{
			if (self::$_s_InitializeException!==null)
			{
				throw new $_s_InitializeException;
			}
		}
		else
		{
			if (self::$_s_Initialized)
			{
				if (self::$_s_InitializeException!==null)
				{
					throw new $_s_InitializeException;
				}
				return;
			}
			try 
			{
				self::$_Enabled;
				self::$_CookieName;
				self::$_CookiePath;
				self::$_CacheRolesInCookie;
				self::$_CookieTimeout;
				self::$_CookiePath;
				self::$_CookieRequireSSL;
				self::$_CookieSlidingExpiration;
				self::$_CookieProtectionValue;
				self::$_Domain;
				self::$_CreatePersistentCookie;
				self::$_MaxCachedResults;
				if (self::$_Enabled)
				{
					if (self::$_MaxCachedResults < 0)
					{
						throw new TException('Value_must_be_non_negative_integer',self::$_MaxCachedResults);
					}////stopped here
				}
			}
			catch (TException $e)
			{
				
			}
		}
	}
	public static function IsUserInRole($roleName,$username=null)
	{

	}
	public static function RemoveUserFromRole($username,$roleName)
	{

	}
	public static function RemoreUserFromRoles($username,$roleNames)
	{

	}
	public static function RemoveUsersFromRole($usernames,$roleName)
	{

	}
	public static function RemoveUsersFromRoles($usernames,$roleNames)
	{

	}
	public static function RoleExists($roleName)
	{

	}
}
?>