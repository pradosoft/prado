<?php
/**
 * TFormsIdentity class.
 * Represents a user identity authenticated using forms authentication. This class cannot be inherited.
 *
 * @author Jason Ragsdale <jrags@jasrags.net>
 * @version $Id: TFormsIdentity.php 1398 2006-09-08 19:31:03Z xue $
 * @package System.Web.Security
 * @since 3.1
 */
Prado::using('System.Web.Security.Principal.IIdentity');
final class TFormsIdentity implements IIdentity
{
	private $_authenticationType;
	private $_isAuthenticated=false;
	private $_name;
	private $_ticket;
	
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
	public function getTicket()
	{
		return $this->_ticket;
	}
	public function setTicket($value)
	{
		$this->_ticket = TPropertyValue::ensureString($value);
	}
	
	public function __construct()
	{
		
	}
}
//public sealed class FormsIdentity : IIdentity
//{
//     // Methods
//     public FormsIdentity(FormsAuthenticationTicket ticket);
//
//     // Properties
//     public string AuthenticationType { get; }
//     public bool IsAuthenticated { get; }
//     public string Name { get; }
//     public FormsAuthenticationTicket Ticket { get; }
//
//     // Fields
//     private FormsAuthenticationTicket _Ticket;
//}
?>