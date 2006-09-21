<?php
Prado::using('System.Web.Security.TMembershipProvider');
class TSqlMembershipProvider extends TMembershipProvider
{
	private $_connectionStringName;

	public function getConnectionStringName()
	{
		return $this->_connectionStringName;
	}
	public function setConnectionStringName($value)
	{
		$this->_connectionStringName = TPropertyValue::ensureString($value);
	}


	public function __construct()
	{

	}
	public function changePassword($username,$oldPassword,$newPassword)
	{

	}
	public function changePasswordQuestionAndAnswer($username,$password,$newPasswordQuestion,$newPasswordAnswer)
	{

	}
	public function createUser($username,$password,$email,$passwordQuestion,$passwordAnswer,$isApproved,$providerUserKey)
	{

	}
	public function deleteUser($username,$deleteAllRelatedData)
	{

	}
	public function findUsersByEmail($emailToMatch,$pageIndex=null,$pageSize=null)
	{

	}
	public function findUsersByName($usernameToMatch,$pageIndex=null,$pageSize=null)
	{

	}
	public function getAllUsers($pageIndex=null,$pageSize=null)
	{

	}
	public function getNumberOfUsersOnline()
	{

	}
	public function getPassword($username,$answer)
	{

	}
	public function getMembershipUser($username=null,$providerUserKey=null,$userIsOnline=false)
	{
		Prado::using('System.Web.Security.TMembershipUser');
//		return new TMembershipUser($this->getID());
	}
	public function getUserNameByEmail($email)
	{

	}
	public function resetPassword($username,$answer)
	{

	}
	public function unlockUser($userName)
	{

	}
	public function updateUser(TMembershipUser $user)
	{

	}
	public function validateUser($username,$password)
	{

	}
}
?>