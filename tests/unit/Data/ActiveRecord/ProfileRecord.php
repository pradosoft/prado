<?php

namespace Prado\Test\Unit\Data\ActiveRecord;

class ProfileRecord extends BaseFkRecord
{
	const TABLE = 'profiles';
	public $fk_player_id;
	public $salary;

	public $player;

	public static $RELATIONS = [
		'player' => [self::BELONGS_TO, PlayerRecord::class],
	];

	public static function finder($className = __CLASS__)
	{
		return parent::finder($className);
	}
}
