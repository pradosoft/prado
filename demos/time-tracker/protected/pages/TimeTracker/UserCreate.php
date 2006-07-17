<?php
/**
 * UserCreate page class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2006 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $16/07/2006: $
 * @package Demos
 */

/**
 * Create new user page class. Validate that the usernames are unique
 * and set the new user credentials as the current application credentials.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Revision: $  $16/07/2006: $
 * @package Demos
 * @since 3.1
 */
class UserCreate extends TPage
{
	/**
	 * Verify that the username is not taken.
	 * @param TControl custom validator that created the event.
	 * @param TServerValidateEventParameter validation parameters.
	 */
	public function checkUsername($sender, $param)
	{
		$userDao = $this->Application->Modules['daos']->getDao('UserDao');
		$user = $userDao->getUserByName($this->username->Text);
		if(!is_null($user))
		{
			$param->IsValid = false;
			$sender->ErrorMessage = 
				"The user name is already taken, try '{$user->Name}01'";
		}
	}
	
	/**
	 * Create a new user if all data entered are valid.
	 * The default user roles are obtained from "config.xml". The new user
	 * details is saved to the database and the new credentials are used as the
	 * application user. The user is redirected to the requested page.
	 * @param TControl button control that created the event.
	 * @param TEventParameter event parameters.
	 */
	public function createNewUser($sender, $param)
	{
		if($this->IsValid)
		{
			$newUser = new TimeTrackerUser($this->User->Manager);
			$newUser->EmailAddress = $this->email->Text;
			$newUser->Name = $this->username->Text;
			$newUser->IsGuest = false;
			$newUser->Roles = $this->Application->Parameters['NewUserRoles'];
	
			//save the user
			$userDao = $this->Application->Modules['daos']->getDao('UserDao');
			$userDao->addNewUser($newUser, $this->password->Text);
	
			//update the user
			$this->User->Manager->updateCredential($newUser);
			
			//return to requested page
			$this->Response->redirect($auth->getReturnUrl());
			
			//goto default page.
			//$url = $this->Service->constructUrl($this->Service->DefaultPage);
			//$this->Response->redirect($url);		
		}
	}
}

?>