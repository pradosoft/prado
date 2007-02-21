<?php
class Home extends BlogPage
{
	public function onLoad($param)
	{
		parent::onLoad($param);

		//		sqlite_open(dirname(__FILE__).'/../App_Data/blog.db');

		//		$finder = UserRecord::finder();
		//		$user = $finder->findByPk(1);
		//		echo TVarDumper::dump($user,10,true);
		//
		//		$finder = PostRecord::finder();
		//		$post = $finder->findByPk(1);
		//		echo TVarDumper::dump($post,10,true);
		//
		//		$finder = CategoryRecord::finder();
		//		$categories = $finder->findAll();
		//		echo TVarDumper::dump($categories,10,true);
		//
		//		$finder = Post2CategoryRecord::finder();
		//		$post2category = $finder->findByPk(1,1);
		//		echo TVarDumper::dump($post2category,10,true);
	}
}
?>