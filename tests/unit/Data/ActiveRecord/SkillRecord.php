<?php

namespace Prado\Test\Unit\Data\ActiveRecord;

class SkillRecord extends BaseFkRecord
{
	const TABLE = 'skills';
	public $skill_id;
	public $name;

	public $players = [];

	public static $RELATIONS = [
		'players' => [self::MANY_TO_MANY, PlayerRecord::class, 'player_skills'],
	];

	public static function finder($className = __CLASS__)
	{
		return parent::finder($className);
	}
}
