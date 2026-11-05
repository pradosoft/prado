<?php

namespace Prado\Test\Unit\Data\ActiveRecord;

class Artist extends SqliteRecord
{
	public $name;

	public $Albums = [];

	public static $RELATIONS = [
		'Albums' => [self::MANY_TO_MANY, Album::class, 'album_artists']
	];

	public static function finder($class = __CLASS__)
	{
		return parent::finder($class);
	}
}
