<?php
/**
 * TCookieProtection class.
 * Describes how information in a cookie is protected.
 * 
 * All			Use both Validation and Encryption to protect the information 
 * 				in the cookie. 
 * Encryption	Encrypt the information in the cookie. 
 * None			Do not protect information in the cookie. Information in the 
 * 				cookie is stored in clear text and not validated when sent back 
 * 				to the server. 
 * Validation	Ensure that the information in the cookie has not been altered 
 * 				before being sent back to the server. 
 *
 * @author Jason Ragsdale <jrags@jasrags.net>
 * @version $Id: TCookieProtection.php 1398 2006-09-08 19:31:03Z xue $
 * @package System.Web.Security
 * @since 3.1
 */
class TCookieProtection extends TEnumerable 
{
	const All='All';
	const Encryption='Encryption';
	const None='None';
	const Validation='Validation';
}
?>