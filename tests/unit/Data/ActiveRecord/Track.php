<?php

namespace Prado\Test\Unit\Data\ActiveRecord;

class Track extends SqliteRecord
{
	public $id;
	public $song_name;
	public $album_id; //FK -> Album.id

	public $Album;

	public static $RELATIONS = [
		'Album' => [self::BELONGS_TO, Album::class],
	];

	public static function finder($class = __CLASS__)
	{
		return parent::finder($class);
	}
}
