<?php
/**
 * TGenericPrincipal class.
 * Represents a generic principal. 
 *
 * @author Jason Ragsdale <jrags@jasrags.net>
 * @version $Id: TGenericPrincipal.php 1398 2006-09-08 19:31:03Z xue $
 * @package System.Web.Security.Principal
 * @since 3.1
 */
Prado::using('System.Web.Security.Principal.IPrincipal');
class TGenericPrincipal implements IPrincipal
{
     private $_identity;
     
     public function getIdentity()
     {
     	return $this->_identity;
     }
     public function setIdentity($value)
     {
     	$this->_identity = TPropertyValue::ensureString($value);
     }
     
     public function __construct($name, $type=null)
     {
     	
     }
     public function isInRole($role)
     {
     	
     }
}
?>