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
	private abstract $_ApplicationName;
	protected function __construct()
	{
		
	}
	public abstract function getApplicationName();
	public abstract function setApplicationName($value);
	public abstract function AddUsersToRoles($usernames,$roleNames);
	public abstract function CreateRole($roleName);
	public abstract function DeleteRole($roleName);
	public abstract function FineUsersInRole($roleName,$usernameToMatch);
	public abstract function GetAllRoles();
	public abstract function GetRolesForUser($username);
	public abstract function GetUsersIsRole($username,$roleName);
	public abstract function IsUserIsRole($username,$roleName);
	public abstract function RemoveUsersFromRoles($usernames,$roleNames);
	public abstract function RoleExists($roleName);
}
?>