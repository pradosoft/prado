<?php
/**
 * TAuthorizationStoreRoleProvider class.
 * Manages storage of role-membership information for an PRADO application in an authorization-manager policy store, in an XML file.
 *
 * @author Jason Ragsdale <jrags@jasrags.net>
 * @version $Id: TAuthorizationStoreRoleProvider.php 1398 2006-09-08 19:31:03Z xue $
 * @package System.Web.Security
 * @since 3.1
 */
Prado::using('System.Web.Security.TRoleProvider');
class TAuthorizationStoreRoleProvider extends TRoleProvider 
{
	private $_ApplicationName;
	public function __construct()
	{
		
	}
	public function getApplicationName()
	{
		return $this->_ApplicationName;
	}
	public function setApplicationName($value)
	{
		$this->_ApplicationName = TPropertyValue::ensureString($value);
	}
	public function AddUsersToRoles($usernames,$roleNames)
	{
		
	}
	public function CreateRole($roleName)
	{
		
	}
	public function DeleteRole($roleName)
	{
		
	}
	public function FineUsersInRole($roleName,$usernameToMatch)
	{
		
	}
	public function GetAllRoles()
	{
		
	}
	public function GetRolesForUser($username)
	{
		
	}
	public function GetUsersIsRole($username,$roleName)
	{
		
	}
	public function IsUserIsRole($username,$roleName)
	{
		
	}
	public function RemoveUsersFromRoles($usernames,$roleNames)
	{
		
	}
	public function RoleExists($roleName)
	{
		
	}
}
?>