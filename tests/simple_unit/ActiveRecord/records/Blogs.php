<?php

class Blogs extends TActiveRecord
{
	public $blog_id;
	public $blog_name;
	public $blog_author;

	public static function finder()
	{
		return self::getRecordFinder('Blogs');
	}
}

?>