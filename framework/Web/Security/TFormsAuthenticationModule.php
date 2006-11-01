<?php
/**
 * TFormsAuthenticationModule class.
 * Sets the identity of the user for an PRADO application when forms authentication is enabled. 
 * This class cannot be inherited.
 *
 * @author Jason Ragsdale <jrags@jasrags.net>
 * @version $Id: TFormsAuthenticationModule.php 1398 2006-09-08 19:31:03Z xue $
 * @package System.Web.Security
 * @since 3.1
 */
final class TFormsAuthenticationModule extends TModule
{
	/**
	 * @var boolean if the module has been initialized
	 */
	private $_initialized=false;
	
	private static $_fAuthChecked=false;
	private static $_fAuthRequired=false;
	private $_fFormsInit;
	private $_formsName;
	private $_loginUrl;
	const CONFIG_DEFAULT_COOKIE = ".ASPXAUTH";
	const CONFIG_DEFAULT_LOGINURL = "login.aspx";

	//Is this the best way to do it?? i dont see how the forms module knows about the provider
	private $_defaultProvider;
	
	public function getDefaultProvider()
	{
		return $this->_defaultProvider;
	}
	public function setDefaultProvider($value)
	{
		$this->_defaultProvider = TPropertyValue::ensureString($value);
	}
	
	public function __construct()
	{

	}
	/**
	 * Initializes this module.
	 * This method is required by the IModule interface.
	 * @param TXmlElement configuration for this module, can be null
	 * @throws TConfigurationException if user manager does not exist or is not IUserManager
	 */
	public function init($config)
	{
		$this->getApplication()->attachEventHandler('OnAuthentication',array($this,'doAuthentication'));
		$this->getApplication()->attachEventHandler('OnEndRequest',array($this,'leave'));
		$this->getApplication()->attachEventHandler('OnAuthorization',array($this,'doAuthorization'));
		$this->_initialized=true;
	}
	
	private static function extractTicketFromCookie($context, $name)
	{

	}
	/**
	 * Performs authentication.
	 * This is the event handler attached to application's Authentication event.
	 * Do not call this method directly.
	 * @param mixed sender of the Authentication event
	 * @param mixed event parameter
	 */
	public function doAuthentication($sender,$param)
	{
		Prado::using('System.Util.TVarDumper');
//		echo TVarDumper::dump(__METHOD__,10,true);
	}
	/**
	 * Performs login redirect if authorization fails.
	 * This is the event handler attached to application's EndRequest event.
	 * Do not call this method directly.
	 * @param mixed sender of the event
	 * @param mixed event parameter
	 */
	public function leave($sender,$param)
	{
		Prado::using('System.Util.TVarDumper');
//		echo TVarDumper::dump(__METHOD__,10,true);
	}
	/**
	 * Performs authorization.
	 * This is the event handler attached to application's Authorization event.
	 * Do not call this method directly.
	 * @param mixed sender of the Authorization event
	 * @param mixed event parameter
	 */
	public function doAuthorization($sender,$param)
	{
		Prado::using('System.Util.TVarDumper');
//		echo TVarDumper::dump(__METHOD__,10,true);
	}
}
//public sealed class FormsAuthenticationModule : IHttpModule
//{
//     // Events
//     public event FormsAuthenticationEventHandler Authenticate;
//
//     // Methods
//     [SecurityPermission(SecurityAction.Demand, Unrestricted=true)]
//     public FormsAuthenticationModule();
//     public void Dispose();
//     private static FormsAuthenticationTicket
//ExtractTicketFromCookie(HttpContext context, string name, out bool
//cookielessTicket);
//     public void Init(HttpApplication app);
//     private void OnAuthenticate(FormsAuthenticationEventArgs e);
//     private void OnEnter(object source, EventArgs eventArgs);
//     private void OnLeave(object source, EventArgs eventArgs);
//     private static void Trace(string str);
//
//     // Fields
//     private FormsAuthenticationEventHandler _eventHandler;
//     private static bool _fAuthChecked;
//     private static bool _fAuthRequired;
//     private bool _fFormsInit;
//     private string _FormsName;
//     private string _LoginUrl;
//     private const string CONFIG_DEFAULT_COOKIE = ".ASPXAUTH";
//     private const string CONFIG_DEFAULT_LOGINURL = "login.aspx";
//}
?>