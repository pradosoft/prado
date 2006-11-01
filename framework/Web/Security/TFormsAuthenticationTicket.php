<?php
/**
 * TFormsAuthenticationTicket class.
 * Provides access to properties and values of the ticket used with forms 
 * authentication to identify users. This class cannot be inherited.
 *
 * @author Jason Ragsdale <jrags@jasrags.net>
 * @version $Id: TFormsAuthenticationTicket.php 1398 2006-09-08 19:31:03Z xue $
 * @package System.Web.Security
 * @since 3.1
 */
final class TFormsAuthenticationTicket
{
	private $_cookiePath;
	private $_expiration;
	private $_expired;
	private $_isPersistent;
	private $_issueDate;
	private $_name;
	private $_userData;
	private $_version;

	public function getCookiePath()
	{
		return $this->_cookiePath;
	}
	public function setCookiePath($value)
	{
		$this->_cookiePath = TPropertyValue::ensureString($value);
	}
	public function getExpiration()
	{
		return $this->_expiration;
	}
	public function setExpiration($value)
	{
		$this->_expiration = TPropertyValue::ensureString($value);
	}
	public function getExpired()
	{
		return $this->_expired;
	}
	public function setExpired($value)
	{
		$this->_expired = TPropertyValue::ensureString($value);
	}
	public function getIsPersistent()
	{
		return $this->_isPersistent;
	}
	public function setIsPersistent($value)
	{
		$this->_isPersistent = TPropertyValue::ensureString($value);
	}
	public function getIssueDate()
	{
		return $this->_issueDate;
	}
	public function setIssueDate($value)
	{
		$this->_issueDate = TPropertyValue::ensureString($value);
	}
	public function getName()
	{
		return $this->_name;
	}
	public function setName($value)
	{
		$this->_name = TPropertyValue::ensureString($value);
	}
	public function getUserData()
	{
		return $this->_userData;
	}
	public function setUserData($value)
	{
		$this->_userData = TPropertyValue::ensureString($value);
	}
	public function getVersion()
	{
		return $this->_version;
	}
	public function setVersion($value)
	{
		$this->_version = TPropertyValue::ensureString($value);
	}
	
	public function __construct()
	{

	}
}
//public sealed class FormsAuthenticationTicket
//{
//     // Methods
//     public FormsAuthenticationTicket(string name, bool isPersistent,
//int timeout);
//     public FormsAuthenticationTicket(int version, string name,
//DateTime issueDate, DateTime expiration, bool isPersistent, string
//userData);
//     public FormsAuthenticationTicket(int version, string name,
//DateTime issueDate, DateTime expiration, bool isPersistent, string
//userData, string cookiePath);
//
//     // Properties
//     public string CookiePath { get; }
//     public DateTime Expiration { get; }
//     public bool Expired { get; }
//     public bool IsPersistent { get; }
//     public DateTime IssueDate { get; }
//     public string Name { get; }
//     public string UserData { get; }
//     public int Version { get; }
//
//     // Fields
//     private string _CookiePath;
//     private DateTime _Expiration;
//     private bool _IsPersistent;
//     private DateTime _IssueDate;
//     private string _Name;
//     private string _UserData;
//     private int _Version;
//}
?>