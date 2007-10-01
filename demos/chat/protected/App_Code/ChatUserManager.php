<?php

class ChatUserManager extends TModule implements IUserManager
{
	/**
	 * @return string name for a guest user.
	 */
	public function getGuestName()
	{
		return 'Guest';
	}

	/**
	 * Returns a user instance given the user name.
	 * @param string user name, null if it is a guest.
	 * @return TUser the user instance
	 */
	public function getUser($username=null)
	{
		$user=new TUser($this);
		$user->setIsGuest(true);
		if($username !== null)
		{
			$user->setIsGuest(false);
			$user->setName($username);
			$user->setRoles(array('normal'));
		}
		return $user;
	}

	/**
	 * Add a new user to the database.
	 * @param string username.
	 */
	public function addNewUser($username)
	{
		$user = new ChatUserRecord();
		$user->username = $username;
		$user->save();
	}

	/**
	 * @return boolean true if username already exists, false otherwise.
	 */
	public function usernameExists($username)
	{
		return ChatUserRecord::finder()->findByUsername($username) instanceof ChatUserRecord;
	}

	/**
	 * Validates if the username exists.
	 * @param string user name
	 * @param string password
	 * @return boolean true if validation is successful, false otherwise.
	 */
	public function validateUser($username,$password)
	{
		return $this->usernameExists($username);
	}

	/**
	 * Saves user auth data into a cookie.
	 * @param THttpCookie the cookie to receive the user auth data.
	 * @since 3.1.1
	 */
	public function saveUserToCookie($cookie)
	{
		// do nothing since we don't support cookie-based auth in this example
	}

	/**
	 * Validates if the username and password are correct.
	 * @param string user name
	 * @param string password
	 * @return boolean true if validation is successful, false otherwise.
	 */
	public function validateUser($username,$password)
	{
		// do nothing since we don't support cookie-based auth in this example
		return false;
	}
}


?>