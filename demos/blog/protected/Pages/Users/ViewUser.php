<?php

class ViewUser extends BlogPage
{
	private $_currentUser=null;

	public function getCurrentUser()
	{
		if($this->_currentUser===null)
		{
			$id=TPropertyValue::ensureInteger($this->Request['id']);
			if(($this->_currentUser=$this->DataAccess->queryUserByID($id))===null)
				throw new BlogException('xxx');
		}
		return $this->_currentUser;
	}
}

?>