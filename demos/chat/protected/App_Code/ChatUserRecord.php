<?php

class ChatUserRecord extends TActiveRecord
{
	public $username;
	private $_last_activity;

	public static $_tablename='chat_users';

	public function getLast_Activity()
	{
		if($this->_last_activity === null)
			$this->_last_activity = time();
		return $this->_last_activity;
	}

	public function setLast_Activity($value)
	{
		$this->_last_activity = $value;
	}

	public static function finder()
	{
		return parent::getRecordFinder('ChatUserRecord');
	}

	public function getUserList()
	{
		$this->deleteAll('last_activity < ?', time()-300); //5 min inactivity
		$content = '<ul>';
		foreach($this->findAll() as $user)
		{
			$content .= '<li>'.htmlspecialchars($user->username).'</li>';
		}
		$content .= '</ul>';

		return $content;
	}
}

?>