<?php
/**
 * EditUser class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado4
 * @copyright Copyright &copy; 2006-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado4/blob/master/LICENSE
 */

/**
 * EditUser class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado4
 * @copyright Copyright &copy; 2006-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado4/blob/master/LICENSE
 */
class EditUser extends BlogPage
{
	private $_userRecord=null;

	public function onInit($param)
	{
		parent::onInit($param);
		if(($id=$this->Request['id'])!==null)
		{
			$id=TPropertyValue::ensureInteger($id);
			if(!$this->User->IsAdmin && $this->User->ID!==$id)
				throw new BlogException(500,'profile_edit_disallowed',$id);
		}
		else
			$id=$this->User->ID;
		if(($this->_userRecord=$this->DataAccess->queryUserByID($id))===null)
			throw new BlogException(500,'profile_id_invalid',$id);
	}

	public function onLoad($param)
	{
		parent::onLoad($param);
		if(!$this->IsPostBack)
		{
			$userRecord=$this->_userRecord;
			$this->Username->Text=$userRecord->Name;
			$this->FullName->Text=$userRecord->FullName;
			$this->Email->Text=$userRecord->Email;
			$this->Website->Text=$userRecord->Website;
		}
	}

	public function saveButtonClicked($sender,$param)
	{
		if($this->IsValid)
		{
			$userRecord=$this->_userRecord;
			if($this->Password->Text!=='')
				$userRecord->Password=md5($this->Password->Text);
			$userRecord->FullName=$this->FullName->Text;
			$userRecord->Email=$this->Email->Text;
			$userRecord->Website=$this->Website->Text;
			$this->DataAccess->updateUser($userRecord);
			$authManager=$this->Application->getModule('auth');
			$this->gotoPage('Users.ViewUser',array('id'=>$userRecord->ID));
		}
	}
}

