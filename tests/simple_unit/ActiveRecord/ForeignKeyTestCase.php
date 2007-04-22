<?php

Prado::using('System.Data.ActiveRecord.TActiveRecord');

abstract class Mysql4Record extends TActiveRecord
{
	protected static $conn;

	public function getDbConnection()
	{
		if(self::$conn===null)
			self::$conn = new TDbConnection('mysql:host=localhost;port=3306;dbname=tests', 'test4', 'test4');
		return self::$conn;
	}
}

class Album extends Mysql4Record
{
	public $title;

	public $Tracks = array(self::HAS_MANY, 'Track');
	public $Artists = array(self::HAS_MANY, 'Artist', 'album_artist');

	public static function finder($class=__CLASS__)
	{
		return parent::finder($class);
	}
}

class Artist extends Mysql4Record
{
	public $name;

	public $Albums = array(self::HAS_MANY, 'Album', 'album_artist');

	public static function finder($class=__CLASS__)
	{
		return parent::finder($class);
	}
}

class Track extends Mysql4Record
{
	public $id;
	public $song_name;
	public $album_id; //FK -> Album.id

	public $Album = array(self::BELONGS_TO, 'Album');

	public static function finder($class=__CLASS__)
	{
		return parent::finder($class);
	}
}

abstract class SqliteRecord extends TActiveRecord
{
	protected static $conn;

	public function getDbConnection()
	{
		if(self::$conn===null)
			self::$conn = new TDbConnection('sqlite:'.dirname(__FILE__).'/blog.db');
		return self::$conn;
	}
}

class PostRecord extends SqliteRecord
{
	const TABLE='posts';
	public $post_id;
	public $author;
	public $create_time;
	public $title;
	public $content;
	public $status;

	public $authorRecord = array(self::HAS_ONE, 'BlogUserRecord');

	public static function finder($className=__CLASS__)
	{
		return parent::finder($className);
	}
}
class BlogUserRecord extends SqliteRecord
{
	const TABLE='users';
	public $username;
	public $email;
	public $password;
	public $role;
	public $first_name;
	public $last_name;

	public $posts = array(self::HAS_MANY, 'PostRecord');

	public static function finder($className=__CLASS__)
	{
		return parent::finder($className);
	}
}

class ForeignKeyTestCase extends UnitTestCase
{
	function test()
	{
		$album = Album::finder()->withTracks()->findAll();
		//print_r($album);
		//print_r(PostRecord::finder()->findAll());
		//print_r(BlogUserRecord::finder()->with_posts()->findAll());
	}
}

?>