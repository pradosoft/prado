<?php
/**
 * TMembershipCreateStatus class.
 * Describes the result of a CreateUser operation.
 * 
 * DuplicateEmail			The e-mail address already exists in the database for the application. 
 * DuplicateProviderUserKey	The provider user key already exists in the database for the application. 
 * DuplicateUserName		The user name already exists in the database for the application. 
 * InvalidAnswer			The password answer is not formatted correctly. 
 * InvalidEmail				The e-mail address is not formatted correctly. 
 * InvalidPassword			The password is not formatted correctly. 
 * InvalidProviderUserKey	The provider user key is of an invalid type or format. 
 * InvalidQuestion			The password question is not formatted correctly. 
 * InvalidUserName			The user name was not found in the database. 
 * ProviderError			The provider returned an error that is not described by other MembershipCreateStatus enumeration values. 
 * Success					The user was successfully created. 
 * UserRejected				The user was not created, for a reason defined by the provider. 
 *
 * @author Jason Ragsdale <jrags@jasrags.net>
 * @version $Id: TMembershipCreateStatus.php 1398 2006-09-08 19:31:03Z xue $
 * @package System.Web.Security
 * @since 3.1
 */
class TMembershipCreateStatus extends TEnumerable 
{
	const DuplicateEmail='DuplicateEmail';
	const DuplicateProviderUserKey='DuplicateProviderUserKey';
	const DuplicateUserName='DuplicateUserName';
	const InvalidAnswer='InvalidAnswer';
	const InvalidEmail='InvalidEmail';
	const InvalidPassword='InvalidPassword';
	const InvalidProviderUserKey='InvalidProviderUserKey';
	const InvalidQuestion='InvalidQuestion';
	const InvalidUserName='InvalidUserName';
	const ProviderError='ProviderError';
	const Success='Success';
	const UserRejected='UserRejected';
}
?>