<?php

require_once 'phing/Task.php';

/**
 * Task to index quickstart
 */	
class QuickstartIndexTask extends Task
{
	private $todir;

	public function setTodir($value)
	{
		$this->todir=$value;
	}

	public function main()
	{
		$srcdir=realpath(dirname(__FILE__).'/../../../');
		$zend_path = $srcdir.'/demos/quickstart/protected/index';
		set_include_path(get_include_path().PATH_SEPARATOR.realpath($zend_path));
		require_once ('Zend/Search/Lucene.php');
		
		require_once($srcdir.'/buildscripts/index/quickstart_index.php');
		$quickstart_source = $srcdir.'/buildscripts/texbuilder/pages.php';
		$quickstart_base = $srcdir.'/demos/quickstart/protected/pages/';
		$quickstart = new quickstart_index($this->todir, realpath($quickstart_base), realpath($quickstart_source));
		$quickstart->create_index();
	}
}

?>