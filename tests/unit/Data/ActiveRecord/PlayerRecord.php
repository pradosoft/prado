<?php

namespace Prado\Test\Unit\Data\ActiveRecord;

use Prado\Collections\TList;

class PlayerRecord extends BaseFkRecord
{
	const TABLE = 'players';
	public $player_id;
	public $age;
	public $team_name;

	public $team;
	private $_skills;
	public $profile;

	public static $RELATIONS = [
		'skills' => [self::MANY_TO_MANY, SkillRecord::class, 'player_skills'],
		'team' => [self::BELONGS_TO, TeamRecord::class],
		'profile' => [self::HAS_ONE, ProfileRecord::class],
	];

	public static function finder($className = __CLASS__)
	{
		return parent::finder($className);
	}

	public function getSkills()
	{
		if ($this->_skills === null && $this->player_id !== null) {
			//lazy load the skill records
			$this->setSkills($this->withSkills()->findByPk($this->player_id)->skills);
		} elseif ($this->_skills === null) {
			//create new TList;
			$this->setSkills(new TList());
		}
		return $this->_skills;
	}

	public function setSkills($value)
	{
		$this->_skills = $value instanceof TList ? $value : new TList($value);
	}
}
