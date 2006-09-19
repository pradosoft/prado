<?php
/**
 * TMembershipPasswordFormat class.
 * Describes the encryption format for storing passwords for membership users.
 * 
 * Clear		Passwords are not encrypted. 
 * Encrypted	Passwords are encrypted using the encryption settings determined by the 
 * 				machineKey Element element configuration. 
 * Hashed		Passwords are encrypted one-way using the SHA1 hashing algorithm. 
 * 				You can specify a hashing algorithm different than the SHA1 
 * 				algorithm using the hashAlgorithmType attribute.
 *
 * @author Jason Ragsdale <jrags@jasrags.net>
 * @version $Id: TMembershipPasswordFormat.php 1398 2006-09-08 19:31:03Z xue $
 * @package System.Web.Security
 * @since 3.1
 */
class TMembershipPasswordFormat extends TEnumerable 
{
	const Clear='Clear';
	const Encrypted='Encrypted';
	const Hashed='Hashed';
}
?>