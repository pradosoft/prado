<?php
/**
 * TRoles class.
 *  Manages user membership in roles for authorization checking in an PRADO application. This class cannot be inherited.
 *
 * @author Jason Ragsdale <jrags@jasrags.net>
 * @version $Id: TRoles.php 1398 2006-09-08 19:31:03Z xue $
 * @package System.Web.Security
 * @since 3.1
 */
Prado::using('System.Web.Security.TProviderException');
final class TRoles
{
	private static $_applicationName;
	private static $_cacheRolesInCookie=false;
	private static $_cookieName;
	private static $_cookiePath;
	private static $_cookieProtectionValue;
	private static $_cookieRequireSSL=false;
	private static $_cookieSlidingExpiration=false;
	private static $_cookieTimeout;
	private static $_createPersistentCookie=false;
	private static $_domain;
	private static $_enabled=false;
	private static $_maxCachedResults;
	private static $_provider;
	private static $_providers;
	private static $_enabledSet=false;
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
	public static function getCacheRolesInCookie()
	{
		return self::$_cacheRolesInCookie;
	}
	public static function getCookieName()
	{
		return self::$_cookieName;
	}
	public static function getCookiePath()
	{
		return self::$_cookiePath;
	}
	public static function getCookieProtectionValue()
	{
		return self::$_cookieProtectionValue;
	}
	public static function getCookieRequireSSL()
	{
		return self::$_cookieRequireSSL;
	}
	public static function getCookieSlidingExpiration()
	{
		return self::$_cookieSlidingExpiration;
	}
	public static function getCookieTimeout()
	{
		return self::$_cookieTimeout;
	}
	public static function getCreatePersistentCookie()
	{
		return self::$_createPersistentCookie;
	}
	public static function getDomain()
	{
		return self::$_domain;
	}
	public static function getEnabled()
	{
		return self::$_enabled;
	}
	public static function getMaxCachedResults()
	{
		return self::$_maxCachedResults;
	}
	public static function getProvider()
	{
		return self::$_provider;
	}
	public static function getProviders()
	{
		return self::$_providers;
	}

	public static function addUsersToRole($usernames,$roleName)
	{

	}
	public static function addUsersToRoles($usernames,$roleNames)
	{

	}
	public static function addUserToRole($username,$roleName)
	{

	}
	public static function addUserToRoles($username,$roleNames)
	{

	}
	public static function createRole($roleName)
	{
		self::ensureEnabled();
		self::$_provider->createRole($roleName);
	}
	public static function deleteCookie()
	{

	}
	public static function deleteRole($roleName,$throwOnPopulatedRole=true)
	{
		self::ensureEnabled();

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
	private static function ensureEnabled()
	{
		self::initialize();
		if (!self::$_Initialized)
		{
			throw new TProviderException('Roles_feature_not_enabled');
		}
	}
	public static function findUsersInRole($roleName,$usernameToMatch)
	{

	}
	public static function getAllRoles()
	{

	}
	private static function getCurrentUser()
	{

	}
	private static function getCurrentUserName()
	{

	}
	public static function getRolesForUser($username=null)
	{

	}
	public static function getUsersInRole($roleName)
	{

	}
	private static function initialize()
	{
		if (self::$_initialized)
		{
			if (self::$_initializeException!==null)
			{
				throw new $_initializeException;
			}
		}
		else
		{
			if (self::$_initialized)
			{
				if (self::$_initializeException!==null)
				{
					throw new $_initializeException;
				}
				return;
			}
			try 
			{
				self::$_enabled;
				self::$_cookieName;
				self::$_cookiePath;
				self::$_cacheRolesInCookie;
				self::$_cookieTimeout;
				self::$_cookiePath;
				self::$_cookieRequireSSL;
				self::$_cookieSlidingExpiration;
				self::$_cookieProtectionValue;
				self::$_domain;
				self::$_createPersistentCookie;
				self::$_maxCachedResults;
				if (self::$_enabled)
				{
					if (self::$_maxCachedResults < 0)
					{
						throw new TProviderException('Value_must_be_non_negative_integer',self::$_MaxCachedResults);
					}////stopped here
				}
			}
			catch (TException $e)
			{
				
			}
		}
	}
	public static function isUserInRole($roleName,$username=null)
	{

	}
	public static function removeUserFromRole($username,$roleName)
	{

	}
	public static function remoreUserFromRoles($username,$roleNames)
	{

	}
	public static function removeUsersFromRole($usernames,$roleName)
	{

	}
	public static function removeUsersFromRoles($usernames,$roleNames)
	{

	}
	public static function roleExists($roleName)
	{

	}
}
?>