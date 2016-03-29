<?php
Prado::using('System.Data.ActiveRecord.TActiveRecord');
require_once(dirname(__FILE__).'/records/Blogs.php');

/**
 * @package System.Data.ActiveRecord
 */
class ActiveRecordMySql5Test extends PHPUnit_Framework_TestCase
{
	function setup()
	{
		$conn = new TDbConnection('mysql:host=localhost;dbname=prado_unitest', 'prado_unitest','prado_unitest');
		TActiveRecordManager::getInstance()->setDbConnection($conn);
	}

	function test_find_first_blog()
	{
		$blog = Blogs::finder()->findByPk(1);
		$this->assertNotNull($blog);
	}

	function test_insert_new_blog()
	{
		$blog = new Blogs();
		$blog->blog_name = 'test1';
		$blog->blog_author = 'wei';

		$this->assertTrue($blog->save());

		$blog->blog_name = 'test2';

		$this->assertTrue($blog->save());

		$check = Blogs::finder()->findByPk($blog->blog_id);

		$this->assertSameBlog($check,$blog);

		$this->assertTrue($blog->delete());
	}

	function assertSameBlog($check, $blog)
	{
		$props = array('blog_id', 'blog_name', 'blog_author');
		foreach($props as $prop)
			$this->assertEquals($check->{$prop}, $blog->{$prop});
	}

}