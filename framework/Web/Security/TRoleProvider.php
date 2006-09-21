<?php
/**
 * TRoleProvider class.
 * Defines the contract that PRADO implements to provide role-management services using custom role providers. 
 *
 * @author Jason Ragsdale <jrags@jasrags.net>
 * @version $Id: TRoleProvider.php 1398 2006-09-08 19:31:03Z xue $
 * @package System.Web.Security
 * @since 3.1
 */
Prado::using('System.Configuration.Provider.TProviderBase');
abstract class TRoleProvider extends TProviderBase 
{
	private $_cacheRolesInCookie=false;
    private $_cookieName="PRADO";
    private $_cookieTimeout="30";
    private $_cookiePath="/";
    private $_cookieRequireSSL=false;
    private $_cookieSlidingExpiration=true;
	
	public function getCacheRolesInCookie()
	{
		return $this->_cacheRolesInCookie;
	}
	public function setCacheRolesInCookie($value)
	{
		$this->_cacheRolesInCookie = TPropertyValue::ensureBoolean($value);
	}
	public function getCookieName()
	{
		return $this->_cookieName;
	}
	public function setCookieName($value)
	{
		$this->_cookieName = TPropertyValue::ensureString($value);
	}
	public function getCookiePath()
	{
		return $this->_cookiePath;
	}
	public function setCookiePath($value)
	{
		$this->_cookiePath = TPropertyValue::ensureString($value);
	}
	public function getCookieRequireSSL()
	{
		return $this->_cookieRequireSSL;
	}
	public function setCookieRequireSSL($value)
	{
		$this->_cookieRequireSSL = TPropertyValue::ensureBoolean($value);
	}
	public function getCookieSlidingExpiration()
	{
		return $this->_cookieSlidingExpiration;
	}
	public function setCookieSlidingExpiration($value)
	{
		$this->_cookieSlidingExpiration = TPropertyValue::ensureBoolean($value);
	}
	public function getCookieTimeout()
	{
		return $this->_cookieTimeout;
	}
	public function setCookieTimeout($value)
	{
		$this->_cookieTimeout = TPropertyValue::ensureInteger($value);
	}
	
	
	public function __construct()
	{
		
	}
	public abstract function addUsersToRoles($usernames,$roleNames);
	public abstract function createRole($roleName);
	public abstract function deleteRole($roleName);
	public abstract function findUsersInRole($roleName,$usernameToMatch);
	public abstract function getAllRoles();
	public abstract function getRolesForUser($username);
	public abstract function getUsersIsRole($username,$roleName);
	public abstract function isUserIsRole($username,$roleName);
	public abstract function removeUsersFromRoles($usernames,$roleNames);
	public abstract function roleExists($roleName);
}
?>