<?php


interface IMembershipUser
{
	public function getEmail();
	public function setEmail($value);
	public function getCreationDate();
	public function setCreationDate($value);
	public function getIsApproved();
	public function setIsApproved($value);
	public function getIsLockedOut();
	public function setIsLockedOut($value);
	public function getIsOnline();
	public function setIsOnline($value);
	public function getLastLoginDate();
	public function setLastLoginDate($value);
	public function getLastActivityDate();
	public function setLastActivityDate($value);
	public function getLastLockoutDate();
	public function setLastLockoutDate($value);
	public function getLastPasswordChangedDate();
	public function setLastPasswordChangedDate($value);
	public function getPasswordQuestion();
	public function setPasswordQuestion($value);
	public function getComment();
	public function setComment($value);

	public function update();
	public function fetchPassword($passwordAnswer=null);
	public function changePassword($username,$oldPassword,$newPassword);
	public function changePasswordQuestionAndAnswer($username,$password,$newQuestion,$newAnswer);
	public function resetPassword($passwordAnswer=null);
}

interface IUserManager
{
}



class TMembershipUser extends TUser implements IMembershipUser
{
}

interface IRoleProvider
{
	public function addUsersToRoles($users,$roles);
	public function removeUsersFromRoles($users,$roles);
	public function createRole($role);
	public function deleteRole($role,$throwOnPopulatedRole);
	public function getAllRoles();
	public function getRolesForUser($user);
	public function getUsersInRole($role);
	public function isUserInRole($user,$role);
	public function roleExists($role);
}

interface IMembershipProvider
{
	public function getApplicationName();
	public function setApplicationName($value);

	public function createUser($username,$password,$email,$question,$answer,$isApproved); // return $key or error status
	public function deleteUser($username,$deleteAllRelatedData);
	public function updateUser($user);

	public function changePassword($username,$oldPassword,$newPassword);
	public function changePasswordQuestionAndAnswer($username,$password,$newQuestion,$newAnswer);

	public function encryptPassword($password);
	public function decryptPassword($encodedPassword);
	public function encodePassword($password,$format,$salt);
	public function decodePassword($password,$format);
	public function generateSalt();

	public function findUsersByEmail($email,$pageIndex,$pageSize);
	public function findUsersByName($email,$pageIndex,$pageSize);

	public function getAllUsers($pageIndex,$pageSize);
	public function getUser($username,$userkey,$userIsOnline);
	public function getNumberOfUsersOnline(); //???
	public function getUsernameByEmail($email);
	public function getPassword($username,$answer);
	public function resetPassword($username,$answer);
	public function unlockUser($username);

	public function validateUser($username,$password);

	public function onValidatingPassword($param);

	public function getEnablePasswordReset();
	public function setEnablePasswordReset($value);
	public function getEnablePasswordRetrieval();
	public function setEnablePasswordRetrieval($value);
	public function getMaxInvalidPasswordAttempts();
	public function setMaxInvalidPasswordAttempts($value);
	public function getUsernameFormat();
	public function setUsernameFormat($value);
	public function getPasswordFormat();
	public function setPasswordFormat($value);
	public function getRequiresQuestionAndAnswer();
	public function setRequiresQuestionAndAnswer($value);
	public function getRequiresUniqueEmail();
	public function setRequiresUniqueEmail($value);
}


?>