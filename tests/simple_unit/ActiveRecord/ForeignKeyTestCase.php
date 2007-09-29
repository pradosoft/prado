<?php

Prado::using('System.Data.ActiveRecord.TActiveRecord');
require_once(dirname(__FILE__).'/records/ItemRecord.php');

abstract class SqliteRecord extends TActiveRecord
{
	protected static $conn;

	public function getDbConnection()
	{
		if(self::$conn===null)
			self::$conn = new TDbConnection('sqlite:'.dirname(__FILE__).'/fk_tests.db');
		return self::$conn;
	}
}

class Album extends SqliteRecord
{
	public $title;

	public $Tracks = array();
	public $Artists = array();

	public $cover;

	public static $RELATIONS = array(
		'Tracks' => array(self::HAS_MANY, 'Track'),
		'Artists' => array(self::HAS_MANY, 'Artist', 'album_artists'),
		'cover' => array(self::HAS_ONE, 'Cover')
	);

	public static function finder($class=__CLASS__)
	{
		return parent::finder($class);
	}
}

class Artist extends SqliteRecord
{
	public $name;

	public $Albums = array();

	public static $RELATIONS=array(
		'Albums' => array(self::HAS_MANY, 'Album', 'album_artists')
	);

	public static function finder($class=__CLASS__)
	{
		return parent::finder($class);
	}
}

class Track extends SqliteRecord
{
	public $id;
	public $song_name;
	public $album_id; //FK -> Album.id

	public $Album;

	public static $RELATIONS = array(
		'Album' => array(self::BELONGS_TO, 'Album'),
	);

	public static function finder($class=__CLASS__)
	{
		return parent::finder($class);
	}
}

class Cover extends SqliteRecord
{
	public $album;
	public $content;
}

class ForeignKeyTestCase extends UnitTestCase
{
	function test_has_many()
	{
		$albums = Album::finder()->withTracks()->findAll();
		$this->assertEqual(count($albums), 2);

		$this->assertEqual($albums[0]->title, 'Album 1');
		$this->assertEqual($albums[1]->title, 'Album 2');

		$this->assertEqual(count($albums[0]->Artists), 0);
		$this->assertEqual(count($albums[1]->Artists), 0);

		$this->assertEqual(count($albums[0]->Tracks), 3);
		$this->assertEqual(count($albums[1]->Tracks), 2);

		$this->assertEqual($albums[0]->Tracks[0]->song_name, 'Track 1');
		$this->assertEqual($albums[0]->Tracks[1]->song_name, 'Song 2');
		$this->assertEqual($albums[0]->Tracks[2]->song_name, 'Song 3');

		$this->assertEqual($albums[1]->Tracks[0]->song_name, 'Track A');
		$this->assertEqual($albums[1]->Tracks[1]->song_name, 'Track B');
	}

	function test_has_one()
	{
		$albums = Album::finder()->with_cover()->findAll();
		$this->assertEqual(count($albums), 2);

		$this->assertEqual($albums[0]->title, 'Album 1');
		$this->assertEqual($albums[1]->title, 'Album 2');

		$this->assertEqual($albums[0]->cover->content, 'lalala');
		$this->assertEqual($albums[1]->cover->content, 'conver content');

		$this->assertEqual(count($albums[0]->Artists), 0);
		$this->assertEqual(count($albums[1]->Artists), 0);

		$this->assertEqual(count($albums[0]->Tracks), 0);
		$this->assertEqual(count($albums[1]->Tracks), 0);
	}

	function test_belongs_to()
	{
		$track = Track::finder()->withAlbum()->find('id = ?', 1);

		$this->assertEqual($track->id, "1");
		$this->assertEqual($track->song_name, "Track 1");
		$this->assertEqual($track->Album->title, "Album 1");
	}

	function test_has_many_associate()
	{
		$album = Album::finder()->withArtists()->find('title = ?', 'Album 2');
		$this->assertEqual($album->title, 'Album 2');
		$this->assertEqual(count($album->Artists), 3);

		$this->assertEqual($album->Artists[0]->name, 'Dan');
		$this->assertEqual($album->Artists[1]->name, 'Karl');
		$this->assertEqual($album->Artists[2]->name, 'Tom');
	}

	function test_multiple_fk()
	{
		$album = Album::finder()->withArtists()->withTracks()->with_cover()->find('title = ?', 'Album 1');

		$this->assertEqual($album->title, 'Album 1');
		$this->assertEqual(count($album->Artists), 2);

		$this->assertEqual($album->Artists[0]->name, 'Dan');
		$this->assertEqual($album->Artists[1]->name, 'Jenny');

		$this->assertEqual($album->Tracks[0]->song_name, 'Track 1');
		$this->assertEqual($album->Tracks[1]->song_name, 'Song 2');
		$this->assertEqual($album->Tracks[2]->song_name, 'Song 3');

		$this->assertEqual($album->cover->content, 'lalala');
	}

	function test_self_reference_fk()
	{
		$item = ItemRecord::finder()->withRelated_Items()->findByPk(1);
		$this->assertNotNull($item);
		$this->assertEqual($item->name, "Professional Work Attire");

		$this->assertEqual(count($item->related_items),2);
		$this->assertEqual($item->related_items[0]->name, "Nametags");
		$this->assertEqual($item->related_items[0]->item_id, 2);

		$this->assertEqual($item->related_items[1]->name, "Grooming and Hygiene");
		$this->assertEqual($item->related_items[1]->item_id, 3);
	}
}

?>