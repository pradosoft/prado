<?php

class UserRecord extends TActiveRecord
{
	public $username;
	public $password;
	public $email;
	public $first_name;
	public $last_name;
	public $job_title;
	public $work_phone;
	public $work_fax;
	public $active = true;
	public $department_id;
	public $salutation;
	public $hint_question;
	public $hint_answer;

	private $_level = -1;

	const TABLE = 'Users';

	public function getLevel()
	{
		return $this->_level;
	}

	public function setLevel($level)
	{
		$this->_level = TPropertyValue::ensureInteger($level);
	}

	public static function finder($className = __CLASS__)
	{
		return parent::finder($className);
	}
}
