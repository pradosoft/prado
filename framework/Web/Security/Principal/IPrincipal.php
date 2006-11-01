<?php
/**
 * IPrincipal interface.
 * Defines the basic functionality of an identity object.
 *
 * @author Jason Ragsdale <jrags@jasrags.net>
 * @version $Id: IIdentity.php 1398 2006-09-08 19:31:03Z xue $
 * @package System.Web.Security.Principal
 * @since 3.1
 */
interface IPrincipal
{
	 private $_identity;
     
     public function getIdentity();
     public function setIdentity($value);
     public function isInRole($role);
}
?>