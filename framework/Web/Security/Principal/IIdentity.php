<?php
/**
 * IIdentity interface.
 * Defines the basic functionality of a principal object.
 *
 * @author Jason Ragsdale <jrags@jasrags.net>
 * @version $Id: IIdentity.php 1398 2006-09-08 19:31:03Z xue $
 * @package System.Web.Security.Principal
 * @since 3.1
 */
interface IIdentity
{
	private $_authenticationType;
	private $_isAuthenticated;
	private $_name;

	public function getAuthenticationType();
	public function setAuthenticationType($value);
	public function getIsAuthenticated();
	public function setIsAuthenticated($value);
	public function getName();
	public function setName($value);
}
?>