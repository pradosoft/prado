<?php
/**
 * TSqlRoleProvider class.
 * Defines the contract that PRADO implements to provide role-management services using custom role providers. 
 *
 * @author Jason Ragsdale <jrags@jasrags.net>
 * @version $Id: TSqlRoleProvider.php 1398 2006-09-08 19:31:03Z xue $
 * @package System.Web.Security
 * @since 3.1
 */
Prado::using('System.Web.Security.TRoleProvider');
class TSqlRoleProvider extends TRoleProvider 
{
	private $_connectionStringName;
	
	public function getConnectionStringName()
	{
		return $this->_connectionStringName;
	}
	public function setConnectionStringName($value)
	{
		$this->_connectionStringName = TPropertyValue::ensureString($value);
	}
	
	public function __construct()
	{
		
	}
	public function addUsersToRoles($usernames,$roleNames)
	{
		
	}
	public function createRole($roleName)
	{
		
	}
	public function deleteRole($roleName)
	{
		
	}
	public function findUsersInRole($roleName,$usernameToMatch)
	{
		
	}
	public function getAllRoles()
	{
		
	}
	public function getRolesForUser($username)
	{
		
	}
	public function getUsersIsRole($username,$roleName)
	{
		
	}
	public function isUserIsRole($username,$roleName)
	{
		
	}
	public function removeUsersFromRoles($usernames,$roleNames)
	{
		
	}
	public function roleExists($roleName)
	{
		
	}
}
?>