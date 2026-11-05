<?php

namespace Prado\Test\Unit\Data\ActiveRecord;

class Album extends SqliteRecord
{
	public $title;

	public $Tracks = [];
	public $Artists = [];

	public $cover;

	public static $RELATIONS = [
		'Tracks' => [self::HAS_MANY, Track::class],
		'Artists' => [self::MANY_TO_MANY, Artist::class, 'album_artists'],
		'cover' => [self::HAS_ONE, Cover::class]
	];

	public static function finder($class = __CLASS__)
	{
		return parent::finder($class);
	}
}
