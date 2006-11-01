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

	public static Authenticate($name, $password);
	public static Decrypt($encryptedTicket);
	public static Encrypt($ticket);
	private static Encrypt($ticket, $hexEncodedTicket);
	public static GetAuthCookie( $$userName, $createPersistentCookie);
	public static GetAuthCookie( $$userName, $createPersistentCookie,  strCookiePath);
	private static GetAuthCookie( $userName, $createPersistentCookie,  strCookiePath,  hexEncodedTicket);
	public static GetLoginPage( $extraQuery);
	public static GetLoginPage( $extraQuery, $reuseReturnUrl);
	public static GetRedirectUrl( $userName, $createPersistentCookie);
	public static GetReturnUrl( $useDefaultIfAbsent);
	public static HashPasswordForStoringInConfigFile($password,  $passwordFormat);
	public static Initialize();
	private static  ernalAuthenticate( $name,  $password);
	private static IsPathWithinAppRoot($context,  $path);
	private static MakeTicketoBinaryBlob($ticket);
	public static  RedirectFromLoginPage($userName, $createPersistentCookie);
	public static  RedirectFromLoginPage($userName, $createPersistentCookie,  $strCookiePath);
	public static  RedirectToLoginPage();
	public static  RedirectToLoginPage($extraQuery);
	private static RemoveQSVar($ref  $strUrl,  $posQ, $token,  $sep,  $lenAtStartToLeave);
	public static  RemoveQueryVariableFromUrl( $strUrl,  $QSVar);
	public static RenewTicketIfOld($tOld);
	public static  SetAuthCookie( $userName, $createPersistentCookie);
	public static  SetAuthCookie( $userName, $createPersistentCookie,  $strCookiePath);
	public static  SignOut();

	// Properties
	public static  CookieDomain { get; }
	public static HttpCookieMode CookieMode { get; }
	public static  CookiesSupported { get; }
	public static  DefaultUrl { get; }
	public static  EnableCrossAppRedirects { get; }
	public static  FormsCookieName { get; }
	public static  FormsCookiePath { get; }
	public static  LoginUrl { get; }
	public static  RequireSSL { get; }
	public static  SlidingExpiration { get; }

	// Fields
	private static  _CookieDomain;
	private static HttpCookieMode _CookieMode;
	private static  _DefaultUrl;
	private static  _EnableCrossAppRedirects;
	private static  _FormsCookiePath;
	private static  _FormsName;
	private static  _Initialized;
	private static object _lockObject;
	private static  _LoginUrl;
	private static FormsProtectionEnum _Protection;
	private static  _RequireSSL;
	private static  _SlidingExpiration;
	private static  _Timeout;
	private const  CONFIG_DEFAULT_COOKIE = ".ASPXAUTH";
	private const  MAC_LENGTH = 20;
	private const  MAX_TICKET_LENGTH = 0x1000;
	ernal const  RETURN_URL = "ReturnUrl";
}
?>