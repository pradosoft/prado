<?php

require_once(__DIR__ . '/../../../ActiveRecord/records/ItemRecord.php');

abstract class SqliteDbSpecificRecord extends TActiveRecord
{
	protected static $conn;

	public function getDbConnection()
	{
		if (self::$conn === null) {
			self::$conn = new TDbConnection('sqlite:' . __DIR__ . '/../../../ActiveRecord/fk_tests.db');
		}
		return self::$conn;
	}
}

class SqliteAlbum extends SqliteDbSpecificRecord
{
	const TABLE = 'album';

	public $title;

	public $Tracks = [];
	public $Artists = [];

	public $cover;

	public static $RELATIONS = [
		'Tracks' => [self::HAS_MANY, 'SqliteTrack'],
		'Artists' => [self::MANY_TO_MANY, 'SqliteArtist', 'album_artists'],
		'cover' => [self::HAS_ONE, 'SqliteCover']
	];

	public static function finder($class = __CLASS__)
	{
		return parent::finder($class);
	}
}

class SqliteArtist extends SqliteDbSpecificRecord
{
	const TABLE = 'artist';

	public $name;

	public $Albums = [];

	public static $RELATIONS = [
		'Albums' => [self::MANY_TO_MANY, 'SqliteAlbum', 'album_artists']
	];

	public static function finder($class = __CLASS__)
	{
		return parent::finder($class);
	}
}

class SqliteTrack extends SqliteDbSpecificRecord
{
	const TABLE = 'track';

	public $id;
	public $song_name;
	public $album_id;

	public $Album;

	public static $RELATIONS = [
		'Album' => [self::BELONGS_TO, 'SqliteAlbum'],
	];

	public static function finder($class = __CLASS__)
	{
		return parent::finder($class);
	}
}

class SqliteCover extends SqliteDbSpecificRecord
{
	const TABLE = 'cover';

	public $album;
	public $content;
}

class SqliteForeignKeyTest extends PHPUnit\Framework\TestCase
{
	public function test_sqlite_has_many()
	{
		$albums = SqliteAlbum::finder()->withTracks()->findAll();
		$this->assertEquals(count($albums), 2);

		$this->assertEquals($albums[0]->title, 'Album 1');
		$this->assertEquals($albums[1]->title, 'Album 2');

		$this->assertEquals(count($albums[0]->Artists), 0);
		$this->assertEquals(count($albums[1]->Artists), 0);

		$this->assertEquals(count($albums[0]->Tracks), 3);
		$this->assertEquals(count($albums[1]->Tracks), 2);

		$this->assertEquals($albums[0]->Tracks[0]->song_name, 'Track 1');
		$this->assertEquals($albums[0]->Tracks[1]->song_name, 'Song 2');
		$this->assertEquals($albums[0]->Tracks[2]->song_name, 'Song 3');

		$this->assertEquals($albums[1]->Tracks[0]->song_name, 'Track A');
		$this->assertEquals($albums[1]->Tracks[1]->song_name, 'Track B');
	}

	public function test_sqlite_has_one()
	{
		$albums = SqliteAlbum::finder()->with_cover()->findAll();
		$this->assertEquals(count($albums), 2);

		$this->assertEquals($albums[0]->title, 'Album 1');
		$this->assertEquals($albums[1]->title, 'Album 2');

		$this->assertEquals($albums[0]->cover->content, 'lalala');
		$this->assertEquals($albums[1]->cover->content, 'conver content');

		$this->assertEquals(count($albums[0]->Artists), 0);
		$this->assertEquals(count($albums[1]->Artists), 0);

		$this->assertEquals(count($albums[0]->Tracks), 0);
		$this->assertEquals(count($albums[1]->Tracks), 0);
	}

	public function test_sqlite_belongs_to()
	{
		$track = SqliteTrack::finder()->withAlbum()->find('id = ?', 1);

		$this->assertEquals($track->id, "1");
		$this->assertEquals($track->song_name, "Track 1");
		$this->assertEquals($track->Album->title, "Album 1");
	}

	public function test_sqlite_has_many_associate()
	{
		$album = SqliteAlbum::finder()->withArtists()->find('title = ?', 'Album 2');
		$this->assertEquals($album->title, 'Album 2');
		$this->assertEquals(count($album->Artists), 3);

		$this->assertEquals($album->Artists[0]->name, 'Dan');
		$this->assertEquals($album->Artists[1]->name, 'Karl');
		$this->assertEquals($album->Artists[2]->name, 'Tom');
	}

	public function test_sqlite_multiple_fk()
	{
		$album = SqliteAlbum::finder()->withArtists()->withTracks()->with_cover()->find('title = ?', 'Album 1');

		$this->assertEquals($album->title, 'Album 1');
		$this->assertEquals(count($album->Artists), 2);

		$this->assertEquals($album->Artists[0]->name, 'Dan');
		$this->assertEquals($album->Artists[1]->name, 'Jenny');

		$this->assertEquals($album->Tracks[0]->song_name, 'Track 1');
		$this->assertEquals($album->Tracks[1]->song_name, 'Song 2');
		$this->assertEquals($album->Tracks[2]->song_name, 'Song 3');

		$this->assertEquals($album->cover->content, 'lalala');
	}
}
