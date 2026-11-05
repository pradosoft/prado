<?php

namespace Prado\Test\Unit\Data\ActiveRecord;

class TeamRecord extends BaseFkRecord
{
	const TABLE = 'teams';
	public $name;
	public $location;

	public $players = [];

	//define the $player member having has many relationship with PlayerRecord
	public static $RELATIONS = [
		'players' => [self::HAS_MANY, PlayerRecord::class],
	];

	public static function finder($className = __CLASS__)
	{
		return parent::finder($className);
	}
}
