<?php
/**
 * TGenericIdentity class.
 * Represents a generic user.
 *
 * @author Jason Ragsdale <jrags@jasrags.net>
 * @version $Id: TGenericIdentity.php 1398 2006-09-08 19:31:03Z xue $
 * @package System.Web.Security.Principal
 * @since 3.1
 */
Prado::using('System.Web.Security.Principal.IIdentity');
class TGenericIdentity implements IIdentity
{
	 private $_authenticationType;
     private $_isAuthenticated;
     private $_name;
     
     public function getAuthenticationType()
     {
     	return $this->_authenticationType;
     }
     public function setAuthenticationType($value)
     {
     	$this->_authenticationType = TPropertyValue::ensureString($value);
     }
     public function getIsAuthenticated()
     {
     	return $this->_isAuthenticated;
     }
     public function setIsAuthenticated($value)
     {
     	$this->_isAuthenticated = TPropertyValue::ensureBoolean($value);
     }
     public function getName()
     {
     	return $this->_name;
     }
     public function setName($value)
     {
     	$this->_name = TPropertyValue::ensureString($value);
     }
     
     public function __construct($name, $type=null)
     {
     	
     }
}
?>