<?php
/**
 * TFormsAuthentication class.
 * Manages forms-authentication services for Web applications. This class cannot be inherited.
 *
 * @author Jason Ragsdale <jrags@jasrags.net>
 * @version $Id: TFormsAuthentication.php 1398 2006-09-08 19:31:03Z xue $
 * @package System.Web.Security
 * @since 3.1
 */
final class TFormsAuthentication
{
	private static $_cookieDomain;
	private static $_cookieMode;
	private static $_cookiesSupported;
	private static $_defaultUrl;
	private static $_enableCrossAppRedirects;
	private static $_formsCookieName;
	private static $_formsCookiePath;
	private static $_loginUrl;
	private static $_requireSSL;
	private static $_slidingExpiration;

	public static function getCookiePath()
	{
		return self::$_cookieDomain;
	}
	public static function setCookiePath($value)
	{
		self::$_cookieDomain = TPropertyValue::ensureString($value);
	}

	public function __construct()
	{

	}

	public static function Authenticate($name, $password)
	{

	}
	public static function Decrypt($encryptedTicket)
	{

	}
	public static function Encrypt($ticket, $hexEncodedTicket=null)
	{

	}
	public static function GetAuthCookie( $userName, $createPersistentCookie,  $strCookiePath=null,  $hexEncodedTicket=null)
	{

	}
	public static function GetLoginPage($extraQuery,$reuseReturnUrl=null)
	{

	}
	public static function GetRedirectUrl( $userName, $createPersistentCookie)
	{

	}
	public static function GetReturnUrl($useDefaultIfAbsent)
	{

	}
	public static function HashPasswordForStoringInConfigFile($password,  $passwordFormat)
	{

	}
	public static function Initialize()
	{

	}
	private static function ernalAuthenticate( $name,  $password)
	{

	}
	private static function IsPathWithinAppRoot($context,  $path)
	{

	}
	private static function MakeTicketoBinaryBlob($ticket)
	{

	}
	public static function RedirectFromLoginPage($userName, $createPersistentCookie,  $strCookiePath=null)
	{

	}
	public static function RedirectToLoginPage($extraQuery=null)
	{

	}
	private static function RemoveQSVar($ref,  $strUrl,  $posQ, $token,  $sep,  $lenAtStartToLeave)
	{

	}
	public static function RemoveQueryVariableFromUrl( $strUrl,  $QSVar)
	{

	}
	public static function RenewTicketIfOld($tOld)
	{

	}
	public static function SetAuthCookie( $userName, $createPersistentCookie, $strCookiePath=null)
	{

	}
	public static function SignOut()
	{

	}

	// Properties
	public static function getCookieDomain()
	{
		return self::$_cookieDomain;
	}
	public static function getCookieMode()
	{
		return self::$_cookieMode;
	}
	public static function getCookiesSupported()
	{
		return self::$_cookiesSupported;
	}
	public static function getDefaultUrl()
	{
		return self::$_defaultUrl;
	}
	public static function getEnableCrossAppRedirects()
	{
		return self::$_enableCrossAppRedirects;
	}
	public static function getFormsCookieName()
	{
		return self::$_formsCookieName;
	}
	public static function getFormsCookiePath()
	{
		return self::$_formsCookiePath;
	}
	public static function getLoginUrl()
	{
		return self::$_loginUrl;
	}
	public static function getRequireSSL()
	{
		return self::$_requireSSL;
	}
	public static function getSlidingExpiration()
	{
		return self::$_slidingExpiration;
	}

	// Fields
	//	private static $_CookieDomain;
	//	private static $_CookieMode;
	//	private static $_DefaultUrl;
	//	private static $_EnableCrossAppRedirects;
	//	private static $_FormsCookiePath;
	//	private static $_FormsName;
	//	private static $_Initialized;
	//	private static $_lockObject;
	//	private static $_LoginUrl;
	//	private static $_Protection;
	//	private static $_RequireSSL;
	//	private static $_SlidingExpiration;
	//	private static $_Timeout;
	//	private const  CONFIG_DEFAULT_COOKIE = ".ASPXAUTH";
	//	private const  MAC_LENGTH = 20;
	//	private const  MAX_TICKET_LENGTH = 0x1000;
	//	ernal const  RETURN_URL = "ReturnUrl";
}
?>